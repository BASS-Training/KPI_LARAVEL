<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's tenant and binds it to the IoC container.
 * Must run AFTER auth:sanctum middleware.
 *
 * Super Admins bypass tenant scope so they can access cross-tenant data.
 * Users with multi-tenant access can switch via X-Tenant-ID header.
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Super Admin role bypasses tenant isolation entirely
        if ($user->hasRole('super_admin')) {
            app()->instance('bypass_tenant_scope', true);
            app()->instance('current_tenant_id', null);

            return $next($request);
        }

        // Allow users with multi-tenant access to switch via header
        $requestedTenantId = $request->header('X-Tenant-ID');
        if ($requestedTenantId && $user->hasAccessToTenant((int) $requestedTenantId)) {
            $tenant = Tenant::withoutGlobalScopes()->find($requestedTenantId);
            if ($tenant && $tenant->status === 'active') {
                app()->instance('current_tenant_id', $tenant->id);
                app()->instance('current_tenant', $tenant);
                app()->instance('bypass_tenant_scope', false);

                return $next($request);
            }
        }

        // Fall back to user's primary tenant (users.tenant_id)
        if (empty($user->tenant_id)) {
            // Check if user has any tenant via pivot table
            $pivotTenant = $user->tenants()->where('tenants.status', 'active')->first();
            if ($pivotTenant) {
                app()->instance('current_tenant_id', $pivotTenant->id);
                app()->instance('current_tenant', $pivotTenant);
                app()->instance('bypass_tenant_scope', false);

                return $next($request);
            }

            return response()->json(['message' => 'User has no tenant assigned.'], 403);
        }

        $tenant = Tenant::withoutGlobalScopes()->find($user->tenant_id);

        if (! $tenant || $tenant->status !== 'active') {
            return response()->json(['message' => 'Tenant is inactive or not found.'], 403);
        }

        app()->instance('current_tenant_id', $tenant->id);
        app()->instance('current_tenant', $tenant);
        app()->instance('bypass_tenant_scope', false);

        return $next($request);
    }
}
