<?php

use App\Jobs\SendPostPublishedEmailJob;
use App\Mail\PostPublishedMail;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

/*
|--------------------------------------------------------------------------
| dispatch 触发条件 — 通过 HTTP 入口测试
|--------------------------------------------------------------------------
*/

it('dispatches the email job when a post is created as published', function () {
    Queue::fake();

    $author = User::factory()->create();

    actingAs($author)->post(route('posts.store'), [
        'title' => 'Hello',
        'slug'  => 'hello',
        'body'  => 'Body content here.',
        'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
    ]);

    Queue::assertPushed(SendPostPublishedEmailJob::class, function ($job) use ($author) {
        return $job->post->slug === 'hello'
            && $job->post->user_id === $author->id;
    });
});

it('does not dispatch the email job when a post is created as draft', function () {
    Queue::fake();

    $author = User::factory()->create();

    actingAs($author)->post(route('posts.store'), [
        'title' => 'Draft post',
        'slug'  => 'draft-post',
        'body'  => 'Still drafting...',
        'published_at' => null,
    ]);

    Queue::assertNotPushed(SendPostPublishedEmailJob::class);
});

it('does not dispatch the email job when a post is scheduled for the future', function () {
    Queue::fake();

    $author = User::factory()->create();

    actingAs($author)->post(route('posts.store'), [
        'title' => 'Future post',
        'slug'  => 'future-post',
        'body'  => 'Coming soon.',
        'published_at' => now()->addDay()->format('Y-m-d\TH:i'),
    ]);

    Queue::assertNotPushed(SendPostPublishedEmailJob::class);
});

/*
|--------------------------------------------------------------------------
| Job 内部行为 — 直接执行 handle()
|--------------------------------------------------------------------------
*/

it('sends the published email to the author when the job handles', function () {
    Mail::fake();

    $post = Post::factory()->create();

    (new SendPostPublishedEmailJob($post))->handle();

    Mail::assertSent(PostPublishedMail::class, function ($mail) use ($post) {
        return $mail->hasTo($post->author->email)
            && $mail->post->is($post);
    });
});

it('renders the post title into the mail subject', function () {
    $post = Post::factory()->create(['title' => 'My Special Title']);

    $mailable = new PostPublishedMail($post);

    expect($mailable->envelope()->subject)
        ->toContain('My Special Title');
});

it('does not send mail when the author has no email', function () {
    Mail::fake();

    $authorWithoutEmail = User::factory()->create(['email' => '']);
    $post = Post::factory()->for($authorWithoutEmail, 'author')->create();

    (new SendPostPublishedEmailJob($post))->handle();

    Mail::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Job 配置 — 直接读属性
|--------------------------------------------------------------------------
*/

it('configures the job to retry up to 3 times with 5s backoff', function () {
    $post = Post::factory()->create();
    $job = new SendPostPublishedEmailJob($post);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe(5);
});

/*
|--------------------------------------------------------------------------
| 失败链路 — failed() 钩子 + 异常传播
|--------------------------------------------------------------------------
*/

it('writes a structured error log when the failed hook is called', function () {
    Log::spy();

    $post = Post::factory()->create();
    $exception = new RuntimeException('SMTP server unreachable');

    (new SendPostPublishedEmailJob($post))->failed($exception);

    Log::shouldHaveReceived('error')
        ->withArgs(function (string $message, array $context) use ($post) {
            return $message === 'SendPostPublishedEmailJob failed'
                && $context['post_id'] === $post->id
                && $context['error'] === 'SMTP server unreachable';
        })
        ->once();
});

it('propagates mail exceptions so the queue worker can retry', function () {
    $post = Post::factory()->create();

    Mail::shouldReceive('to')
        ->once()
        ->andThrow(new RuntimeException('SMTP connection refused'));

    expect(fn () => (new SendPostPublishedEmailJob($post))->handle())
        ->toThrow(RuntimeException::class, 'SMTP connection refused');
});
