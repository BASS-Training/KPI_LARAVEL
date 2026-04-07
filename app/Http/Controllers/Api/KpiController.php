<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\KpiScoreResource;
use App\Models\User;
use App\Services\KpiCalculatorService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KpiController extends ApiController
{
    public function __construct(private readonly KpiCalculatorService $calculator)
    {
    }

    public function me(Request $request)
    {
        $score = $this->calculator->calculateForUser(
            $request->user(),
            $request->integer('bulan') ?: null,
            $request->integer('tahun') ?: null,
        );

        return $this->resource(new KpiScoreResource($score));
    }

    public function show(Request $request, User $user)
    {
        if (!$request->user()->canManageAllData()) {
            return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
        }

        $score = $this->calculator->calculateForUser(
            $user,
            $request->integer('bulan') ?: null,
            $request->integer('tahun') ?: null,
        );

        return $this->resource(new KpiScoreResource($score));
    }

    public function ranking(Request $request)
    {
        $ranking = $this->calculator
            ->ranking($request->integer('bulan') ?: null, $request->integer('tahun') ?: null)
            ->values();

        return $this->success(KpiScoreResource::collection($ranking)->resolve(), 'Berhasil');
    }
}
