<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class FileType extends Enum
{
    const Image = 'image';
    const Video = 'video';
    const Gif = 'gif';
    const Pdf = 'pdf';
    const Unknown = 'unknown';
    const Csv = 'csv';
    const SpreadSheet = 'xml';
    const Excel = 'xlsx';
    const ELS = 'xls';
    const Xps = 'xps';
    const Exe = 'exe';
    const Document = 'docx';
    const Ppt = 'pptx';
    const Audio = 'Audio';
    // const Excel ='sheet';
}
