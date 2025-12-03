<?php 


namespace App\Repositories\CaseStudy;

use App\Models\CaseStudy;

class CaseStudyRepository
{
    public function create(array $data): CaseStudy
    {
        return CaseStudy::create($data);
    }

    public function find($id): ?CaseStudy
    {
        return CaseStudy::with('answers')->find($id);
    }

    public function all()
    {
        return CaseStudy::latest()->paginate(10);
    }

    public function update(CaseStudy $caseStudy, array $data)
    {
        $caseStudy->update($data);
        return $caseStudy;
    }

    public function delete(CaseStudy $caseStudy)
    {
        return $caseStudy->delete();
    }
}
