<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'is_folder', 'parent_document_id', 'path', 'document_type',
         'mime_type', 'extension', 'file_name',
         'size', 'vendor_id', 'isActive', 'created_by', 'updated_by',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        //
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

    /**
     * Get the Vendor for the Document.
     */
    public function vendor()
    {
        return $this->belongsTo(\App\Vendor::class, 'vendor_id');
    }

    /**
     * Get the documents for the Document.
     */
    public function documents()
    {
        return $this->hasMany('App\Model\Document', 'parent_document_id', 'id');
    }
}
