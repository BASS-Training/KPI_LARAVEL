<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
        private readonly AuditService $auditService,
    ) {}

    #[OA\Get(
        path: '/api/v2/tenants',
        summary: 'List all tenants (paginated)',
        security: [['bearerAuth' => []]],
        tags: ['Tenants'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'suspended'])),
            new OA\Parameter(name: 'page',   in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated tenant list'),
            new OA\Response(response: 403, description: 'Forbidden — super_admin only'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::query()
            ->when($request->search, fn ($q) => $q->where('tenant_name', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($tenants);
    }

    #[OA\Post(
        path: '/api/v2/tenants',
        summary: 'Create a new tenant',
        security: [['bearerAuth' => []]],
        tags: ['Tenants'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tenant_code', 'tenant_name'],
                properties: [
                    new OA\Property(property: 'tenant_code',   type: 'string', example: 'COMP-001'),
                    new OA\Property(property: 'tenant_name',   type: 'string', example: 'PT Example'),
                    new OA\Property(property: 'domain',        type: 'string', example: 'app.example.com'),
                    new OA\Property(property: 'status',        type: 'string', enum: ['active', 'inactive', 'suspended']),
                    new OA\Property(property: 'contact_email', type: 'string', example: 'admin@example.com'),
                    new OA\Property(property: 'contact_phone', type: 'string', example: '+628123456789'),
                    new OA\Property(property: 'address',       type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tenant created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_code'    => 'required|string|max:20|unique:tenants,tenant_code',
            'tenant_name'    => 'required|string|max:255',
            'domain'         => 'nullable|string|max:255|unique:tenants,domain',
            'status'         => 'in:active,inactive,suspended',
            'logo_url'       => 'nullable|url',
            'primary_color'  => 'nullable|string|max:10',
            'contact_email'  => 'nullable|email',
            'contact_phone'  => 'nullable|string|max:20',
            'address'        => 'nullable|string',
        ]);

        $tenant = $this->tenantService->create($data);
        $this->auditService->log('tenants', 'create', 'Tenant', $tenant->id, null, $tenant->toArray());

        return response()->json(['message' => 'Tenant created.', 'data' => $tenant], 201);
    }

    #[OA\Get(
        path: '/api/v2/tenants/{id}',
        summary: 'Get a single tenant with counts',
        security: [['bearerAuth' => []]],
        tags: ['Tenants'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Tenant detail'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->loadCount(['users', 'departments', 'kpiTemplates']);

        return response()->json(['data' => $tenant]);
    }

    #[OA\Put(
        path: '/api/v2/tenants/{id}',
        summary: 'Update a tenant',
        security: [['bearerAuth' => []]],
        tags: ['Tenants'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tenant_name',   type: 'string'),
                    new OA\Property(property: 'domain',        type: 'string'),
                    new OA\Property(property: 'status',        type: 'string', enum: ['active', 'inactive', 'suspended']),
                    new OA\Property(property: 'contact_email', type: 'string'),
                    new OA\Property(property: 'address',       type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tenant updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'tenant_name'   => 'sometimes|string|max:255',
            'domain'        => 'nullable|string|max:255|unique:tenants,domain,'.$tenant->id,
            'status'        => 'in:active,inactive,suspended',
            'logo_url'      => 'nullable|url',
            'primary_color' => 'nullable|string|max:10',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'address'       => 'nullable|string',
            'settings'      => 'nullable|array',
        ]);

        $old     = $tenant->toArray();
        $updated = $this->tenantService->update($tenant, $data);
        $this->auditService->log('tenants', 'update', 'Tenant', $tenant->id, $old, $updated->toArray());

        return response()->json(['message' => 'Tenant updated.', 'data' => $updated]);
    }

    #[OA\Patch(
        path: '/api/v2/tenants/{id}/activate',
        summary: 'Activate a tenant',
        security: [['bearerAuth' => []]],
        tags: ['Tenants'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Tenant activated')]
    )]
    public function activate(Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->setStatus($tenant, 'active');
        $this->auditService->log('tenants', 'activate', 'Tenant', $tenant->id);

        return response()->json(['message' => 'Tenant activated.', 'data' => $tenant]);
    }

    #[OA\Patch(
        path: '/api/v2/tenants/{id}/deactivate',
        summary: 'Deactivate a tenant',
        security: [['bearerAuth' => []]],
        tags: ['Tenants'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Tenant deactivated')]
    )]
    public function deactivate(Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->setStatus($tenant, 'inactive');
        $this->auditService->log('tenants', 'deactivate', 'Tenant', $tenant->id);

        return response()->json(['message' => 'Tenant deactivated.', 'data' => $tenant]);
    }

    public function users(Tenant $tenant): JsonResponse
    {
        // Primary users (users.tenant_id = tenant.id)
        $primary = \App\Models\User::withoutGlobalScopes()
            ->with('roles')
            ->where('tenant_id', $tenant->id)
            ->get();

        // Secondary users assigned via user_tenants pivot
        $secondary = $tenant->secondaryUsers()
            ->with('roles')
            ->get()
            ->map(fn ($u) => $u->setAttribute('_via_pivot', true));

        $merged = $primary->merge($secondary)->unique('id')->values();

        return response()->json(['data' => $merged]);
    }
}
