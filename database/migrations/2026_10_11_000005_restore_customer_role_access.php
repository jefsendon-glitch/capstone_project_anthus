<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'customer')->where('guard_name', 'web')->first();

        if (! $role) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'customer',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $roleId = $role->id;
        }

        $userIds = DB::table('users as users')
            ->leftJoin('model_has_roles as roles', function ($join): void {
                $join->on('roles.model_id', '=', 'users.id')
                    ->where('roles.model_type', User::class);
            })
            ->whereNull('users.deleted_at')
            ->whereNull('roles.role_id')
            ->pluck('users.id');

        foreach ($userIds as $userId) {
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => User::class,
                'model_id' => $userId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Assignments may have been changed by an administrator after migration.
    }
};
