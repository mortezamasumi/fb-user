<?php

namespace Mortezamasumi\FbUser\Tests\Services;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $guarded = [];

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
