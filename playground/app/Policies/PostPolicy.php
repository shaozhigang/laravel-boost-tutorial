<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Anyone (including guests) can view the public list.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Anyone can view a published post.
     * Only the author can view their own drafts / scheduled posts.
     */
    public function view(?User $user, Post $post): bool
    {
        if ($post->published_at !== null && $post->published_at->isPast()) {
            return true;
        }

        return $user !== null && $user->id === $post->user_id;
    }

    /**
     * Any authenticated user can create posts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the author can update their own post.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Only the author can delete their own post.
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
