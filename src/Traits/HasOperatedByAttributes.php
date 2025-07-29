<?php

namespace Mortezamasumi\FbUser\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasOperatedByAttributes
{
    protected function createdBy(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                $causer = '';

                if (method_exists($this, 'activities')) {
                    $causer = $this
                        ->activities()
                        ->where('description', 'created')
                        ->where('subject_id', $this->id ?? null)
                        ->latest()
                        ->first()
                        ?->causer
                        ?->name;
                }

                return $causer;
            },
        );
    }

    protected function updatedBy(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                $causer = '';

                if (method_exists($this, 'activities')) {
                    $causer = $this
                        ->activities()
                        ->where('description', 'updated')
                        ->where('subject_id', $this->id ?? null)
                        ->latest()
                        ->first()
                        ?->causer
                        ?->name;
                }

                return $causer;
            },
        );
    }
}
