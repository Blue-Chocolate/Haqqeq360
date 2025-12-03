<?php 

namespace App\Repositories\CaseStudy;

use App\Models\CaseStudyAnswer;

class CaseStudyAnswerRepository
{
    public function create(array $data): CaseStudyAnswer
    {
        return CaseStudyAnswer::create($data);
    }

    public function update(CaseStudyAnswer $answer, array $data)
    {
        $answer->update($data);
        return $answer;
    }

    public function find($id): ?CaseStudyAnswer
    {
        return CaseStudyAnswer::with('files')->find($id);
    }

    public function answersForCase($caseId)
    {
        return CaseStudyAnswer::where('case_study_id', $caseId)
            ->with('student')
            ->latest()
            ->get();
    }
}
