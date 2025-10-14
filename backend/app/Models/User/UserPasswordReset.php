<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class UserPasswordReset extends Model {
    
    use HasFactory, SoftDeletes;

    protected $table = 'password_resets';

    protected $fillable = [ 'email', 'type_id', 'status', 'token', 'created_at', 'updated_at', 'deleted_at'];

    public static function createResetPasswordToken($email, $typeId = 1) {  // 1 by user

        $token = Str::random(10);
        $newObject = new UserPasswordReset();
        $newObject->email = $email;
        $newObject->type_id = $typeId;
        $newObject->token = $token;
        $newObject->created_at = now();
        $newObject->save();
        
        return $token;
    }
}
