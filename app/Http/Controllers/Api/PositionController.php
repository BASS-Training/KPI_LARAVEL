<?php

namespace App\Http\Controllers\Api;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends ApiController
{
    public function index(Request $request)
    {
        $positions = Position::query()
            ->with('department:id,nama,division_id')
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->integer('department_id')))
            ->orderBy('nama')
            ->get();

        return $this->success($positions);
    }
}
