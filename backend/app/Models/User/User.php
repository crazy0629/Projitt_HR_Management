<?php

namespace App\Models\User;

use App\Helpers\Helper;
use App\Models\Role\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

class User extends Authenticatable implements HasApiTokensContract
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = ['uuid', 'first_name', 'middle_name', 'last_name', 'email', 'department', 'password', 'role_id'];

    protected $hidden = ['password', 'remember_token'];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = Helper::makeUUID($model->firstName, $model->lastName, $model->id);
        });
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function createToken(string $name, array $abilities = ['*'], $ip = null, $agent = null, $userId = null)
    {

        $plainTextToken = Str::random(40);
        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => json_encode($abilities),
            'actual_token' => $plainTextToken,
            'ip' => $ip,
            'agent' => $agent,
            'user_id' => $userId,
            'expires_at' => now()->addDays(30),
        ]);

        return new NewAccessToken($token, $plainTextToken);
    }

    public function createRefreshToken(string $name = 'refresh', array $abilities = ['*'], $ip = null, $agent = null, $userId = null)
    {
        $plainRefreshToken = Str::random(64);

        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainRefreshToken),
            'abilities' => json_encode($abilities),
            'actual_token' => $plainRefreshToken,
            'ip' => $ip,
            'agent' => $agent,
            'user_id' => $userId,
            'expires_at' => now()->addDays(30),
        ]);

        return new NewAccessToken($token, $plainRefreshToken);
    }

    public static function filterData($request)
    {

        $filteredData = self::with(['role'])->select('id', 'first_name', 'middle_name', 'last_name', 'email', 'role_id');

        if (! empty($request->input('full_name'))) {
            $filteredData = $filteredData->where(function ($query) use ($request) {
                $fullName = $request->input('full_name');
                $query->where('first_name', 'LIKE', '%'.$fullName.'%')
                    ->orWhere('middle_name', 'LIKE', '%'.$fullName.'%')
                    ->orWhere('last_name', 'LIKE', '%'.$fullName.'%');
            });
        }

        if (! empty($request->input('email'))) {
            $filteredData = $filteredData->Where('email', 'LIKE', '%'.$request->input('email').'%');
        }

        if (! empty($request->input('uuid'))) {
            $filteredData = $filteredData->Where('uuid', $request->input('uuid'));
        }

        return $filteredData;
    }
}
