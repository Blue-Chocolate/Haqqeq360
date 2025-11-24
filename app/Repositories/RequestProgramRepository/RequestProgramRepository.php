<?php

namespace App\Repositories\RequestProgramRepository;

use App\Models\RequestProgram;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RequestProgramRepository
{
    /**
     * Get all program requests.
     */
    public function getAll(): Collection
    {
        return RequestProgram::with('user')->latest()->get();
    }

    /**
     * Get paginated program requests.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return RequestProgram::with('user')->latest()->paginate($perPage);
    }

    /**
     * Find a program request by ID.
     */
    public function findById(int $id): ?RequestProgram
    {
        return RequestProgram::with('user')->find($id);
    }

    /**
     * Get all pending requests.
     */
    public function getPending(): Collection
    {
        return RequestProgram::with('user')->pending()->latest()->get();
    }

    /**
     * Get all approved requests.
     */
    public function getApproved(): Collection
    {
        return RequestProgram::with('user')->approved()->latest()->get();
    }

    /**
     * Get all rejected requests.
     */
    public function getRejected(): Collection
    {
        return RequestProgram::with('user')->rejected()->latest()->get();
    }

    /**
     * Get requests by user ID.
     */
    public function getByUserId(int $userId): Collection
    {
        return RequestProgram::where('user_id', $userId)->latest()->get();
    }

    /**
     * Create a new program request.
     */
    public function create(array $data): RequestProgram
    {
        return RequestProgram::create($data);
    }

    /**
     * Update a program request.
     */
    public function update(int $id, array $data): bool
    {
        $request = $this->findById($id);
        
        if (!$request) {
            return false;
        }

        return $request->update($data);
    }

    /**
     * Delete a program request.
     */
    public function delete(int $id): bool
    {
        $request = $this->findById($id);
        
        if (!$request) {
            return false;
        }

        return $request->delete();
    }

    /**
     * Update request status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Approve a request.
     */
    public function approve(int $id): bool
    {
        return $this->updateStatus($id, 'approved');
    }

    /**
     * Reject a request.
     */
    public function reject(int $id): bool
    {
        return $this->updateStatus($id, 'rejected');
    }
}