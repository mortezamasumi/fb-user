<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mortezamasumi\FbUser\Support\OperatedBy;
use Mortezamasumi\FbUser\Tests\Services\User;

beforeEach(function (): void {
    DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$args): string => implode('', $args), -1);

    Schema::create('operated_by_subjects', function (Blueprint $table): void {
        $table->uuid('id')->primary();
    });

    Schema::create('activity_log', function (Blueprint $table): void {
        $table->id();
        $table->string('description');
        $table->string('subject_type');
        $table->uuid('subject_id');
        $table->uuid('causer_id');
    });
});

afterEach(function (): void {
    Schema::dropIfExists('activity_log');
    Schema::dropIfExists('operated_by_subjects');
});

it('sorts and searches by the latest activity causer name', function (): void {
    $alice = User::factory()->create(['first_name' => 'Alice', 'last_name' => 'Zulu']);
    $bob = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Yan']);

    DB::table('operated_by_subjects')->insert([
        ['id' => '00000000-0000-0000-0000-000000000001'],
        ['id' => '00000000-0000-0000-0000-000000000002'],
    ]);

    DB::table('activity_log')->insert([
        ['description' => 'created', 'subject_type' => OperatedBySubject::class, 'subject_id' => '00000000-0000-0000-0000-000000000001', 'causer_id' => $alice->id],
        ['description' => 'created', 'subject_type' => OperatedBySubject::class, 'subject_id' => '00000000-0000-0000-0000-000000000002', 'causer_id' => $bob->id],
    ]);

    $query = OperatedBySubject::query();
    (OperatedBy::sortUsing('created'))($query, 'asc');

    expect($query->pluck('id')->all())->toBe([
        '00000000-0000-0000-0000-000000000001',
        '00000000-0000-0000-0000-000000000002',
    ]);

    $query = OperatedBySubject::query();
    (OperatedBy::searchUsing('created'))($query, 'Bob');

    expect($query->pluck('id')->all())->toBe([
        '00000000-0000-0000-0000-000000000002',
    ]);
});

class OperatedBySubject extends Model
{
    public $timestamps = false;

    protected $table = 'operated_by_subjects';

    protected $keyType = 'string';

    public $incrementing = false;
}
