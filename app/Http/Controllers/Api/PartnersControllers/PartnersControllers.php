<?php

namespace App\Http\Controllers\Api\PartnersControllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;

class PartnersController extends Controller
{
    // GET /api/partners?page=1&limit=10
    public function index(Request $request)
    {
        $limit = $request->get('limit', 10); // default 10 per page
        $partners = Partner::where('is_active', true)->paginate($limit);
        return response()->json($partners);
    }

    // GET /api/partners/{id}
    public function show($id)
    {
        $partner = Partner::findOrFail($id);
        return response()->json($partner);
    }
}