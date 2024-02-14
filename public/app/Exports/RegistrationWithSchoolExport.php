<?php


namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Helpers\Reports\RegistrationReport;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RegistrationWithSchoolExport implements FromCollection, WithHeadings
{
    private $schoolId, $schoolPartnerId, $campaignId, $startDate, $endDate ;
    public function __construct($request)
    {
        $this->schoolId = $request->school_id;
        $this->schoolPartnerId = $request->schoolPartner_id;
        $this->campaignId = $request->campaign_id; 
        $this->startDate = $request->start_date;
        $this->endDate = $request->end_date;
    }

    public function collection()
    {
        return RegistrationReport::getSchoolRegistration($this->schoolId, $this->schoolPartnerId, $this->campaignId, $this->startDate, $this->endDate);
    }

    public function headings(): array
    {
        return [
            'User Id',
            'Name',
            'Address',
            'Created At',
            'Email',
            'Phone',
            'Grade',
            'Section',
            'School',
            'City',
            'Campaign',
            'Media',
            'Status',
            'Submitted On',
            'Device'
        ];
    }
}
