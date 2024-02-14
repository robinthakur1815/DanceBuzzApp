<?php

namespace App\Jobs;

use App\File;
use App\Helpers\ImageHelper;
use App\Helpers\StoryHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadStory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $fileModel;
    private $user;
    private $story;
    private $email;
    private $profile;

    public function __construct($fileModel, $user, $story, $profile, $email = null)
    {
        $this->onQueue('story');
        $this->fileModel = $fileModel;
        $this->user = $user;
        $this->story = $story;
        $this->email = $email;
        $this->profile = $profile;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $storyHelper = new StoryHelper();
        // $storyData = $storyHelper->storyModelData($this->story);

        try {
            $this->uploadS3($this->fileModel->uuid);
            if (\Str::contains($this->fileModel->mime_type, 'video')) {
                $this->fileModel->is_processing_video = true;
                $this->fileModel->save();
                ConvertVideoToMP4Job::dispatch($this->fileModel);
            }

            $points = $this->profile->enthu_points;
            $enthu_points = $points + config('app.creative_corner_enthu_point');
            DB::connection('partner_mysql')->table('user_profiles')
                ->where('user_id', $this->user->id)
                ->update(['enthu_points' => $enthu_points]);
          //  \Mail::to($this->email)->send(new \App\Mail\StorySubmission($this->user->id, $this->story->id, true));
        } catch (\Exception $th) {
            info([$th]);
           // \Mail::to($this->email)->send(new \App\Mail\StorySubmission($this->user->id, $this->story->id, false));
        }
    }

    private function uploadS3($path)
    {
        // info('uploading started');
        // $exists = Storage::disk('s3public')->exists($path);
        // if (!$exists) {
        //     throw new \Exception("file not found", 1);
        // }
        // $filePath = storage_path('app/public') . "/" . $path;
        // $fileData = fopen($filePath, 'r+');
        // $fileData = Storage::disk('s3public')->get($path);
        // Storage::disk('s3')->put(
        //     $path,
        //     $fileData
        // );

        // File copying using stream
        $start_memory = memory_get_usage();
        info("Copying file using stream - {$path}");
        Storage::disk('s3')->writeStream($path, Storage::disk('s3public')->readStream($path));

        // $s3 = Storage::disk('s3');
        // $s3public = Storage::disk('s3public');
        // $stream = $s3public->getDriver()->readStream($path);
        // $s3->put($path, $stream);
        $uses = memory_get_usage() - $start_memory;

        info("uploading completed - {$path} - memory {$uses}");
    }
}
