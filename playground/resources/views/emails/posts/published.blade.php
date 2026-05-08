<x-mail::message>
# 新文章已发布

你好 {{ $author->name }}，

你的文章 **{{ $post->title }}** 已成功发布。

发布时间：{{ $post->published_at?->format('Y-m-d H:i') ?? '草稿' }}

---

## 摘要

{{ \Illuminate\Support\Str::limit(strip_tags($post->body), 200) }}

<x-mail::button :url="$url">
查看文章
</x-mail::button>

感谢使用 Boost Playground，
{{ config('app.name') }}
</x-mail::message>
