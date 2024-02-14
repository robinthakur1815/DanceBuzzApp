<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class WebPageSection extends Model
{
    protected $table = 'web_page_sections';
    protected $fillable = [
        'web_page_id', 'web_section_id', 'sequence',
    ];

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function section()
    {
        return $this->belongsTo(\App\WebSection::class);
    }
}
