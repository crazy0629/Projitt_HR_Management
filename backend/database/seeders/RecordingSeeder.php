<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VideoCall\Recording;
use App\Models\VideoCall\Meeting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecordingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $meeting = Meeting::query()->first();

            if (!$meeting) {
                return;
            }

            $filename = 'recordings/meeting-'.$meeting->id.'-seed.txt';
            Storage::disk('local')->put($filename, "This is a seeded mock recording for meeting {$meeting->id}.");

            Recording::updateOrCreate(
                ['meeting_id' => $meeting->id, 'file_path' => $filename],
                [
                    'started_at' => now()->subMinutes(30),
                    'ended_at' => now()->subMinutes(25),
                    'participants' => ['owner@example.com', 'invitee@example.com'],
                ]
            );
        });
    }
}
