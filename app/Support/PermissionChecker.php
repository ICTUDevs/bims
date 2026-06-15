<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

final class PermissionChecker
{
    /** @return Collection<int, string> */
    public static function names(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return $user->getAllPermissions()->pluck('name');
    }

    public static function allows(?User $user, string ...$permissions): bool
    {
        if (! $user || $permissions === []) {
            return false;
        }

        $held = static::names($user);

        return collect($permissions)->contains(fn (string $permission) => $held->contains($permission));
    }
}
