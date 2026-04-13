<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\KpiDashboardRequest;
use App\Http\Requests\StoreKpiInputRequest;
use App\Http\Resources\KpiDashboardResource;
use App\Http\Resources\KpiScoreDetailResource;
use App\Services\KpiService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class KpiManagementController extends ApiController
{
    public function __construct(private readonly KpiService $kpiService)
    {
    }

    public function dashboard(KpiDashboardRequest $request): JsonResponse
    {
        $dashboard = $this->kpiService->getDashboard($request->filters());

        return $this->resource(new KpiDashboardResource($dashboard));
    }

    public function showUser(int $id, KpiDashboardRequest $request): JsonResponse
    {
        try {
            $score = $this->kpiService->getUserScore($id, $request->filters(), $request->user());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), status: Response::HTTP_FORBIDDEN);
        }

        return $this->resource(new KpiScoreDetailResource($score));
    }

    public function ranking(KpiDashboardRequest $request): JsonResponse
    {
        $ranking = $this->kpiService->getRanking($request->filters());

        return $this->success(KpiScoreDetailResource::collection($ranking)->resolve(), 'Berhasil');
    }

    public function input(StoreKpiInputRequest $request): JsonResponse
    {
        try {
            $score = $this->kpiService->inputRecord($request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->resource(
            new KpiScoreDetailResource($score),
            'KPI record berhasil disimpan dan score diperbarui.',
            Response::HTTP_CREATED
        );
    }
}
