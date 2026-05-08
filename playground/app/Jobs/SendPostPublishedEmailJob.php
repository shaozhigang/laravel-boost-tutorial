<?php

namespace App\Jobs;

use App\Mail\PostPublishedMail;
use App\Models\Post;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPostPublishedEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(public Post $post)
    {
    }

    public function handle(): void
    {
        $this->post->loadMissing('author');

        if (! $this->post->author?->email) {
            return;
        }

        Mail::to($this->post->author->email)
            ->send(new PostPublishedMail($this->post));
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('SendPostPublishedEmailJob failed', [
            'post_id' => $this->post->id,
            'error' => $e->getMessage(),
        ]);
    }
}
