<?php

namespace App\Http\Controllers\Api\BootcampController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Actions\Bootcamps\{
    ListBootcampsBasicAction,
    ShowBootcampAction
};

class BootcampController extends Controller
{
    public function index(Request $request, ListBootcampsBasicAction $action)
    {
        $limit = $request->input('limit', 10);
        return $action->execute($limit);
    }

    public function show(Request $request, int $id, ShowBootcampAction $action)
    {
        return $action->execute($id);
    }
}
