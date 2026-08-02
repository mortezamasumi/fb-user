<?php

namespace Mortezamasumi\FbUser\Tests\Services;

use Illuminate\Database\Eloquent\Model;
use Mortezamasumi\FbUser\Traits\HasOperatedByAttributes;

class Post extends Model
{
    use HasOperatedByAttributes;

    protected $guarded = [];

    public function activities()
    {
        return $this->hasMany(Activity::class, 'subject_id');
    }
}
