<?php

namespace App\Http\Controllers\Api\DiscussionController;

use App\Actions\Comment\CreateCommentAction;
use App\Actions\Like\ToggleLikeAction;
use App\Models\Discussion;
use App\Models\DiscussionComment;
use App\Repositories\DiscussionRepository\DiscussionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class DiscussionController extends Controller
{
    public function __construct(
        private DiscussionRepository $discussionRepository,
        private CreateCommentAction $createCommentAction,
        private ToggleLikeAction $toggleLikeAction
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $limit = min($request->input('limit', 10), 100);
            $page = $request->input('page', 1);

            $discussions = $this->discussionRepository->getPublishedDiscussions($limit, $page);

            return response()->json([
                'success' => true,
                'data' => $discussions->items(),
                'pagination' => [
                    'current_page' => $discussions->currentPage(),
                    'per_page' => $discussions->perPage(),
                    'total' => $discussions->total(),
                    'last_page' => $discussions->lastPage(),
                    'from' => $discussions->firstItem(),
                    'to' => $discussions->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve discussions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $discussion = $this->discussionRepository->findById($id);

            if (!$discussion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discussion not found',
                ], 404);
            }

            // Check if user can view unpublished discussion
            if (!$discussion->is_published && $discussion->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this discussion',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $discussion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve discussion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getComments(Request $request, int $discussionId): JsonResponse
    {
        try {
            $limit = min($request->input('limit', 10), 100);
            $page = $request->input('page', 1);

            $discussion = $this->discussionRepository->findById($discussionId);

            if (!$discussion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discussion not found',
                ], 404);
            }

            if (!$discussion->is_published && $discussion->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view comments for this discussion',
                ], 403);
            }

            $comments = $this->discussionRepository->getCommentsByDiscussion($discussionId, $limit, $page);

            return response()->json([
                'success' => true,
                'data' => $comments->items(),
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                    'last_page' => $comments->lastPage(),
                    'from' => $comments->firstItem(),
                    'to' => $comments->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve comments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function comment(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'content' => 'required|string|max:5000',
                'parent_id' => 'nullable|exists:discussion_comments,id',
            ]);

            $discussion = $this->discussionRepository->findById($id);

            if (!$discussion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discussion not found',
                ], 404);
            }

            if (!$discussion->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot comment on unpublished discussion',
                ], 403);
            }

            // Validate parent comment belongs to same discussion
            if ($request->parent_id) {
                $parentComment = DiscussionComment::find($request->parent_id);
                if (!$parentComment || $parentComment->discussion_id !== $id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid parent comment',
                    ], 400);
                }
            }

            $comment = $this->createCommentAction->execute([
                'discussion_id' => $id,
                'user_id' => Auth::id(),
                'parent_id' => $request->parent_id,
                'content' => $request->content,
            ]);

            return response()->json([
                'success' => true,
                'data' => $comment->load('user'),
                'message' => 'Comment added successfully',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function likeDiscussion(int $id): JsonResponse
    {
        try {
            $discussion = $this->discussionRepository->findById($id);

            if (!$discussion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discussion not found',
                ], 404);
            }

            if (!$discussion->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot like unpublished discussion',
                ], 403);
            }

            $liked = $this->toggleLikeAction->execute($discussion, Auth::id());

            return response()->json([
                'success' => true,
                'data' => [
                    'liked' => $liked,
                    'likes_count' => $discussion->likes()->count(),
                ],
                'message' => $liked ? 'Discussion liked' : 'Discussion unliked',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like on discussion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function likeComment(int $commentId): JsonResponse
    {
        try {
            $comment = DiscussionComment::find($commentId);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comment not found',
                ], 404);
            }

            // Check if discussion is published
            $discussion = $comment->discussion;
            if (!$discussion || !$discussion->is_published) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot like comment on unpublished discussion',
                ], 403);
            }

            $liked = $this->toggleLikeAction->execute($comment, Auth::id());

            return response()->json([
                'success' => true,
                'data' => [
                    'liked' => $liked,
                    'likes_count' => $comment->likes()->count(),
                ],
                'message' => $liked ? 'Comment liked' : 'Comment unliked',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle like on comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}