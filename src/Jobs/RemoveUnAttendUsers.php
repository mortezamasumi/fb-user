<?php

namespace Mortezamasumi\FbUser\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemoveUnAttendUsers implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $className = config('auth.providers.users.model');

        $usersWithoutRoles = $className::whereDoesntHave('roles')
            ->where('created_at', '<', now()->subHours(config('fb-user.remove_unattend_user_hours')))
            ->get();

        $className::whereIn('id', $usersWithoutRoles->pluck('id'))->delete();
    }
}
