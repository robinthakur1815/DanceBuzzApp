<?php

namespace App\Jobs;

use App\Helpers\ImageHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImageThumbnails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $imagePath;
    protected $basePath;

    public function __construct($imagePath, $basePath)
    {
        $this->onQueue('thumbnail');
        $this->imagePath = $imagePath;
        $this->basePath = $basePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $basePath = $this->basePath;
        $imagePath = $this->imagePath;

        $imageHelper = new ImageHelper();
        // $pic1 = $imageHelper->resize($basePath, $imagePath, 1224, 450);
        // $pic2 = $imageHelper->resize($basePath, $imagePath, 808, 404);
        // $pic3 = $imageHelper->resize($basePath, $imagePath, 392, 220);
        // $pic4 = $imageHelper->resize($basePath, $imagePath, 392, 342);
        // $pic5 = $imageHelper->resize($basePath, $imagePath, 140, 140);

        // info([$pic1, $pic2, $pic3, $pic4, $pic5]);
    }
}
