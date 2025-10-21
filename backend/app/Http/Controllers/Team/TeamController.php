<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\AddTeamRequest;
use App\Http\Requests\Team\DeleteTeamRequest;
use App\Http\Requests\Team\EditTeamRequest;
use App\Http\Requests\Team\ListWithFiltersTeamRequest;
use App\Http\Requests\Team\MergeTeamsRequest;
use App\Models\Team\Team;
use App\Models\Team\TeamUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller {

    public function add(AddTeamRequest $request): JsonResponse {

        DB::beginTransaction();
        try {
            // create team
            $team = new Team();
            $team->name        = $request->input('name');
            $team->description = $request->filled('description') ? $request->input('description') : null;
            $team->created_by  = Auth::id();
            $team->save();

            // attach users into pivot table
            if ($request->filled('user_ids')) {
                foreach ($request->input('user_ids') as $userId) {
                    TeamUser::create([
                        'team_id' => $team->id,
                        'user_id' => $userId,
                    ]);
                }
            }

            DB::commit();

            // reload with users info using model helper
            $team = Team::getSingleTeam($team->id);

            return $this->sendSuccess($team, config('messages.success'), 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError('Unable to create team: ' . $e->getMessage(), [], 500);
        }
    }


    public function edit(EditTeamRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $team = Team::whereNull('deleted_at')->findOrFail($request->input('id'));

            $team->name        = $request->filled('name') ? $request->input('name') : $team->name;
            $team->description = $request->filled('description') ? $request->input('description') : $team->description;
            $team->updated_by  = Auth::id();
            $team->save();

            // Sync team members if provided
            if ($request->has('user_ids')) {
                // Remove old assignments
                TeamUser::where('team_id', $team->id)->delete();

                // Add new ones
                foreach ($request->input('user_ids') as $userId) {
                    TeamUser::create([
                        'team_id' => $team->id,
                        'user_id' => $userId,
                    ]);
                }
            }

            DB::commit();

            $team = Team::getSingleTeam($team->id);

            return $this->sendSuccess($team, config('messages.success'), 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError('Unable to update team: ' . $e->getMessage(), [], 500);
        }
    }


    public function single(int $id): JsonResponse
    {
        try {
            $team = Team::getSingleTeam($id);

            if (!$team) {
                return $this->sendError('Team not found.', [], 404);
            }

            return $this->sendSuccess($team, config('messages.success'), 200);
        } catch (\Throwable $e) {
            return $this->sendError('Unable to fetch team: ' . $e->getMessage(), [], 500);
        }
    }


    public function delete(DeleteTeamRequest $request): JsonResponse
    {
        try {
            $ids = $request->input('team_ids', []);

            // Soft delete teams
            $affected = Team::whereNull('deleted_at')
                ->whereIn('id', $ids)
                ->update([
                    'deleted_by' => Auth::id(),
                    'deleted_at' => now(),
                ]);

            // Optional: clear pivot memberships so no active links remain
            if (!empty($ids)) {
                TeamUser::whereIn('team_id', $ids)->delete();
            }

            return $this->sendSuccess(
                ['affected' => $affected, 'team_ids' => $ids],
                config('messages.success'),
                200
            );
        } catch (\Throwable $e) {
            return $this->sendError('Unable to delete teams: ' . $e->getMessage(), [], 500);
        }
    }


    public function merge(MergeTeamsRequest $request): JsonResponse
    {
        $teamIds  = $request->input('team_ids'); // exactly 2 IDs (validated)
        $newName  = $request->input('new_name');

        DB::beginTransaction();
        try {
            // Fetch source teams (non-deleted)
            $sourceTeams = Team::whereNull('deleted_at')
                ->whereIn('id', $teamIds)
                ->get(['id', 'name', 'description']);

            if ($sourceTeams->count() !== 2) {
                return $this->sendError('Exactly two active teams must be provided.', [], 422);
            }

            // Create destination (merged) team
            $merged = new Team();
            $merged->name        = $newName;
            // (Optional) choose a description; here we take the first team’s description if any
            $merged->description = $sourceTeams->first()->description ?? null;
            $merged->created_by  = Auth::id();
            $merged->save();

            // Collect distinct users from both teams
            $userIds = TeamUser::whereIn('team_id', $teamIds)
                ->pluck('user_id')
                ->unique()
                ->values();

            // Attach all members to the new team (deduplicated)
            if ($userIds->isNotEmpty()) {
                $rows = $userIds->map(fn ($uid) => [
                    'team_id'    => $merged->id,
                    'user_id'    => $uid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

                // Bulk insert
                TeamUser::insert($rows);
            }

            // Soft-delete the source teams (and clear their pivot rows)
            Team::whereIn('id', $teamIds)->update([
                'deleted_by' => Auth::id(),
                'deleted_at' => now(),
            ]);
            TeamUser::whereIn('team_id', $teamIds)->delete();

            DB::commit();

            // Return the newly merged team with members
            $data = Team::getSingleTeam($merged->id);

            return $this->sendSuccess($data, config('messages.success'), 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError('Unable to merge teams: ' . $e->getMessage(), [], 500);
        }
    }


    public function listAllWithFilters(ListWithFiltersTeamRequest $request): JsonResponse
    {
        try {
            $object = Team::filterData($request);
            $object = getData(
                $object,
                $request->input('pagination'),
                $request->input('per_page'),
                $request->input('page')
            );

            return $this->sendSuccess($object, config('messages.success'), 200);
        } catch (\Throwable $e) {
            return $this->sendError('Unable to fetch teams: ' . $e->getMessage(), [], 500);
        }
    }

}
