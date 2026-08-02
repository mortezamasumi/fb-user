<?php

namespace Mortezamasumi\FbUser\Tests\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mortezamasumi\FbUser\Traits\HasCascadeOperation;

class CascadeSubject extends Model
{
    use HasCascadeOperation;
    use SoftDeletes;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
