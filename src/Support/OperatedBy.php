<?php

namespace Mortezamasumi\FbUser\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

class OperatedBy
{
    /**
     * Build an SQL expression resolving the latest activity causer's name.
     *
     * This matches HasOperatedByAttributes: the causer of the newest activity
     * with the requested description for the current subject.
     */

    /**
     * @param  Builder<Model>  $query
     */
    public static function subquery(Builder $query, string $description): string
    {
        $subject = $query->getModel();
        $pdo     = $subject->getConnection()->getPdo();

        $sql = sprintf(
            "(select concat(users.first_name, ' ', users.last_name) "
                . 'from activity_log '
                . 'inner join users on users.id = activity_log.causer_id '
                . 'where activity_log.subject_type = %s '
                . 'and activity_log.subject_id = %s '
                . 'and activity_log.description = %s '
                . 'order by activity_log.id desc limit 1)',
            $pdo->quote($subject->getMorphClass()),
            $subject->getQualifiedKeyName(),
            $pdo->quote($description),
        );

        return $sql;
    }

    /**
     * Return a Filament sortable(query:) closure for an operated-by accessor.
     */
    public static function sortUsing(string $description): \Closure
    {
        return function (Builder $query, string $direction) use ($description): void {
            $subject = $query->getModel();

            $query
                ->selectSub(function (QueryBuilder $subquery) use ($subject, $description): void {
                    $subquery
                        ->from('activity_log')
                        ->join('users', 'users.id', '=', 'activity_log.causer_id')
                        ->selectRaw("concat(users.first_name, ' ', users.last_name)")
                        ->where('activity_log.subject_type', $subject->getMorphClass())
                        ->whereColumn('activity_log.subject_id', $subject->getQualifiedKeyName())
                        ->where('activity_log.description', $description)
                        ->latest('activity_log.id')
                        ->limit(1);
                }, '__operated_by_sort')
                ->orderBy('__operated_by_sort', $direction === 'desc' ? 'desc' : 'asc')
                ->addSelect($subject->getTable() . '.*');
        };
    }

    /**
     * Return a Filament searchable(query:) closure for an operated-by accessor.
     */
    public static function searchUsing(string $description): \Closure
    {
        return function (Builder $query, string $search) use ($description): void {
            $query->whereExists(self::causerQuery($query, $description, $search));
        };
    }

    /** Find subjects whose latest matching activity causer has the searched name. */

    /**
     * @param  Builder<Model>  $query
     */
    private static function causerQuery(Builder $query, string $description, string $search): \Closure
    {
        $subject = $query->getModel();

        return fn(QueryBuilder $activityQuery) => $activityQuery
            ->select('activity_log.id')
            ->from('activity_log')
            ->join('users', 'users.id', '=', 'activity_log.causer_id')
            ->whereColumn('activity_log.subject_id', $subject->getQualifiedKeyName())
            ->where('activity_log.subject_type', $subject->getMorphClass())
            ->where('activity_log.description', $description)
            ->whereRaw(
                'activity_log.id = (select max(latest_activity.id) from activity_log as latest_activity where latest_activity.subject_type = ? and latest_activity.subject_id = activity_log.subject_id and latest_activity.description = ?)',
                [$subject->getMorphClass(), $description],
            )
            ->where(function (QueryBuilder $nameQuery) use ($search): void {
                $nameQuery
                    ->where('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("CONCAT(users.last_name, ' ', users.first_name) LIKE ?", ["%{$search}%"]);
            });
    }
}
