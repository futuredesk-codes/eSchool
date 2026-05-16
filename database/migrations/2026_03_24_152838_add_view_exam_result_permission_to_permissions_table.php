<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        // 1. Insert permission if not exists
        $permissionId = DB::table('permissions')
            ->where('name', 'view-exam-result')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'view-exam-result',
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. Assign permission to roles 1 and 2 (if they exist)
        $roles = [1, 2];

        foreach ($roles as $roleId) {
            $roleExists = DB::table('roles')->where('id', $roleId)->exists();

            // Skip on fresh installation — role won't exist yet and the seeder handles
            if ($roleExists) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // 1. Get permission id
        $permissionId = DB::table('permissions')
            ->where('name', 'view-exam-result')
            ->where('guard_name', 'web')
            ->value('id');

        if ($permissionId) {
            // 2. Remove from roles 1 and 2
            DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->whereIn('role_id', [1, 2])
                ->delete();

            // 3. Delete permission
            DB::table('permissions')
                ->where('id', $permissionId)
                ->delete();
        }
    }
};
