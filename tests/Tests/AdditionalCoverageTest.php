<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbUser\Jobs\RemoveUnAttendUsers;
use Mortezamasumi\FbUser\Models\User as ModelsUser;
use Mortezamasumi\FbUser\Resources\Pages\ListUsers;
use Mortezamasumi\FbUser\Tests\Services\Activity;
use Mortezamasumi\FbUser\Tests\Services\CascadeSubject;
use Mortezamasumi\FbUser\Tests\Services\Post;
use Mortezamasumi\FbUser\Tests\Services\User;
use Mortezamasumi\FbUser\Widgets\NoRoleWidget;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Schema::create('cascade_subjects', function ($table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('posts', function ($table) {
        $table->id();
        $table->timestamps();
    });

    Schema::create('activities', function ($table) {
        $table->id();
        $table->foreignId('subject_id')->nullable();
        $table->foreignId('causer_id')->nullable();
        $table->string('description')->nullable();
        $table->timestamps();
    });

    config(['fb-auth.auth_type' => AuthType::Link]);
    config(['fb-user.remove_unattend_user_hours' => 48]);
    config(['fb-user.default_users_list_filter' => 'all']);
});

it('deletes unattended users without roles older than the configured hours', function () {
    $oldUser = User::factory()->create(['created_at' => now()->subDays(3)]);
    $recentUser = User::factory()->create(['created_at' => now()]);
    $role = Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
    $withRole = User::factory()->create(['created_at' => now()->subDays(3)]);
    $withRole->assignRole($role);

    (new RemoveUnAttendUsers)->handle();

    $this->assertSoftDeleted('users', ['id' => $oldUser->id]);
    $this->assertDatabaseHas('users', ['id' => $recentUser->id]);
    $this->assertDatabaseHas('users', ['id' => $withRole->id]);
});

it('keeps unattended users when the auth provider is not eloquent', function () {
    config([
        'auth.providers.users.driver' => 'database',
        'auth.providers.users.table' => 'users',
    ]);

    $user = User::factory()->create(['created_at' => now()->subDays(3)]);

    (new RemoveUnAttendUsers)->handle();

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

it('does not reset email_verified_at when email is not changed', function () {
    $user = User::factory()->create(['active' => true]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $user->first_name = 'Updated';
    $user->save();

    $this->assertNotNull($user->fresh()->email_verified_at);
});

it('resets email_verified_at when the unverifiable attribute changes', function () {
    $user = User::factory()->create(['active' => true]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $user->email = 'new-email@example.com';
    $user->save();

    $this->assertNull($user->fresh()->email_verified_at);
});

it('resets email_verified_at when a mobile based auth type is changed', function () {
    config(['fb-auth.auth_type' => AuthType::Mobile]);

    $user = User::factory()->create(['mobile' => '09120000000', 'active' => true]);
    $user->forceFill(['email_verified_at' => now()])->save();

    $user->mobile = '09121111111';
    $user->save();

    $this->assertNull($user->fresh()->email_verified_at);
});

it('cascade update assigns the role to the related user', function () {
    $user = User::factory()->create();
    $subject = CascadeSubject::create(['user_id' => $user->id]);
    Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);

    $subject->cascadeUpdate(['user' => 'nurse']);

    $this->assertTrue($user->fresh()->hasRole('nurse'));
});

it('cascade delete deletes the related user when it has the exact role', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
    $user->assignRole($role);
    $subject = CascadeSubject::create(['user_id' => $user->id]);

    $subject->cascadeDelete(['user' => 'nurse']);

    $this->assertSoftDeleted($user);
});

it('cascade delete only removes the role when the related user has more roles', function () {
    $user = User::factory()->create();
    $nurse = Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
    $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
    $user->assignRole([$nurse, $doctor]);
    $subject = CascadeSubject::create(['user_id' => $user->id]);

    $subject->cascadeDelete(['user' => 'nurse']);

    $this->assertFalse($user->fresh()->hasRole('nurse'));
    $this->assertTrue($user->fresh()->hasRole('doctor'));
});

it('cascade delete without a role deletes every related user', function () {
    $user = User::factory()->create();
    $subject = CascadeSubject::create(['user_id' => $user->id]);

    $subject->cascadeDelete('user');

    $this->assertSoftDeleted($user);
});

it('cascade restore restores the related user and reassigns the role', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
    $user->assignRole($role);
    $user->delete();
    $subject = CascadeSubject::create(['user_id' => $user->id]);
    $subject->delete();

    $subject->cascadeRestore(['user' => 'nurse']);

    $this->assertNotSoftDeleted($user);
    $this->assertTrue($user->fresh()->hasRole('nurse'));
});

it('cascade operations throw when the relation does not exist', function () {
    $subject = CascadeSubject::create();

    $subject->cascadeUpdate(['missing' => 'nurse']);
})->throws(InvalidArgumentException::class);

it('cascade operations throw when the relation role map is not associative', function () {
    $subject = CascadeSubject::create();

    $subject->cascadeUpdate([['nurse']]);
})->throws(InvalidArgumentException::class);

it('returns the causer name for the created_by attribute', function () {
    $causer = User::factory()->create();
    $post = Post::create();

    Activity::create([
        'subject_id' => $post->id,
        'causer_id' => $causer->id,
        'description' => 'created',
    ]);

    $this->assertSame($causer->name, $post->created_by);
});

it('returns the causer name for the updated_by attribute', function () {
    $causer = User::factory()->create();
    $post = Post::create();

    Activity::create([
        'subject_id' => $post->id,
        'causer_id' => $causer->id,
        'description' => 'updated',
    ]);

    $this->assertSame($causer->name, $post->updated_by);
});

it('returns null when no matching activity exists', function () {
    $post = Post::create();

    $this->assertNull($post->created_by);
    $this->assertNull($post->updated_by);
});

it('hides the no-role widget from users that already have a role', function () {
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'nurse', 'guard_name' => 'web']);
    $user->assignRole($role);

    $this->actingAs($user);

    $this->assertFalse(NoRoleWidget::canView());
});

it('shows the no-role widget to users without any role', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->assertTrue(NoRoleWidget::canView());
});

it('lists only active users by default when the filter is set to active', function () {
    config(['fb-user.default_users_list_filter' => 'active']);

    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $admin = User::factory()->create(['active' => true])->assignRole($role);
    $this->actingAs($admin);

    Gate::before(fn () => true);

    $activeUser = User::factory()->create(['active' => true]);
    $inactiveUser = User::factory()->create(['active' => false]);

    $this
        ->livewire(ListUsers::class)
        ->assertCanSeeTableRecords([$activeUser])
        ->assertCanNotSeeTableRecords([$inactiveUser]);
});

it('uses the configured model as the user model', function () {
    $this->assertTrue(is_subclass_of(config('auth.providers.users.model'), ModelsUser::class));
});
