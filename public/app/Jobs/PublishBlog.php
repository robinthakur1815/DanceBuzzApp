<?php

namespace App\Jobs;

use App\Enums\CollectionType;
use App\Enums\PublishStatus;
use App\Helpers\ImageHelper;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishBlog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $user;
    protected $blog;

    public function __construct($blog, $user)
    {
        $this->onQueue('publish');
        $this->blog = $blog;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $blog = $this->blog;
        $user = $this->user;

        $imageHelper = new ImageHelper();

        $saved_content = json_decode($blog->saved_content);

        if ($blog->collection_type != CollectionType::researchSummaries) {
            if (isset($saved_content->content)) {
                $saved_content->content = $imageHelper->replaceBase64WithUrl($saved_content->content, config('app.cms_media_path'));
            }
        }

        $data = [
            'published_content' => json_encode($saved_content),
            'status'            => PublishStatus::Published,
            'published_by'      => $user->id,
            'published_at'      => Carbon::now(),
        ];

        $blog->update($data);
        $blog->refresh();

        $versionData = [
            'version' => Carbon::now()->toDateString().'-'.$blog->title,
            'published_content' => $blog->published_content,
            'created_by'      => $user->id,
            'updated_by'      => $user->id,
        ];
        $version = $blog->versions()->create($versionData);
    }
}
