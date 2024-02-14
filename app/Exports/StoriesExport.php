<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class StoriesExport implements FromView
{
    protected $headers;
    protected $bodies;

    public function __construct($headers, $bodies)
    {
        $this->headers = $headers;
        $this->bodies = $bodies;
    }

    public function view(): View
    {
        return view('exports.storiesexport', [
            'headers' => $this->headers,
            'bodies' => $this->bodies,
        ]);
    }
}
