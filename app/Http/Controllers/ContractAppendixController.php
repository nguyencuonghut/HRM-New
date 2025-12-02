<?php

namespace App\Http\Controllers;

use App\Events\AppendixSubmitted;
use App\Events\AppendixApproved;
use App\Events\AppendixRejected;
use App\Http\Requests\StoreContractAppendixRequest;
use App\Http\Requests\UpdateContractAppendixRequest;
use App\Http\Resources\ContractAppendixResource;
use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\ContractAppendixTemplate;
use App\Services\ContractAppendixGenerateService;
use App\Enums\ActivityLogDescription;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ContractAppendixController extends Controller
{
    use AuthorizesRequests;

    public function index(Contract $contract)
    {
        $this->authorize('viewAny', ContractAppendix::class);

        $rows = ContractAppendix::where('contract_id', $contract->id)
            ->with('attachments')
            ->orderBy('effective_date','desc')->get();

        return response()->json(ContractAppendixResource::collection($rows));
    }

    public function store(StoreContractAppendixRequest $request, Contract $contract)
    {
        $this->authorize('create', ContractAppendix::class);
        $payload = $request->validated();
        $payload['contract_id'] = $contract->id;

        // (Rule) Không cho 2 phụ lục ACTIVE trùng phạm vi với cùng loại quan trọng (ví dụ SALARY)
        // Có thể kiểm tra nâng cao theo loại nếu bạn muốn, tạm thời bỏ qua ở Phase 1.

        $row = ContractAppendix::create($payload);

        // Upload attachments nếu có
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('appendixes/' . $row->id . '/attachments', $fileName, 'public');

                $row->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        activity('contract-appendix')
            ->performedOn($row)->causedBy($request->user())
            ->withProperties([
                'contract_id' => $contract->id,
                'payload'     => $payload,
                'created'     => (new ContractAppendixResource($row))->resolve(),
            ])->log(ActivityLogDescription::APPENDIX_CREATED->value);

        session()->flash('message', 'Đã tạo phụ lục hợp đồng!');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    public function update(UpdateContractAppendixRequest $request, Contract $contract, ContractAppendix $appendix)
    {
        $this->authorize('update', $appendix);

        // Chỉ cho phép cập nhật appendix ở trạng thái DRAFT hoặc REJECTED
        if (!in_array($appendix->status, ['DRAFT', 'REJECTED'])) {
            session()->flash('message', 'Chỉ có thể chỉnh sửa phụ lục ở trạng thái Nháp hoặc Bị từ chối.');
            session()->flash('type', 'error');

            return Inertia::location(route('contracts.show', [
                'contract' => $contract->id,
                'tab' => 'appendixes'
            ]));
        }

        $before = $appendix->getOriginal();
        $appendix->update($request->validated());

        // Upload attachments mới nếu có
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('appendixes/' . $appendix->id . '/attachments', $fileName, 'public');

                $appendix->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        // Xóa attachments đã chọn (nếu có)
        if ($request->has('delete_attachments')) {
            $deleteIds = $request->input('delete_attachments', []);
            $attachmentsToDelete = $appendix->attachments()->whereIn('id', $deleteIds)->get();

            foreach ($attachmentsToDelete as $attachment) {
                if (Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
                $attachment->delete();
            }
        }

        activity('contract-appendix')
            ->performedOn($appendix)->causedBy($request->user())
            ->withProperties([
                'contract_id' => $contract->id,
                'before'      => $before,
                'after'       => $appendix->getAttributes(),
                'changed'     => array_keys($appendix->getChanges()),
            ])->log(ActivityLogDescription::APPENDIX_UPDATED->value);

        session()->flash('message', 'Đã cập nhật phụ lục hợp đồng!');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    public function destroy(Request $request, Contract $contract, ContractAppendix $appendix)
    {
        $this->authorize('delete', $appendix);

        $snapshot = (new ContractAppendixResource($appendix))->resolve();

        // Xóa file PDF nếu có
        if ($appendix->generated_pdf_path && Storage::disk('public')->exists($appendix->generated_pdf_path)) {
            Storage::disk('public')->delete($appendix->generated_pdf_path);
        }

        $appendix->delete();

        activity('contract-appendix')
            ->performedOn($appendix)->causedBy($request->user())
            ->withProperties([
                'contract_id' => $contract->id,
                'deleted'     => $snapshot,
            ])->log(ActivityLogDescription::APPENDIX_DELETED->value);

        session()->flash('message', 'Đã xoá phụ lục hợp đồng!');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    public function bulkDelete(Request $request, Contract $contract)
    {
        $this->authorize('bulkDelete', ContractAppendix::class);

        $ids  = (array) $request->input('ids', []);
        $rows = ContractAppendix::where('contract_id',$contract->id)->whereIn('id',$ids)->get();
        $snapshots = ContractAppendixResource::collection($rows)->resolve();

        // Xóa các file PDF nếu có
        foreach ($rows as $appendix) {
            if ($appendix->generated_pdf_path && Storage::disk('public')->exists($appendix->generated_pdf_path)) {
                Storage::disk('public')->delete($appendix->generated_pdf_path);
            }
        }

        ContractAppendix::whereIn('id', $rows->pluck('id'))->delete();

        activity('contract-appendix')
            ->causedBy($request->user())
            ->withProperties([
                'contract_id' => $contract->id,
                'ids'         => $ids,
                'deleted'     => $snapshots,
            ])->log(ActivityLogDescription::APPENDIX_BULK_DELETED->value);

        session()->flash('message', 'Đã xoá các phụ lục đã chọn!');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    public function submitForApproval(Request $request, Contract $contract, ContractAppendix $appendix)
    {
        $this->authorize('submit', $appendix);

        // Chỉ appendix ở trạng thái DRAFT hoặc REJECTED mới có thể gửi phê duyệt
        if (!in_array($appendix->status, ['DRAFT', 'REJECTED'])) {
            session()->flash('message', 'Chỉ phụ lục ở trạng thái Nháp hoặc Bị từ chối mới có thể gửi phê duyệt.');
            session()->flash('type', 'error');

            return Inertia::location(route('contracts.show', [
                'contract' => $contract->id,
                'tab' => 'appendixes'
            ]));
        }

        $appendix->update(['status' => 'PENDING_APPROVAL']);

        activity('contract-appendix')
            ->performedOn($appendix)->causedBy($request->user())
            ->withProperties([
                'action'      => 'submitted_for_approval',
                'contract_id' => $contract->id,
            ])->log(ActivityLogDescription::APPENDIX_SUBMITTED->value);

        // Dispatch event để gửi notification
        event(new AppendixSubmitted($appendix));

        session()->flash('message', 'Đã gửi phụ lục hợp đồng để phê duyệt.');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    public function recall(Request $request, Contract $contract, ContractAppendix $appendix)
    {
        $this->authorize('recall', $appendix);

        // Chỉ appendix ở trạng thái PENDING_APPROVAL mới có thể thu hồi
        if ($appendix->status !== 'PENDING_APPROVAL') {
            session()->flash('message', 'Chỉ phụ lục đang chờ phê duyệt mới có thể thu hồi.');
            session()->flash('type', 'error');

            return Inertia::location(route('contracts.show', [
                'contract' => $contract->id,
                'tab' => 'appendixes'
            ]));
        }

        $appendix->update(['status' => 'DRAFT']);

        activity('contract-appendix')
            ->performedOn($appendix)->causedBy($request->user())
            ->withProperties([
                'action'      => 'recalled',
                'contract_id' => $contract->id,
            ])->log(ActivityLogDescription::APPENDIX_RECALLED->value);

        session()->flash('message', 'Đã thu hồi yêu cầu phê duyệt phụ lục.');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    public function approve(Request $request, Contract $contract, ContractAppendix $appendix)
    {
        $this->authorize('approve', $appendix);

        // Nếu là phụ lục gia hạn, sử dụng ContractRenewalService
        if ($appendix->appendix_type === \App\Enums\AppendixType::EXTENSION) {
            $renewalService = app(\App\Services\ContractRenewalService::class);
            $renewalService->approveRenewal($appendix, $request->user(), $request->input('approval_note'));

            session()->flash('message', 'Đã phê duyệt phụ lục gia hạn.');
            session()->flash('type', 'success');

            return Inertia::location(route('contracts.show', [
                'contract' => $contract->id,
                'tab' => 'appendixes'
            ]));
        }

        // Xử lý phê duyệt cho các loại phụ lục khác (không phải gia hạn)
        $appendix->update([
            'status' => 'ACTIVE',
            'approver_id' => $request->user()->id,
            'approved_at' => now(),
            'rejected_at' => null,
            'approval_note' => $request->input('approval_note')
        ]);

        activity('contract-appendix')
            ->performedOn($appendix)->causedBy($request->user())
            ->withProperties([
                'action'        => 'approved',
                'contract_id'   => $contract->id,
                'approval_note' => $request->input('approval_note'),
            ])->log(ActivityLogDescription::APPENDIX_APPROVED->value);

        // Dispatch event để gửi notification
        event(new AppendixApproved($appendix, $request->user(), $request->input('approval_note')));

        session()->flash('message', 'Đã phê duyệt phụ lục.');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    public function reject(Request $request, Contract $contract, ContractAppendix $appendix)
    {
        $this->authorize('approve', $appendix);

        // Nếu là phụ lục gia hạn, sử dụng ContractRenewalService
        if ($appendix->appendix_type === \App\Enums\AppendixType::EXTENSION) {
            $renewalService = app(\App\Services\ContractRenewalService::class);
            $renewalService->rejectRenewal($appendix, $request->user(), $request->input('approval_note'));

            session()->flash('message', 'Đã từ chối phụ lục gia hạn.');
            session()->flash('type', 'success');

            return Inertia::location(route('contracts.show', [
                'contract' => $contract->id,
                'tab' => 'appendixes'
            ]));
        }

        // Xử lý từ chối cho các loại phụ lục khác (không phải gia hạn)
        $appendix->update([
            'status' => 'REJECTED',
            'approver_id' => $request->user()->id,
            'rejected_at' => now(),
            'approval_note' => $request->input('approval_note')
        ]);

        activity('contract-appendix')
            ->performedOn($appendix)->causedBy($request->user())
            ->withProperties([
                'action'        => 'rejected',
                'contract_id'   => $contract->id,
                'approval_note' => $request->input('approval_note'),
            ])->log(ActivityLogDescription::APPENDIX_REJECTED->value);

        // Dispatch event để gửi notification
        event(new AppendixRejected($appendix, $request->user(), $request->input('approval_note')));

        session()->flash('message', 'Đã từ chối phụ lục.');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    public function generate(
        Request $request,
        Contract $contract,
        ContractAppendix $appendix,
        ContractAppendixGenerateService $generator
    ) {
        $this->authorize('update', $appendix);

        $templateId = $request->input('template_id');
        $template   = null;

        if ($templateId) {
            $template = ContractAppendixTemplate::find($templateId);
        }

        $generator->generate($appendix, $template);

        session()->flash('message', 'Đã sinh file PDF cho phụ lục.');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    /**
     * Download appendix attachment
     */
    public function downloadAttachment(\App\Models\ContractAppendixAttachment $attachment)
    {
        $appendix = $attachment->appendix;
        $this->authorize('view', $appendix);

        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'File không tồn tại');
        }

        $path = Storage::disk('public')->path($attachment->file_path);

        // Return file inline (browser will display if possible, otherwise download)
        return response()->file($path, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"'
        ]);
    }
}
