<?php

namespace App\Helpers;

use App\Models\User\User;
use App\Models\User\UserMedia;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class Helper{

    // get age with today date
    public static function getAge($dob){
        $now  = Carbon::now();
        $data=[];
        $data['Years']=$now->diffInYears($dob);
        $data['Months']=$now->diffInMonths($dob);
        $data['Days']=$now->diffInDays($dob);
        return $data;

    }

    // get random number
    public static function randomNumber(){
        $current_time = round(microtime(true) * 1000);
        return $current_time . rand(1111, 9999);
    }

    // decrypt
    public static function decrypt($string = null){

        try {
            return Crypt::decrypt($string);
        }catch (DecryptException $exception){
            return ['status' => false, 'message' => 'page not found'];
        }
    }

    // get file size in GB / MB / KB
    public static function getFileSize($bytes = null){

        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public static function getModuleId($slug){
        return DB::table('modules')->where('slug', str::slug($slug))->value('id');
    }
    public static function getPermissionId($slug){
        return DB::table('permissions')->where('slug', str::slug($slug))->value('id');
    }

    public static function makeUUID($firstName, $lastName, $userId){
        $firstNameInitial = substr($firstName, 0, 1);
        $lastNameInitial = substr($lastName, 0, 1);
        return $firstNameInitial . $lastNameInitial . $userId . str_pad(mt_rand(1, 1000), 5 - strlen($userId), '0', STR_PAD_LEFT);
    }

    public static function makeRequestNumber($userId, $requestId){

        $user = User::select('first_name', 'last_name')->find($userId);
        $firstNameInitial = substr($user->first_name, 0, 1);
        $lastNameInitial = substr($user->last_name, 0, 1);

        return $firstNameInitial . $lastNameInitial . $requestId . str_pad(mt_rand(1, 1000), 5 - strlen($requestId), '0', STR_PAD_LEFT);
    }

    public static function generateStrongPassword($length = 16) {
        // Create a random string
        $randomString = bin2hex(random_bytes($length / 2));
    
        // Ensure the password contains at least one uppercase letter, one lowercase letter, one digit, and one special character
        $password = Str::random($length);
    
        // Add at least one character from each required set if they are not already included
        if (!preg_match('/[A-Z]/', $password)) {
            $password .= chr(rand(65, 90)); // Add uppercase letter
        }
        if (!preg_match('/[a-z]/', $password)) {
            $password .= chr(rand(97, 122)); // Add lowercase letter
        }
        if (!preg_match('/[0-9]/', $password)) {
            $password .= chr(rand(48, 57)); // Add digit
        }
        if (!preg_match('/[\W_]/', $password)) {
            $password .= chr(rand(33, 47)); // Add special character
        }
    
        // Shuffle the password to ensure randomness
        $password = str_shuffle($password);
    
        // Trim the password to the desired length
        return substr($password, 0, $length);
    }

    public static function setEnvValue($key, $value) {

    $path = base_path('.env');

    if (file_exists($path)) {
        $env = file_get_contents($path);

        if (preg_match("/^$key=.*$/m", $env)) {
            $env = preg_replace("/^$key=.*$/m", "$key=\"$value\"", $env);
        } else {
            $env .= "\n$key=\"$value\"";
        }

        file_put_contents($path, $env);

        // Clear and cache the config to apply changes
        Artisan::call('config:clear');
        Artisan::call('config:cache');
    }
}
}
