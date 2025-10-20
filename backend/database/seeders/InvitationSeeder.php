<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VideoCall\Invitation;
use App\Models\VideoCall\Meeting;
use App\Models\User\User;
use Illuminate\Support\Str;

class InvitationSeeder extends Seeder
{
    public function run(): void
    {
        $meeting = Meeting::first();
        $invitee = User::where('email', 'super.admin2@example.com')->first();
        $owner = User::where('email', 'super.admin1@example.com')->first();

        if (!$meeting || !$invitee || !$owner) {
            return;
        }

        Invitation::firstOrCreate(
            ['meeting_id' => $meeting->id, 'invitee_user_id' => $invitee->id],
            [
                'inviter_id' => $owner->id,
                'status' => 'accepted',
                'token' => Str::random(32),
            ]
        );
    }
}
