<?php

use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Testing\TestAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Mortezamasumi\FbUser\Resources\Exports\UserExporter;
use Mortezamasumi\FbUser\Resources\Pages\CreateUser;
use Mortezamasumi\FbUser\Resources\Pages\EditUser;
use Mortezamasumi\FbUser\Resources\Pages\ListUsers;
use Mortezamasumi\FbUser\Resources\UserResource;
use Mortezamasumi\FbUser\Tests\Services\User;
use Spatie\Permission\Models\Role;

describe('as guest/un-authorized user', function () {
    beforeEach(function () {
        $this->user = User::factory()
            ->create()
            ->assignRole(Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']));
    });

    it('guests cannot access the resource', function () {
        $this
            ->get(UserResource::getUrl('index'))
            ->assertRedirect(config('filament.auth.pages.login'));

        $this
            ->get(UserResource::getUrl('create'))
            ->assertRedirect(config('filament.auth.pages.login'));

        $this
            ->get(UserResource::getUrl('edit', ['record' => $this->user]))
            ->assertRedirect(config('filament.auth.pages.login'));
    });

    it('un-authorized users cannot access the resource', function () {
        $this->actingAs($this->user);

        $this
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();

        $this
            ->get(UserResource::getUrl('create'))
            ->assertForbidden();

        $this
            ->get(UserResource::getUrl('edit', ['record' => $this->user]))
            ->assertForbidden();
    });
});

describe('as authorized user', function () {
    beforeEach(function () {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create()->assignRole($role);

        $this->actingAs($this->adminUser);

        Gate::before(fn () => true);
    });

    it('can render the list page', function () {
        $this
            ->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can list users', function () {
        $users = User::factory(3)->create();

        $this
            ->livewire(ListUsers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($users);
    });

    it('can search users by `name` or `email`', function () {
        $users = User::factory()->count(5)->create();

        $this
            ->livewire(ListUsers::class)
            ->assertCanSeeTableRecords($users)
            ->searchTable($users->first()->first_name)
            ->assertCanSeeTableRecords($users->take(1))
            ->assertCanNotSeeTableRecords($users->skip(1))
            ->searchTable($users->last()->email)
            ->assertCanSeeTableRecords($users->take(-1))
            ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
    });

    it('can sort users by `last_name`', function () {
        User::factory()->count(3)->create();

        $this
            ->livewire(ListUsers::class)
            ->sortTable('last_name')
            ->assertCanSeeTableRecords(User::oldest('last_name')->get(), inOrder: true)
            ->sortTable('last_name', 'desc')
            ->assertCanSeeTableRecords(User::latest('last_name')->get(), inOrder: true);
    });

    it('can filter users by `active_users` and `role', function () {
        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        User::factory()
            ->count(3)
            ->afterCreating(function ($user) use ($role) {
                $user->assignRole($role);
            })
            ->create();

        User::factory()->count(2)->create(['active' => false]);

        $users = User::all();

        $this
            ->livewire(ListUsers::class)
            ->assertCanSeeTableRecords($users)
            ->filterTable('active_users', true)
            ->assertCanSeeTableRecords($users->where('active', true))
            ->assertCanNotSeeTableRecords($users->where('active', false))
            ->removeTableFilter('active_users')
            ->filterTable('roles', $role->id)
            ->assertCanSeeTableRecords($users->filter(fn ($user) => $user->roles->contains($role->id)))
            ->assertCanNotSeeTableRecords($users->filter(fn ($user) => ! $user->roles->contains($role->id)));
    });

    it('can toggle active on table', function () {
        $user = User::factory()->create();

        $this
            ->livewire(ListUsers::class)
            ->assertTableColumnExists('active')
            ->assertTableColumnStateSet('active', true, $user)
            ->callTableColumnAction('active', $user, ['state' => 0])
            ->assertHasNoTableActionErrors();

        $user->refresh();
        // expect($user->active)->toBe(0);
    });

    it('can soft delete a user from the list page record action', function () {
        $user = User::factory()->create();

        $this
            ->livewire(ListUsers::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($user))
            ->assertHasNoTableActionErrors();

        $this->assertSoftDeleted($user);
    });

    it('can restore a user from the list page record action', function () {
        $user = User::factory()->create(['deleted_at' => now()]);

        $this
            ->livewire(ListUsers::class)
            ->filterTable(TrashedFilter::class, true)
            ->callAction(TestAction::make(RestoreAction::class)->table($user))
            ->assertHasNoTableActionErrors();

        $this->assertNotSoftDeleted($user);
    });

    it('can force delete a user from the list page record action', function () {
        $user = User::factory()->create();

        $this
            ->livewire(ListUsers::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($user))
            ->filterTable(TrashedFilter::class, true)
            ->callAction(TestAction::make(ForceDeleteAction::class)->table($user))
            ->assertHasNoTableActionErrors();

        $this->assertModelMissing($user);
    });

    it('can bulk delete users from the list page', function () {
        $usersToDelete = User::factory(3)->create();

        $this
            ->livewire(ListUsers::class)
            ->assertCanSeeTableRecords($usersToDelete)
            ->selectTableRecords($usersToDelete)
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertNotified()
            ->assertCanNotSeeTableRecords($usersToDelete);

        foreach ($usersToDelete as $user) {
            $this->assertSoftDeleted($user);
        }
    });

    it('can bulk activ/deactive users from the list page', function () {
        $users = User::factory(3)->create();

        $this
            ->livewire(ListUsers::class)
            ->assertCanSeeTableRecords($users)
            ->selectTableRecords($users)
            ->callAction(TestAction::make('active-deactive-users')->table()->bulk(), ['active' => 0])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        foreach ($users as $user) {
            $user->refresh();
            expect($user->active)->toBe(0);
        }
    });

    it('can render the create page', function () {
        $this
            ->get(UserResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can create a user with valid data', function () {
        $role = Role::firstOrCreate(['name' => 'test', 'guard_name' => 'web']);

        $newUserData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$role->id],
            'profile.some_data' => 'test',
        ];

        $this
            ->livewire(CreateUser::class)
            ->fillForm($newUserData)
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

        $user = User::whereEmail('test@example.com')->first();
        $this->assertNotNull($user, 'User was not created.');
        $this->assertEquals('test', $user->profile['some_data']);

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => User::whereEmail('test@example.com')->first()->id,
        ]);
    });

    it('cannot create a user with invalid data (validation)', function () {
        $this
            ->livewire(CreateUser::class)
            ->fillForm([
                'first_name' => '',
                'email' => '',
                'password' => '',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'first_name' => 'required',
                'email' => 'required',
                'password' => 'required',
                'roles' => 'required',
            ])
            ->fillForm([
                'password' => 'password123',
                'password_confirmation' => 'mismatch',
            ])
            ->call('create')
            ->assertHasFormErrors(['password' => 'same']);
    });

    it('can render the edit page', function () {
        $user = User::factory()->create();

        $this
            ->get(UserResource::getUrl('edit', ['record' => $user]))
            ->assertSuccessful();
    });

    it('can load existing data into the edit form', function () {
        $user = User::factory()->create();

        $this
            ->livewire(EditUser::class, [
                'record' => $user->getRouteKey(),
            ])
            ->assertSchemaStateSet([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ]);
    });

    it('can update a user', function () {
        $role = Role::firstOrCreate(['name' => 'test', 'guard_name' => 'web']);

        $user = User::factory()->create();

        $this
            ->livewire(EditUser::class, [
                'record' => $user->getRouteKey(),
            ])
            ->fillForm([
                'first_name' => 'Updated Name',
                'last_name' => 'Updated Last',
                'email' => 'updated@example.com',
                'roles' => [$role->id],
                'profile.some_data' => 'test',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    });

    it('does not update password when password fields are empty', function () {
        $role = Role::firstOrCreate(['name' => 'test', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $originalPassword = $user->password;

        $this
            ->livewire(EditUser::class, [
                'record' => $user->getRouteKey(),
            ])
            ->fillForm([
                'first_name' => 'Updated Name',
                'password' => '',
                'password_confirmation' => '',
                'roles' => [$role->id],
                'profile.some_data' => 'test',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        expect($user->password)->toBe($originalPassword);
        expect($user->first_name)->toBe('Updated Name');
    });

    it('can delete a user from the edit page', function () {
        $user = User::factory()->create();

        $this
            ->livewire(EditUser::class, [
                'record' => $user->getRouteKey(),
            ])
            ->callAction(DeleteAction::class)
            ->assertHasNoActionErrors();

        $this->assertSoftDeleted($user);
    });

    it('can export users', function () {
        $users = User::factory(3)->create();

        $this
            ->livewire(ListUsers::class)
            ->callAction('export-users')
            ->assertHasNoActionErrors();
    });

    it('can export users and verify downloaded csv file', function () {
        $count = 5;

        $users = User::factory($count)->create();

        $this
            ->actingAs(User::factory()->create())
            ->livewire(ListUsers::class)
            ->callAction('export-users')
            ->assertNotified();

        $export = Export::latest()->first();

        expect($export)
            ->not
            ->toBeNull()
            ->processed_rows
            ->toBe($count + 1)
            ->successful_rows
            ->toBe($count + 1)
            ->completed_at
            ->not
            ->toBeNull();

        $this
            ->get(route(
                'filament.exports.download',
                ['export' => $export, 'format' => 'csv'],
                absolute: false
            ))
            ->assertDownload()
            ->tap(function ($response) use ($users) {
                $content = $response->streamedContent();

                foreach (collect(UserExporter::getColumns())->map(fn ($column) => $column->getLabel()) as $label) {
                    expect($content)
                        ->toContain($label);
                };

                foreach ($users as $user) {
                    expect($content)
                        ->toContain($user->first_name)
                        ->toContain($user->first_name)
                        ->toContain($user->email);
                }
            });
    });

    it('can import users from a csv file', function () {
        Storage::fake('public');

        $fixturePath = __DIR__.'/../Services/users_import.csv';

        $csvContent = file_get_contents($fixturePath);

        $csvContent = trim($csvContent);

        $fakeFile = UploadedFile::fake()->createWithContent(
            'import.csv',
            $csvContent
        );

        $this
            ->livewire(ListUsers::class)
            ->callAction('import-users', [
                'file' => $fakeFile,
                'createMissedRoles' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'testRole',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'first_name' => 'Another',
            'email' => 'another@example.com',
        ]);
    });

    it('validate missing roles in import file', function () {
        Storage::fake('public');

        $fakeFile = UploadedFile::fake()->createWithContent(
            'import.csv',
            'a-title'
        );

        $this
            ->livewire(ListUsers::class)
            ->callAction('import-users', ['file' => $fakeFile])
            ->assertHasActionErrors();
    });

    it('validates the import file type', function () {
        Storage::fake('public');

        $fakeImage = UploadedFile::fake()->image('not-a-csv.jpg');

        $this
            ->livewire(ListUsers::class)
            ->callAction('import-users', [
                'file' => $fakeImage,
                'createMissedRoles' => true,
            ])
            ->assertHasActionErrors(['file']);
    });
});
