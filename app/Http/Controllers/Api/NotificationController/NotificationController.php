<?php

namespace App\Http\Controllers\Api\NotificationController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * Success response helper
     */
    protected function success($data, $message = 'Success', $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Error response helper
     */
    protected function error($message, $status = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Validate pagination parameters
     */
    protected function validatePaginationParams(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [
            'per_page' => min((int) $request->input('per_page', 15), 100),
            'page' => (int) $request->input('page', 1)
        ];
    }

    /**
     * Get all notifications for authenticated user with pagination
     * GET /api/notifications
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Validate pagination parameters
            $params = $this->validatePaginationParams($request);

            // Check if user is authenticated
            if (!Auth::check()) {
                return $this->error('User not authenticated', 401);
            }

            $user = Auth::user();

            // Check if user has notifications relationship
            if (!method_exists($user, 'userNotifications')) {
                Log::error('User model does not have userNotifications relationship');
                return $this->error('Notifications feature not available', 500);
            }

            $notifications = $user->userNotifications()
                ->orderBy('created_at', 'desc')
                ->paginate($params['per_page']);

            // Handle empty results
            if ($notifications->isEmpty() && $notifications->currentPage() > 1) {
                return $this->error('Page not found', 404);
            }

            return $this->success([
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                    'has_more_pages' => $notifications->hasMorePages()
                ]
            ], 'Notifications retrieved successfully');

        } catch (ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            Log::error('Error fetching notifications: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to retrieve notifications', 500);
        }
    }

    /**
     * Get a single notification
     * GET /api/notifications/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                return $this->error('Invalid notification ID', 400);
            }

            if (!Auth::check()) {
                return $this->error('User not authenticated', 401);
            }

            $user = Auth::user();

            if (!method_exists($user, 'userNotifications')) {
                Log::error('User model does not have userNotifications relationship');
                return $this->error('Notifications feature not available', 500);
            }

            $notification = $user->userNotifications()->find($id);

            if (!$notification) {
                return $this->error('Notification not found', 404);
            }

            return $this->success($notification, 'Notification retrieved successfully');

        } catch (Exception $e) {
            Log::error('Error fetching notification: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to retrieve notification', 500);
        }
    }

    /**
     * Mark notification as read (triggered when user clicks on notification)
     * PATCH /api/notifications/{id}/read
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markRead($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return $this->error('Invalid notification ID', 400);
            }

            if (!Auth::check()) {
                return $this->error('User not authenticated', 401);
            }

            $user = Auth::user();

            if (!method_exists($user, 'userNotifications')) {
                Log::error('User model does not have userNotifications relationship');
                return $this->error('Notifications feature not available', 500);
            }

            $notification = $user->userNotifications()->find($id);

            if (!$notification) {
                return $this->error('Notification not found', 404);
            }

            // Check if already read
            if ($notification->is_read) {
                return $this->success($notification, 'Notification is already marked as read');
            }

            $notification->update(['is_read' => true]);
            $notification->refresh();

            return $this->success($notification, 'Notification marked as read');

        } catch (Exception $e) {
            Log::error('Error marking notification as read: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to mark notification as read', 500);
        }
    }

    /**
     * Mark notification as unread
     * PATCH /api/notifications/{id}/unread
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markUnread($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return $this->error('Invalid notification ID', 400);
            }

            if (!Auth::check()) {
                return $this->error('User not authenticated', 401);
            }

            $user = Auth::user();

            if (!method_exists($user, 'userNotifications')) {
                Log::error('User model does not have userNotifications relationship');
                return $this->error('Notifications feature not available', 500);
            }

            $notification = $user->userNotifications()->find($id);

            if (!$notification) {
                return $this->error('Notification not found', 404);
            }

            // Check if already unread
            if (!$notification->is_read) {
                return $this->success($notification, 'Notification is already marked as unread');
            }

            $notification->update(['is_read' => false]);
            $notification->refresh();

            return $this->success($notification, 'Notification marked as unread');

        } catch (Exception $e) {
            Log::error('Error marking notification as unread: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to mark notification as unread', 500);
        }
    }

    /**
     * Delete a notification
     * DELETE /api/notifications/{id}
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            if (!is_numeric($id) || $id <= 0) {
                return $this->error('Invalid notification ID', 400);
            }

            if (!Auth::check()) {
                return $this->error('User not authenticated', 401);
            }

            $user = Auth::user();

            if (!method_exists($user, 'userNotifications')) {
                Log::error('User model does not have userNotifications relationship');
                return $this->error('Notifications feature not available', 500);
            }

            $notification = $user->userNotifications()->find($id);

            if (!$notification) {
                return $this->error('Notification not found', 404);
            }

            $notification->delete();

            return $this->success(null, 'Notification deleted successfully');

        } catch (Exception $e) {
            Log::error('Error deleting notification: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'notification_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('Failed to delete notification', 500);
        }
    }
}