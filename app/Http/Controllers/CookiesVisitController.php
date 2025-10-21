<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCookiesVisitRequest;
use App\Models\CookiesVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CookiesVisitController extends Controller
{
    /**
     * Store a new CookiesVisit record.
     */
    public function add(AddCookiesVisitRequest $request): JsonResponse
    {
        $visit = new CookiesVisit();
        $visit->session_id        = $request->filled('session_id') ? $request->input('session_id') : null;
        $visit->page_url          = $request->filled('page_url') ? $request->input('page_url') : null;
        $visit->page_title        = $request->filled('page_title') ? $request->input('page_title') : null;
        $visit->referrer          = $request->filled('referrer') ? $request->input('referrer') : null;
        $visit->user_agent        = $request->filled('user_agent') ? $request->input('user_agent') : null;
        $visit->screen_resolution = $request->filled('screen_resolution') ? $request->input('screen_resolution') : null;
        $visit->viewport_size     = $request->filled('viewport_size') ? $request->input('viewport_size') : null;
        $visit->language          = $request->filled('language') ? $request->input('language') : null;
        $visit->timezone          = $request->filled('timezone') ? $request->input('timezone') : null;
        $visit->page_type         = $request->filled('page_type') ? $request->input('page_type') : null;
        $visit->is_first_visit    = $request->filled('is_first_visit') ? $request->boolean('is_first_visit') : false;
        $visit->ip_address        = $request->filled('ip_address') ? $request->input('ip_address') : null;
        $visit->save();

        $visit = CookiesVisit::find($visit->id);

        return $this->sendSuccess(config('messages.success'), $visit, 200);
    }

    public function stats(Request $request)
    {
        $v = $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'group_by'   => 'nullable|in:day,week,month',
        ]);

        $groupBy = $v['group_by'] ?? 'day';
        $start   = isset($v['start_date']) ? $v['start_date'].' 00:00:00' : now()->subDays(29)->startOfDay();
        $end     = isset($v['end_date'])   ? $v['end_date'].' 23:59:59'   : now()->endOfDay();

        // Your actual table/columns
        $table      = 'cookies_visits';
        $sessionCol = 'session_id';
        $pageUrlCol = 'page_url';

        // Consent columns are optional. We'll detect and compute only if available.
        $hasConsent = Schema::hasColumn($table, 'consent_status');
        $consentCol = 'consent_status'; // expected values like 'accepted'/'rejected'

        // Grouping label/expression
        $bucketExpr = match ($groupBy) {
            'week'  => "DATE_FORMAT(created_at, '%x-W%v')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };
        $labelKey = $groupBy === 'day' ? 'date' : ($groupBy === 'week' ? 'week' : 'month');

        $base = DB::table($table)->whereBetween('created_at', [$start, $end]);

        // ---- Totals (computed, not stored) ----
        $totalsQuery = (clone $base)
            ->selectRaw('COUNT(*) as total_visits')
            ->selectRaw("COUNT(DISTINCT {$sessionCol}) as unique_sessions");

        if ($hasConsent) {
            $totalsQuery
                ->selectRaw("SUM({$consentCol} = 'accepted') as consent_accepted")
                ->selectRaw("SUM({$consentCol} = 'rejected') as consent_rejected");
        }

        $totals = $totalsQuery->first();

        // ---- Bucketed stats (day/week/month) ----
        $bucketsQuery = (clone $base)
            ->selectRaw("$bucketExpr as bucket")
            ->selectRaw('COUNT(*) as visits')
            ->selectRaw("COUNT(DISTINCT {$sessionCol}) as unique_sessions")
            ->groupBy('bucket')
            ->orderBy('bucket');

        if ($hasConsent) {
            $bucketsQuery
                ->selectRaw("SUM({$consentCol} = 'accepted') as accepted_cnt")
                ->selectRaw("SUM({$consentCol} = 'rejected') as rejected_cnt");
        } else {
            // Provide zeros to keep mapping logic simple
            $bucketsQuery
                ->selectRaw('0 as accepted_cnt')
                ->selectRaw('0 as rejected_cnt');
        }

        $buckets = $bucketsQuery->get()->map(function ($r) use ($labelKey) {
            $decided = (int)$r->accepted_cnt + (int)$r->rejected_cnt;
            return [
                $labelKey         => $r->bucket,
                'visits'          => (int)$r->visits,
                'unique_sessions' => (int)$r->unique_sessions,
                'consent_rate'    => $decided ? ((int)$r->accepted_cnt / $decided) : null,
            ];
        })->values();

        // ---- Top pages (computed, not stored) ----
        $topPages = (clone $base)
            ->whereNotNull($pageUrlCol)
            ->selectRaw("$pageUrlCol as page_url")
            ->selectRaw('COUNT(*) as visits')
            ->selectRaw("COUNT(DISTINCT {$sessionCol}) as unique_visits")
            ->groupBy($pageUrlCol)
            ->orderByDesc('visits')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'page_url'      => $r->page_url,
                'visits'        => (int)$r->visits,
                'unique_visits' => (int)$r->unique_visits,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_visits'     => (int)($totals->total_visits ?? 0),
                'unique_sessions'  => (int)($totals->unique_sessions ?? 0),
                'consent_accepted' => (int)($totals->consent_accepted ?? 0),
                'consent_rejected' => (int)($totals->consent_rejected ?? 0),
                'daily_stats'      => $buckets,
                'top_pages'        => $topPages,
            ],
        ]);
    }

}
