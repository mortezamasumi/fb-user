<?php

namespace Mortezamasumi\FbUser\Jobs;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;

class RemoveUnAttendUsers implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $provider = Auth::getProvider();

        if (! $provider instanceof EloquentUserProvider) {
            return;
        }

        /** @var class-string<Model> $model */
        $model = $provider->getModel();

        $model::whereDoesntHave('roles')
            ->where('created_at', '<', now()->subHours(config('fb-user.remove_unattend_user_hours')))
            ->delete();
    }
}
