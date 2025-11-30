<?php

namespace App\Repositories\WhyChooseFeatureRepository;

use App\Models\WhyChooseFeature;

class WhyChooseFeatureRepository
{
    // Get active features with pagination
    public function getActivePaginated(int $perPage)
    {
        return WhyChooseFeature::where('is_active', true)
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    // Find a feature by ID
    public function findById(int $id)
    {
        return WhyChooseFeature::find($id);
    }
}