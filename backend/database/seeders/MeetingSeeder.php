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
        $owner = User::where('email', 'super.admin1@example.com')->first();

        if (!$owner) {
            return;
        }

        $meeting = Meeting::firstOrCreate(
            ['created_by' => $owner->id, 'title' => 'Kickoff Meeting'],
            [
                'scheduled_at' => now()->addDay(),
                'duration_minutes' => 60,
                'join_code' => Str::random(12),
                'status' => 'scheduled',
            ]
        );

        Invitation::firstOrCreate(
            ['meeting_id' => $meeting->id, 'invitee_email' => 'invitee@example.com'],
            [
                'inviter_id' => $owner->id,
                'status' => 'accepted',
                'token' => Str::random(32),
            ]
        );
    }
}
