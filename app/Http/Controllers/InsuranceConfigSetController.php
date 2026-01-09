<?php

namespace App\Http\Controllers;

use App\Models\InsuranceConfigSet;
use App\Models\InsuranceMinimumWageConfig;
use App\Models\InsuranceSalaryGradeConfig;
use App\Services\InsuranceConfigResolver;
use App\Http\Requests\StoreInsuranceConfigSetRequest;
use App\Http\Requests\UpdateInsuranceConfigSetRequest;
use App\Http\Resources\InsuranceConfigSetResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InsuranceConfigSetController extends Controller
{
    protected InsuranceConfigResolver $resolver;

    public function __construct(InsuranceConfigResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function index(Request $request)
    {
        $query = InsuranceConfigSet::query()->with(['minimumWages', 'salaryGrades']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('effective_from', 'desc');

        $perPage = $request->get('per_page', 10);
        $sets = $query->paginate($perPage);

        return Inertia::render('Insurance/ConfigSets/Index', [
            'configSets' => [
                'data' => InsuranceConfigSetResource::collection($sets->items())->resolve(),
                'pagination' => [
                    'total' => $sets->total(),
                    'per_page' => $sets->perPage(),
                    'current_page' => $sets->currentPage(),
                    'last_page' => $sets->lastPage(),
                ],
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('Insurance/ConfigSets/Form');
    }

    public function store(StoreInsuranceConfigSetRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $configSet = InsuranceConfigSet::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => InsuranceConfigSet::STATUS_DRAFT,
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['minimum_wages'] as $wage) {
                InsuranceMinimumWageConfig::create([
                    'config_set_id' => $configSet->id,
                    'region' => $wage['region'],
                    'amount' => $wage['amount'],
                    'note' => $wage['note'] ?? null,
                ]);
            }

            foreach ($data['salary_grades'] as $grade) {
                InsuranceSalaryGradeConfig::create([
                    'config_set_id' => $configSet->id,
                    'grade' => $grade['grade'],
                    'name' => $grade['name'],
                    'coefficient' => $grade['coefficient'],
                    'description' => $grade['description'] ?? null,
                    'is_active' => true,
                ]);
            }

            // Activity log
            activity()
                ->performedOn($configSet)
                ->causedBy($request->user())
                ->withProperties([
                    'attributes' => [
                        'code' => $configSet->code,
                        'name' => $configSet->name,
                        'status' => $configSet->status,
                        'effective_from' => $configSet->effective_from->format('Y-m-d'),
                        'effective_to' => $configSet->effective_to?->format('Y-m-d'),
                        'minimum_wages_count' => count($data['minimum_wages']),
                        'salary_grades_count' => count($data['salary_grades']),
                    ]
                ])
                ->log('Tạo bộ config bảo hiểm');

            DB::commit();

            return redirect()->route('insurance-config-sets.show', $configSet->id)
                ->with([
                    'message' => 'Tạo bộ config bảo hiểm thành công.',
                    'type' => 'success'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with([
                    'message' => 'Lỗi khi tạo config set: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    public function show($id)
    {
        $configSet = InsuranceConfigSet::with([
            'minimumWages' => fn($q) => $q->orderBy('region'),
            'salaryGrades' => fn($q) => $q->orderBy('grade'),
            'basedOnSet',
        ])->findOrFail($id);

        return Inertia::render('Insurance/ConfigSets/Detail', [
            'configSet' => InsuranceConfigSetResource::make($configSet)->resolve(),
            'validation' => $configSet->validateForActivation(),
            'mode' => 'view'
        ]);
    }

    public function edit($id)
    {
        $configSet = InsuranceConfigSet::with([
            'minimumWages' => fn($q) => $q->orderBy('region'),
            'salaryGrades' => fn($q) => $q->orderBy('grade'),
        ])->findOrFail($id);

        if (!$configSet->isDraft()) {
            return redirect()->route('insurance-config-sets.show', $id)
                ->with([
                    'message' => 'Chỉ có thể chỉnh sửa config ở trạng thái DRAFT.',
                    'type' => 'error'
                ]);
        }

        return Inertia::render('Insurance/ConfigSets/Detail', [
            'configSet' => InsuranceConfigSetResource::make($configSet)->resolve(),
            'validation' => $configSet->validateForActivation(),
            'mode' => 'edit'
        ]);
    }

    public function update(UpdateInsuranceConfigSetRequest $request, $id)
    {
        $configSet = InsuranceConfigSet::findOrFail($id);

        if (!$configSet->isDraft()) {
            return back()->with([
                'message' => 'Chỉ có thể cập nhật config ở trạng thái DRAFT.',
                'type' => 'error'
            ]);
        }

        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Store old data for activity log
            $oldAttributes = [
                'code' => $configSet->code,
                'name' => $configSet->name,
                'effective_from' => $configSet->effective_from->format('Y-m-d'),
            ];

            $configSet->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
            ]);

            // Update minimum wages
            $configSet->minimumWages()->delete();
            foreach ($data['minimum_wages'] as $wage) {
                InsuranceMinimumWageConfig::create([
                    'config_set_id' => $configSet->id,
                    'region' => $wage['region'],
                    'amount' => $wage['amount'],
                    'note' => $wage['note'] ?? null,
                ]);
            }

            // Update salary grades
            $configSet->salaryGrades()->delete();
            foreach ($data['salary_grades'] as $grade) {
                InsuranceSalaryGradeConfig::create([
                    'config_set_id' => $configSet->id,
                    'grade' => $grade['grade'],
                    'name' => $grade['name'],
                    'coefficient' => $grade['coefficient'],
                    'description' => $grade['description'] ?? null,
                    'is_active' => true,
                ]);
            }

            // Activity log
            activity()
                ->performedOn($configSet)
                ->causedBy($request->user())
                ->withProperties([
                    'old' => $oldAttributes,
                    'attributes' => [
                        'code' => $configSet->code,
                        'name' => $configSet->name,
                        'effective_from' => $configSet->effective_from->format('Y-m-d'),
                        'minimum_wages_count' => count($data['minimum_wages']),
                        'salary_grades_count' => count($data['salary_grades']),
                    ]
                ])
                ->log('Cập nhật bộ config bảo hiểm');

            DB::commit();

            return redirect()->route('insurance-config-sets.show', $configSet->id)
                ->with([
                    'message' => 'Cập nhật bộ config bảo hiểm thành công.',
                    'type' => 'success'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with([
                    'message' => 'Lỗi khi cập nhật config set: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    public function destroy($id)
    {
        $configSet = InsuranceConfigSet::findOrFail($id);

        if (!$configSet->isDraft()) {
            return back()->with([
                'message' => 'Chỉ có thể xóa config ở trạng thái DRAFT.',
                'type' => 'error'
            ]);
        }

        // Activity log before delete
        activity()
            ->performedOn($configSet)
            ->causedBy(auth()->user())
            ->withProperties([
                'attributes' => [
                    'code' => $configSet->code,
                    'name' => $configSet->name,
                ]
            ])
            ->log('Xóa bộ config bảo hiểm');

        $configSet->delete();

        return redirect()->route('insurance-config-sets.index')
            ->with([
                'message' => 'Xóa bộ config bảo hiểm thành công.',
                'type' => 'success'
            ]);
    }

    public function activate($id)
    {
        $configSet = InsuranceConfigSet::findOrFail($id);

        $validation = $configSet->validateForActivation();

        if (!$validation['valid']) {
            return back()->with([
                'message' => 'Config chưa hợp lệ: ' . implode(', ', $validation['errors']),
                'type' => 'error'
            ]);
        }

        DB::beginTransaction();

        try {
            // Archive all current ACTIVE configs
            InsuranceConfigSet::where('status', InsuranceConfigSet::STATUS_ACTIVE)
                ->where('id', '!=', $configSet->id)
                ->update([
                    'status' => InsuranceConfigSet::STATUS_ARCHIVED,
                    'archived_at' => now(),
                    'archived_by' => auth()->id(),
                ]);

            $configSet->update([
                'status' => InsuranceConfigSet::STATUS_ACTIVE,
                'activated_at' => now(),
                'activated_by' => auth()->id(),
            ]);

            // Activity log
            activity()
                ->performedOn($configSet)
                ->causedBy(auth()->user())
                ->withProperties([
                    'attributes' => [
                        'code' => $configSet->code,
                        'name' => $configSet->name,
                        'status' => 'ACTIVE',
                    ]
                ])
                ->log('Kích hoạt bộ config bảo hiểm');

            DB::commit();

            return back()->with([
                'message' => 'Kích hoạt bộ config bảo hiểm thành công.',
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with([
                'message' => 'Lỗi khi kích hoạt config: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    public function archive($id)
    {
        $configSet = InsuranceConfigSet::findOrFail($id);

        $configSet->update([
            'status' => InsuranceConfigSet::STATUS_ARCHIVED,
            'archived_at' => now(),
            'archived_by' => auth()->id(),
        ]);

        // Activity log
        activity()
            ->performedOn($configSet)
            ->causedBy(auth()->user())
            ->withProperties([
                'attributes' => [
                    'code' => $configSet->code,
                    'name' => $configSet->name,
                    'status' => 'ARCHIVED',
                ]
            ])
            ->log('Lưu trữ bộ config bảo hiểm');

        return back()->with([
            'message' => 'Lưu trữ bộ config bảo hiểm thành công.',
            'type' => 'success'
        ]);
    }

    public function cloneSet(Request $request, $id)
    {
        $sourceSet = InsuranceConfigSet::with(['minimumWages', 'salaryGrades'])->findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:50|unique:insurance_config_sets,code',
            'name' => 'required|string|max:255',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        DB::beginTransaction();

        try {
            $newSet = InsuranceConfigSet::create([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description ?? "Clone từ {$sourceSet->code}",
                'status' => InsuranceConfigSet::STATUS_DRAFT,
                'effective_from' => $request->effective_from,
                'effective_to' => $request->effective_to,
                'based_on_set_id' => $sourceSet->id,
                'created_by' => auth()->id(),
            ]);

            foreach ($sourceSet->minimumWages as $wage) {
                InsuranceMinimumWageConfig::create([
                    'config_set_id' => $newSet->id,
                    'region' => $wage->region,
                    'amount' => $wage->amount,
                    'note' => $wage->note,
                ]);
            }

            foreach ($sourceSet->salaryGrades as $grade) {
                InsuranceSalaryGradeConfig::create([
                    'config_set_id' => $newSet->id,
                    'grade' => $grade->grade,
                    'name' => $grade->name,
                    'coefficient' => $grade->coefficient,
                    'description' => $grade->description,
                    'is_active' => $grade->is_active,
                ]);
            }

            // Activity log
            activity()
                ->performedOn($newSet)
                ->causedBy($request->user())
                ->withProperties([
                    'attributes' => [
                        'code' => $newSet->code,
                        'name' => $newSet->name,
                        'based_on' => $sourceSet->code,
                        'minimum_wages_count' => $sourceSet->minimumWages->count(),
                        'salary_grades_count' => $sourceSet->salaryGrades->count(),
                    ]
                ])
                ->log('Sao chép bộ config bảo hiểm');

            DB::commit();

            return redirect()->route('insurance-config-sets.show', $newSet->id)
                ->with([
                    'message' => 'Sao chép bộ config bảo hiểm thành công.',
                    'type' => 'success'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with([
                    'message' => 'Lỗi khi sao chép config: ' . $e->getMessage(),
                    'type' => 'error'
                ]);
        }
    }

    public function current()
    {
        $info = $this->resolver->getCurrentConfigInfo();

        if (!$info) {
            return back()->withErrors(['error' => 'Không có config đang active']);
        }

        $configSet = InsuranceConfigSet::findOrFail($info['id']);
        return redirect()->route('insurance-config-sets.show', $configSet->id);
    }
}
