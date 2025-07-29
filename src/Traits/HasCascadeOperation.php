<?php

namespace Mortezamasumi\FbUser\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;
use InvalidArgumentException;

trait HasCascadeOperation
{
    private function checkRelationExists(string $relation): void
    {
        if (! method_exists($this, $relation)) {
            $model = class_basename($this);

            throw new InvalidArgumentException("model {$model} has not relation named {$relation}");
        }
    }

    private function getRelationRole(mixed $relation_role): array
    {
        if (! Arr::isAssoc($relation_role)) {
            throw new InvalidArgumentException('relation_role must be associative array');
        }

        $role = reset($relation_role);
        $relation = key($relation_role);

        if (! is_string($role) || ! is_string($relation)) {
            throw new InvalidArgumentException('relation_role must have key and value as string');
        }

        return [$relation, $role];
    }

    /**
     * Update relation to assign role
     *
     * @param array<string, string|string[]>|array<array<string, string|string[]>> $relation_roles
     *    Either:
     *    - A single associative array where keys are relations and value is role (string)
     *    - An array of such associative arrays
     *
     *    Example (single): ['relation' => 'role']
     *    Example (multiple): [['relation1' => 'role1'], ['relation2' => 'role2']]
     *
     * @return Model
     */
    public function cascadeUpdate(array $relation_roles): Model
    {
        foreach (
            Arr::isAssoc($relation_roles) ? [$relation_roles] : $relation_roles as $relation_role
        ) {
            [$relation, $role] = $this->getRelationRole($relation_role);

            $this->checkRelationExists($relation);

            Role::findByName($role);

            // $this is HasRole model (like User)
            if (method_exists($this, 'hasRole')) {
                $relations = $this->$relation()->withTrashed();

                if ($relations->exists()) {
                    if ($this->hasRole($role)) {
                        $relations->each(fn ($related) => $related->restoreQuietly());
                    } else {
                        if ($this->isForceDeleting()) {
                            $relations->each(fn ($related) => $related->forceDeleteQuietly());
                        } else {
                            $relations->each(fn ($related) => $related->deleteQuietly());
                        }
                    }
                }
            }

            // $this->$relation is HasRole (like User)
            if (! $this->trashed() && $this->$relation && method_exists($this->$relation, 'assignRole')) {
                $this->$relation->assignRole($role);
            }
        }

        return $this;
    }

    /**
     * Update relation to delete relation when it has only one role or just remove role from relation
     *
     * @param array<string, string|string[]>|array<array<string, string|string[]>> $relation_roles
     *    Either:
     *    - An string as relation, no role activity will do
     *    - A single associative array where keys are relations and value is role (string)
     *    - An array of such associative arrays
     *
     *    Example (single): 'relation'
     *    Example (multiple relation): ['relation1','relation2',...]
     *    Example (single relation-role): ['relation' => 'role']
     *    Example (multiple relation-role): [['relation1' => 'role1'], ['relation2' => 'role2'],...]
     *
     * @return Model
     */
    public function cascadeDelete(array|string $relation_roles): Model
    {
        $relation_roles = Arr::wrap($relation_roles);

        foreach (
            Arr::isAssoc($relation_roles) ? [$relation_roles] : $relation_roles as $relation_role
        ) {
            [$relation, $role] = match (true) {
                is_array($relation_role) => $this->getRelationRole($relation_role),
                is_string($relation_role) => [$relation_role, null],
                default => throw new InvalidArgumentException('relation must be string or assoc array')
            };

            $this->checkRelationExists($relation);

            $relations = $this->$relation()->withTrashed();

            if (! $relations->exists()) {
                continue;
            }

            if ($role) {
                Role::findByName($role);

                $relations->each(function ($related) use ($role) {
                    if ($related->hasExactRoles($role)) {
                        if ($this->isForceDeleting()) {
                            $related->forceDeleteQuietly();
                        } else {
                            $related->deleteQuietly();
                        }
                    } else {
                        $related->removeRole($role);
                    }
                });
            } else {
                if ($this->isForceDeleting()) {
                    $relations->each(fn ($related) => $related->forceDeleteQuietly());
                } else {
                    $relations->each(fn ($related) => $related->deleteQuietly());
                }
            }
        }

        return $this;
    }

    /**
     * Update relation to restore relation when and add role if not exists
     *
     * @param array<string, string|string[]>|array<array<string, string|string[]>> $relation_roles
     *    Either:
     *    - An string as relation, no role activity will do
     *    - A single associative array where keys are relations and value is role (string)
     *    - An array of such associative arrays
     *
     *    Example (single): 'relation'
     *    Example (multiple relation): ['relation1','relation2',...]
     *    Example (single relation-role): ['relation' => 'role']
     *    Example (multiple relation-role): [['relation1' => 'role1'], ['relation2' => 'role2'],...]
     *
     * @return Model
     */
    public function cascadeRestore(array|string $relation_roles): Model
    {
        $relation_roles = Arr::wrap($relation_roles);

        foreach (
            Arr::isAssoc($relation_roles) ? [$relation_roles] : $relation_roles as $relation_role
        ) {
            [$relation, $role] = match (true) {
                is_array($relation_role) => $this->getRelationRole($relation_role),
                is_string($relation_role) => [$relation_role, null],
                default => throw new InvalidArgumentException('relation must be string')
            };

            $this->checkRelationExists($relation);

            $relations = $this->$relation()->withTrashed();

            if (! $relations->exists()) {
                continue;
            }

            if ($role) {
                Role::findByName($role);
            }

            $relations->each(function ($related) use ($role) {
                $related->restoreQuietly();

                if (method_exists($related, 'assignRole')) {
                    $related->assignRole($role);
                }
            });
        }

        return $this;
    }
}
