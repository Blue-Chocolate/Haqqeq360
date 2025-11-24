<?php

namespace App\Repositories\ScholarshipRequest;

use App\Models\ScholarshipRequest;
use Exception;

class ScholarshipRequestRepository
{
    public function getAllByUser($userId)
    {
        return ScholarshipRequest::where('user_id', $userId)->latest()->get();
    }

    public function create(array $data)
    {
        return ScholarshipRequest::create($data);
    }
}