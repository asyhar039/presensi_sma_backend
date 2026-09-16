<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Allow the request through when the authenticated user holds one of the given roles.
     *
     * Accepts a variadic list and comma/pipe separated values, e.g.
     * `role:admin`, `role:admin,teacher` or `role:admin|teacher`.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = collect($roles)
            ->flatMap(fn (string $role): array => preg_split('/[|,]/', $role) ?: [])
            ->map(fn (string $role): ?RoleEnum => RoleEnum::tryFrom(strtolower(trim($role))))
            ->filter()
            ->all();

        $user = $request->user();

        if ($user === null || ! $user->hasRole($allowed)) {
            abort(Response::HTTP_FORBIDDEN, 'Forbidden.');
        }

        return $next($request);
    }
}
