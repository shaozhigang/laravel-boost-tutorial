<?php

namespace App\Mail;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PostPublishedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Post $post)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '新文章已发布：'.$this->post->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.posts.published',
            with: [
                'post' => $this->post,
                'author' => $this->post->author,
                'url' => url('/posts/'.$this->post->slug),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
