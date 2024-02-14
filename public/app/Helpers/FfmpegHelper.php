<?php

namespace  App\Helpers;

use FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;

class FfmpegHelper extends Facade
{
    public static function convertVideoToMp4_2($s3Path, $disk = 's3')
    {
        $pathInfo = pathinfo($s3Path);
        if (strcasecmp($pathInfo['extension'], 'mp4') == 0) {
            return $s3Path;
        }
        $outputFilePath = "{$pathInfo['filename']}.mp4";
        // print $outputFilePath;

        // create a video format...
        $lowBitrateFormat = (new X264)->setAudioCodec('libmp3lame')->setKiloBitrate(500);

        FFMpeg::fromDisk($disk)
        ->open($s3Path)
        ->export()
        ->toDisk($disk)
        ->inFormat($lowBitrateFormat)
        ->save($outputFilePath);

        return $outputFilePath;
    }

    public static function convertVideoToMp4($s3Path, $disk = 's3')
    {
        $pathInfo = pathinfo($s3Path);
        //$ext = $pathInfo['extension'];
        if (strcasecmp($pathInfo['extension'], 'mp4') == 0) {
            return $s3Path;
        }


        $outputFilePath = "{$pathInfo['dirname']}/{$pathInfo['filename']}.mp4";
        $tmpPath = "tmp/{$s3Path}";
        $tmpOutPath = "tmp/{$outputFilePath}";
        $physicalPath = storage_path("app/public/{$tmpPath}");
        $physicalOutPath = storage_path("app/public/{$tmpOutPath}");
        if (Storage::disk('public')->exists($tmpPath)) {
            Storage::disk('public')->delete($tmpPath);
        }

        Storage::disk('public')->writeStream($tmpPath, Storage::disk($disk)->readStream($s3Path));
        $ffmpegBin = env('FFMPEG_BINARIES', '/usr/local/bin/ffmpeg');
        if (strcasecmp($pathInfo['extension'], 'webm') == 0) {
            $output = shell_exec("{$ffmpegBin} -y -fflags +genpts  -i {$physicalPath} -r 24 {$physicalOutPath}");
        } else {
            $output = shell_exec("{$ffmpegBin} -y -i {$physicalPath} -codec copy {$physicalOutPath}");
        }
        if (Storage::disk($disk)->exists($outputFilePath)) {
            Storage::disk($disk)->delete($outputFilePath);
        }
        info([$outputFilePath, $tmpOutPath, $tmpPath, $physicalOutPath, $physicalPath, $disk]);

        $stream = Storage::disk('public')->readStream($tmpOutPath);
        Storage::disk($disk)->writeStream($outputFilePath, $stream);
        if (Storage::disk('public')->exists($tmpPath)) {
            unlink($physicalPath);
        }

        if (Storage::disk('public')->exists($tmpOutPath)) {
            unlink($physicalOutPath);
        }
        return $outputFilePath;
    }

    public static function convertVideoToStreamingVideo($s3Path, $disk = 's3')
    {
        $pathInfo = pathinfo($s3Path);
        $outputFilePath = "{$pathInfo['filename']}.m3u8";

        // create some video formats...
        $lowBitrateFormat = (new X264)->setKiloBitrate(500);
        $midBitrateFormat = (new X264)->setKiloBitrate(1500);
        $highBitrateFormat = (new X264)->setKiloBitrate(3000);

        FFMpeg::fromDisk($disk)
        ->open($s3Path)
        ->exportForHLS()
        ->toDisk($disk)
        ->addFormat($lowBitrateFormat)
        ->addFormat($midBitrateFormat)
        ->addFormat($highBitrateFormat)
        ->save($outputFilePath);

        return $outputFilePath;
    }
}
