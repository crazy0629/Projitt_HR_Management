<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\AddNewEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeePassword;
use App\Http\Requests\Employee\UpdateStepFourRequest;
use App\Http\Requests\Employee\UpdateStepThreeRequest;
use App\Http\Requests\Employee\UpdateStepTwoRequest;
use App\Http\Requests\User\LoginRequest;
use App\Models\Employee\Employee;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller {
    
    public function add(AddNewEmployeeRequest $request): JsonResponse
    {
        $data = $request->validated();
    
        try {

            $employee = DB::transaction(function () use ($request, $data) {

                $user = new User();
                $user->first_name = $data['first_name'];
                $user->last_name  = $data['last_name'];
                $user->email      = $data['email'];
                $user->password   = Hash::make(Str::random(32));
                $user->save();

                $object = new Employee();
                $object->user_id = $user->id;
                $object->employee_type = $request->filled('employee_type') ? $request->input('employee_type') : null;
                $object->country_id    = $request->filled('country_id') ? $request->input('country_id') : null;
                $object->created_by = Auth::id();
    
                $object->save();
    
                return $object; 
            });
    
            $employee = Employee::with(['country', 'department', 'manager', 'role'])->find($employee->id);
    
            return $this->sendSuccess(config('messages.success'), $employee, 200);
        } catch (\Throwable $e) {
            return $this->sendError('Unable to create employee: '.$e->getMessage(), [], 500);
        }
    }



    public function updateStep2(UpdateStepTwoRequest $request): JsonResponse
    {
        $data = $request->validated();
    
        try {
            // Find target employee (not soft-deleted)
            $employee = Employee::whereNull('deleted_at')->findOrFail($data['employee_id']);
    
            // Update Step-2 fields
            $employee->alice_work_id        = (int) $data['alice_work_id'];
            $employee->department_id        = (int) $data['department_id'];
            $employee->job_title_id         = (int) $data['job_title_id'];
            $employee->manager_id           = $request->filled('manager_id') ? (int) $data['manager_id'] : null;
            $employee->contract_start_date  = Carbon::parse($data['contract_start_date'])->format('Y-m-d');
            $employee->updated_by           = Auth::id();
            $employee->save();
    
            // Return with common relations (adjust if you have others, e.g., jobTitle/workLocation)
            $employee->load(['country', 'department', 'manager', 'role']);
    
            return $this->sendSuccess(config('messages.success'), $employee, 200);
        } catch (\Throwable $e) {
            return $this->sendError('Unable to update employee: '.$e->getMessage(), [], 500);
        }
    }


    public function updateStep3(UpdateStepThreeRequest $request): JsonResponse
    {
        $data = $request->validated();
    
        try {
            $employee = Employee::whereNull('deleted_at')->findOrFail($data['employee_id']);
    
            $employee->earning_structure = $data['earning_structure'];
            // store as two-decimal string (works well with DECIMAL(…,2))
            $employee->rate = number_format((float) $data['rate'], 2, '.', '');
    
            $employee->updated_by = Auth::id();
            $employee->save();
    
            $employee->load(['country', 'department', 'manager', 'role']);
    
            return $this->sendSuccess(config('messages.success'), $employee, 200);
        } catch (\Throwable $e) {
            return $this->sendError('Unable to update employee: ' . $e->getMessage(), [], 500);
        }
    }


    public function updateStep4(UpdateStepFourRequest $request): JsonResponse
    {
        $data = $request->validated();
    
        try {
            // Target employee (not soft-deleted)
            $employee = Employee::whereNull('deleted_at')->findOrFail($data['employee_id']);
    
            // Onboarding checklist ids (only if provided)
            if (array_key_exists('onbaording_checklist_ids', $data)) {
                // Ensure ints & de-duplicate (request already has distinct, this is extra safety)
                $employee->onbaording_checklist_ids = collect($data['onbaording_checklist_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            }
    
            // Training / learning path (nullable; update only if present in payload)
            if ($request->has('training_learnging_path')) {
                $employee->training_learnging_path = $data['training_learnging_path'];
            }
    
            // Benefits ids (only if provided)
            if (array_key_exists('benifit_ids', $data)) {
                $employee->benifit_ids = collect($data['benifit_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            }
    
            $employee->updated_by = Auth::id();
            $employee->save();
    
            // Load common relations
            $employee->load(['country', 'department', 'manager', 'role']);
    
            return $this->sendSuccess(config('messages.success'), $employee, 200);
        } catch (\Throwable $e) {
            return $this->sendError('Unable to update employee: ' . $e->getMessage(), [], 500);
        }
    }


    public function updatePassword(UpdateEmployeePassword $request): JsonResponse {

        $user = User::where('email', $request->email)
            ->whereNull('deleted_at')
            ->first();
    
        if (!$user) {
            $user = User::create([
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);
    
            return $this->sendSuccess($user, 'User created and password set successfully.');
        }
    
        $user->password = Hash::make($request->password);
        $user->save();
    
        return $this->sendSuccess($user, 'Password updated successfully.');

    }



    public function login(LoginRequest $request): JsonResponse {

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
    
            $token = $user->createToken(
                 'hr',
                ['*'],
                $request->ip(),
                $request->userAgent(),
                $user->id
            )->plainTextToken;
    
            $user->token = $token;

            $user->manager = null;
            return $this->sendSuccess($user, config('messages.success'));
        }
    
        return $this->sendValidation('Invalid credentials');
    }
    
    
}
