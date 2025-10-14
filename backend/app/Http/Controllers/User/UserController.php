<?php

namespace App\Http\Controllers\User;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\RegisterUserRequest;
use App\Http\Requests\User\LoginRequest;
use App\Http\Requests\User\ForgotPasswordRequest;
use App\Http\Requests\User\ListWithFilterRole;
use App\Http\Requests\User\ResetPasswordRequest;
use App\Http\Requests\User\SendApplicantOTPRequest;
use App\Http\Requests\User\UserListWithFilterRequest;
use App\Http\Requests\User\VerifyApplicantOTP;
use Illuminate\Http\JsonResponse;
use App\Mail\User\ResetPasswordLink;
use App\Mail\User\UserRegistration;
use App\Models\Role\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User\User;
use App\Models\User\UserAuthToken;
use App\Models\User\UserPasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function register(RegisterUserRequest $request): JsonResponse {

        DB::beginTransaction();

        try {

            $newUser = User::create([
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name', null),
                'last_name' => $request->input('last_name', null),
                'email' => $request->input('email', null),
                'password' => bcrypt($request->input('password')),
                'role_id' =>  $request->input('role_id')
            ]);

            $token = $newUser->createToken(
                'hr',
                ['*'],
                $request->ip(),
                $request->userAgent(),
                $newUser->id
            )->plainTextToken;

            $uuid = Helper::makeUUID($request->input('first_name'), $request->input('last_name'), $newUser->id);
            $newUser->update(['uuid' => $uuid]);

            DB::commit();

            $newUser->token = $token;
            Mail::to($request->input('email'))->send(new UserRegistration($newUser));

            return $this->sendSuccess($newUser, config('messages.success'));

        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
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
    
            return $this->sendSuccess($user, config('messages.success'));
        }
    
        return $this->sendValidation('Invalid credentials');
    }



    public function logout(Request $request): JsonResponse {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->sendSuccess(null, config('messages.success'));
        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }


    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse {

        try {
            $token = UserPasswordReset::createResetPasswordToken($request->input('email', $request->input('type_id')));

            Mail::to($request->input('email'))->send(new ResetPasswordLink($token));

            return $this->sendSuccess(null, config('messages.success'));
        } catch (\Exception $exception) {
            return $this->sendError(config('messages.error'), $exception->getMessage());
        }
    }



    public function validateResetPasswordToken($token) {

        $resetRecord = UserPasswordReset::where([
            'status' => null, 
            'token' => $token
        ])->orderBy('created_at', 'desc')->first();
    
        if (!$resetRecord) {
            return redirect()->route('password.reset.form', ['token' => $token])
                             ->with('error', config('messages.invalid_reset_link'));
        }
    
        if (now()->diffInMinutes($resetRecord->created_at) > config('auth.passwords.users.expire')) {
            UserPasswordReset::where(['token' => $token])
                             ->update(['status' => 2, 'deleted_at' => now()]); // 2 = expired
            return redirect()->route('password.reset.form', ['token' => $token])
                             ->with('error', config('messages.expired_reset_link'));
        }
    
        return view('emails.auth.update-password', [
            'token' => $token,
            'email' => $resetRecord->email,
            'errors' => session()->get('errors') // Explicitly pass errors to the view
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request) {

        DB::beginTransaction();
        try {
            $resetRecord = UserPasswordReset::where([
                'status' => null,
                'token' => $request->input('token')
            ])->orderBy('created_at', 'desc')->first();
    
            if (!$resetRecord) {
                return redirect()->back()->with('error', config('messages.invalid_reset_link'));
            }
    
            if (now()->diffInMinutes($resetRecord->created_at) > config('auth.passwords.users.expire')) {
                UserPasswordReset::where(['token' => $request->input('token')])
                                 ->update(['status' => 2, 'deleted_at' => now()]);
                return redirect()->back()->with('error', config('messages.expired_reset_link'));
            }
    
            User::where('email', $request->input('email'))
                ->update(['password' => bcrypt($request->input('password'))]);
    
            UserPasswordReset::where([
                'email' => $request->input('email'), 
                'token' => $request->input('token')
            ])->update(['status' => 1, 'deleted_at' => now()]);
    
            DB::commit();
    
            return redirect(env('APP_URL'))->with('success', 'Password reset successfully.');
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('error', $exception->getMessage());
        }
    }

    public function listAllWithFilters(UserListWithFilterRequest $request): JsonResponse {

        $query = User::filterData($request);
        $result = $this->getData($query, $request->input('pagination'), $request->input('per_page'), $request->input('page'));

        return $this->sendSuccess($result, config('messages.success'));
    }

    public function listAllWithFiltersRole(ListWithFilterRole $request): JsonResponse {

        $addressTypeObject = Role::filterData($request);
        $addressTypeObject = $this->getData($addressTypeObject, $request->input('pagination'), $request->input('per_page'), $request->input('page'));
        return $this->sendSuccess($addressTypeObject, config('messages.success'));

    }


    public function refreshToken(Request $request): JsonResponse {

        $refreshToken = $request->header('refresh-token');

        if (!$refreshToken) {
            return $this->sendValidation('Refresh token is required.');
        }

        $hashed = hash('sha256', $refreshToken);

        $tokenRecord = UserAuthToken::where('token', $hashed)->first();

        if (!$tokenRecord || $tokenRecord->expires_at < now()) {
            return $this->sendValidation('Invalid or expired refresh token.');
        }

        $user = User::find($tokenRecord->user_id);

        if (!$user) {
            return $this->sendValidation('User not found.');
        }

        $tokenRecord->delete();

        $access = $user->createToken('access', ['*'], $request->ip(), $request->userAgent(), $user->id);
        $refresh = $user->createRefreshToken('refresh', ['*'], $request->ip(), $request->userAgent(), $user->id);

        return $this->sendSuccess([
            'access_token' => $access->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
        ], 'Token refreshed successfully.');

    }


    public function sendApplicantOtp(SendApplicantOTPRequest $request): JsonResponse {

        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => '', 'password' => '', 'created_at' => now()]
        );
    
        // Generate OTP and store
        $otp = rand(100000, 999999);
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();
    
        // Send OTP via mail
        Mail::raw("Your OTP code is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Your Applicant OTP Code');
        });
    
        return $this->sendSuccess(null, config('messages.success'));
    }


    public function verifyApplicantOtp(VerifyApplicantOTP $request): JsonResponse {

        $user = User::where('email', $request->email)
                    ->where('otp', $request->otp)
                    ->where('otp_expires_at', '>', now())
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 401);
        }

        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();
        // $token = $user->createToken('applicant-onboarding')->plainTextToken;

        $token = $user->createToken(
            'applicant-onboarding',
           ['*'],
           $request->ip(),
           $request->userAgent(),
           $user->id
       )->plainTextToken;
       $user->applicant_token = $token;
        return response()->json([
            'message' => 'OTP verified. Login successful.',
            'token' => $user,
        ]);

    }

    public function applicantsss(): JsonResponse {
        dd('sss');
        
    }

    
    
}
