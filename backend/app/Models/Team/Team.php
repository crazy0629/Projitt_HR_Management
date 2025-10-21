<?php

namespace App\Models\Team;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use SoftDeletes;

    protected $table = 'teams';

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            if (empty($team->tuid)) {
                $team->tuid = (string) Str::uuid();
            }

            if (empty($team->slug)) {
                $base = Str::slug($team->name) ?: 'team';
                $slug = $base . '-' . substr((string) Str::uuid(), 0, 8);

                // ensure uniqueness (including soft-deleted)
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . Str::lower(Str::random(6));
                }
                $team->slug = $slug;
            }
        });
    }

    /**
     * Team members (users) via pivot table team_users.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'team_users', 'team_id', 'user_id')
            ->select('users.id', 'users.first_name', 'users.middle_name', 'users.last_name');
    }

    /**
     * Get a single team with members (selected fields only).
     *
     * @return \App\Models\Team\Team|null
     */
    public static function getSingleTeam(int $id)
    {
        $team = static::query()
            ->select('id', 'name', 'tuid', 'slug', 'description', 'created_at', 'updated_at')
            ->whereNull('deleted_at')
            ->find($id);

        if (!$team) {
            return null;
        }

        $team->load(['users' => function ($q) {
            $q->select('users.id', 'users.first_name', 'users.middle_name', 'users.last_name');
        }]);

        $team->users->makeHidden(['pivot']);

        return $team;
    }


    public static function filterData($request) {

        $filteredData = self::select('id', 'name', 'tuid', 'slug', 'description')
            ->whereNull('deleted_at');

        if (!empty($request->input('name'))) {
            $filteredData->where('name', 'LIKE', '%' . $request->input('name') . '%');
        }

        if (!empty($request->input('created_by'))) {
            $filteredData->where('created_by', $request->input('created_by'));
        }

        if (!empty($request->input('user_id'))) {
            $filteredData->whereHas('users', function ($q) use ($request) {
                $q->where('users.id', $request->input('user_id'));
            });
        }

        return $filteredData;
    }
    

}
