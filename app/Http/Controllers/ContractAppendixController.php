<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractAppendixRequest;
use App\Http\Requests\UpdateContractAppendixRequest;
use App\Http\Resources\ContractAppendixResource;
use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\ContractAppendixTemplate;
use App\Services\ContractAppendixGenerateService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractAppendixController extends Controller
{
    use AuthorizesRequests;

    public function index(Contract $contract)
    {
        $this->authorize('viewAny', ContractAppendix::class);

        $rows = ContractAppendix::where('contract_id', $contract->id)
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

        activity('contract-appendix')
            ->performedOn($row)->causedBy($request->user())
            ->withProperties([
                'contract_id' => $contract->id,
                'payload'     => $payload,
                'created'     => (new ContractAppendixResource($row))->resolve(),
            ])->log('created');

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

        $before = $appendix->getOriginal();
        $appendix->update($request->validated());

        activity('contract-appendix')
            ->performedOn($appendix)->causedBy($request->user())
            ->withProperties([
                'contract_id' => $contract->id,
                'before'      => $before,
                'after'       => $appendix->getAttributes(),
                'changed'     => array_keys($appendix->getChanges()),
            ])->log('updated');

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
        $appendix->delete();

        activity('contract-appendix')
            ->performedOn($appendix)->causedBy($request->user())
            ->withProperties([
                'contract_id' => $contract->id,
                'deleted'     => $snapshot,
            ])->log('deleted');

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
        ContractAppendix::whereIn('id', $rows->pluck('id'))->delete();

        activity('contract-appendix')
            ->causedBy($request->user())
            ->withProperties([
                'contract_id' => $contract->id,
                'ids'         => $ids,
                'deleted'     => $snapshots,
            ])->log('bulk-deleted');

        session()->flash('message', 'Đã xoá các phụ lục đã chọn!');
        session()->flash('type', 'success');

        return Inertia::location(route('contracts.show', [
            'contract' => $contract->id,
            'tab' => 'appendixes'
        ]));
    }

    public function approve(Request $request, Contract $contract, ContractAppendix $appendix)
    {
        $this->authorize('approve', $appendix);

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
            ])->log('approved');

        // (Tuỳ chọn) áp xuống "current snapshot" tức thì:
        // VD: nếu appendix có base_salary != null => cập nhật contract.base_salary (hoặc bảng snapshot riêng).
        // Bản tối ưu là tạo service ApplyAppendixService để đồng bộ các trường có giá trị.

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
            ])->log('rejected');

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
}
