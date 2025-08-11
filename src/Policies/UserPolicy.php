<?php

namespace Mortezamasumi\FbUser\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny($user): bool
    {
        return $user->can('view_any_user');
    }

    public function view($user): bool
    {
        return $user->can('view_user');
    }

    public function create($user): bool
    {
        return $user->can('create_user');
    }

    public function update($user): bool
    {
        return $user->can('update_user');
    }

    public function delete($user): bool
    {
        return $user->can('delete_user');
    }

    public function deleteAny($user): bool
    {
        return $user->can('delete_any_user');
    }

    public function forceDelete($user): bool
    {
        return $user->can('force_delete_user');
    }

    public function forceDeleteAny($user): bool
    {
        return $user->can('force_delete_any_user');
    }

    public function restore($user): bool
    {
        return $user->can('restore_user');
    }

    public function restoreAny($user): bool
    {
        return $user->can('restore_any_user');
    }

    public function replicate($user): bool
    {
        return $user->can('replicate_user');
    }

    public function reorder($user): bool
    {
        return $user->can('reorder_user');
    }

    public function export($user): bool
    {
        return $user->can('export_user');
    }
}
