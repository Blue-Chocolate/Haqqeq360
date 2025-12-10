<?php

namespace App\Repositories\DiscussionRepository;

use App\Models\Discussion;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\DiscussionComment;

class DiscussionRepository
{
    public function getPublishedDiscussions(int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        return Discussion::where('is_published', true)
            ->with(['user', 'course'])
            ->withCount(['comments', 'likes'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }

    public function findById(int $id): ?Discussion
    {
        return Discussion::with(['user', 'course'])
            ->withCount(['comments', 'likes'])
            ->find($id);
    }

    public function getCommentsByDiscussion(int $discussionId, int $limit = 10, int $page = 1): LengthAwarePaginator
    {
        return DiscussionComment::where('discussion_id', $discussionId)
            ->whereNull('parent_id') // Only get top-level comments
            ->with(['user', 'replies.user', 'replies.likes'])
            ->withCount(['likes', 'replies'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);
    }
}