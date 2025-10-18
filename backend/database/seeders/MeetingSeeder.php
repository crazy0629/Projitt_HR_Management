<?php

namespace Database\Seeders;

use App\Models\VideoCall\Invitation;
use Illuminate\Database\Seeder;
use App\Models\VideoCall\Meeting;
use App\Models\User\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        Invitation::truncate();
        Meeting::truncate();

        Schema::enableForeignKeyConstraints();
        
        $owner = User::where('email', 'super.admin1@example.com')->first();

        Meeting::create([
            'created_by' => $owner->id,
            'title' => 'Kickoff Meeting',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'join_code' => Str::random(12),
            'status' => 'scheduled',
        ]);
    }
}
