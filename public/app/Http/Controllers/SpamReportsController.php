<?php

namespace App\Http\Controllers;

use App\Enums\SpamReportStatus;
use App\Http\Resources\SpamReportCollection;
use App\SpamReport;
use Illuminate\Http\Request;

class SpamReportsController extends Controller
{
    // Spam Reports Listing
    public function getSpamListing(Request $request)
    {
        $spams = SpamReport::with('comment', 'createdBy', 'updatedBy')->latest();

        if ($request->isTrashed) {
            $spams = $spams->onlyTrashed();
        }

        if ($request->spamStatus) {
            $spams = $spams->where('status', $request->spamStatus);
        }

        if ($request->maxRows) {
            $spams = $spams->paginate($request->maxRows);
        } else {
            $spams = $spams->get();
        }

        return new SpamReportCollection($spams);
    }

    // Soft delete spam report
    public function deleteSpamReport(Request $req)
    {
        $user = auth()->user();
        $spamReports = SpamReport::whereIn('id', $req->reportIds)->get();
        foreach ($spamReports as $spamReport) {
            if (! $spamReport) {
                return response(['errors' => 'report not Found', 'status' => false, 'message' => ''], 422);
            }

            //deleting spam report as invalid report.
            $spamReport->delete();
        }

        return response(['status' => true, 'message' => 'spam report deleted successfully'], 200);
    }

    //delete reported comment
    public function approveSpamReport(Request $req)
    {
        $user = auth()->user();
        $spamReports = SpamReport::with('comment')->whereIn('id', $req->reportIds)->get();
        foreach ($spamReports as $spamReport) {
            if (! $spamReport || ($spamReport && ! $spamReport->comment)) {
                return response(['errors' => 'comment not Found', 'status' => false, 'message' => ''], 422);
            }
            $comment = $spamReport->comment;
            // Comment marked as inactive.
            $comment->update(['is_active' => false, 'updated_by' => $user->id]);

            //on deleting comment or reportable, report is approved.
            $spamReport->update(['status' => SpamReportStatus::Approved, 'updated_by' => $user->id]);
        }

        return response(['status' => true, 'message' => 'comments deleted successfully'], 200);
    }
}
