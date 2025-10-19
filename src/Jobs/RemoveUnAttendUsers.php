<?php

namespace Mortezamasumi\FbUser\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;

class RemoveUnAttendUsers implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        /** @disregard */
        Auth::getProvider()->getModel()::whereDoesntHave('roles')
            ->where('created_at', '<', now()->subHours(config('fb-user.remove_unattend_user_hours')))
            ->delete();
    }
}
