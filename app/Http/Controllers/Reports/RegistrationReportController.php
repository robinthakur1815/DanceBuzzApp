<?php

namespace App\Http\Controllers\Reports;

use App\Exports\RegistrationWithSchoolExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class RegistrationReportController extends Controller
{
    public function downloadReport(Request $request)
    {
        
        return Excel::download(new RegistrationWithSchoolExport($request), 'registrations.csv');
    }
}
