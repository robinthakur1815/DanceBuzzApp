<?php

namespace App\Jobs;

use App\Helpers\FfmpegHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\File as FileModel;
class ConvertVideoToMP4Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fileModel;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(FileModel $fileModel)
    {
        $this->onQueue('video_conversion');
        $this->fileModel = $fileModel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $conversionId = \Str::random(10);
        info("Converting file with conversion Id {$conversionId} : {$this->fileModel->uuid}");
        $outputFilePath = FfmpegHelper::convertVideoToMp4($this->fileModel->uuid);
        $this->fileModel->uuid_before_conversion = $this->fileModel->uuid;
        $this->fileModel->uuid = $outputFilePath;
        $this->fileModel->mime_type = 'video/mp4';
        $this->fileModel->video_converted = true;
        $this->fileModel->is_processing_video = false;
        $this->fileModel->save();
        info("File converstion completed for conversion Id {$conversionId} : {$this->fileModel->uuid}");
    }
}
