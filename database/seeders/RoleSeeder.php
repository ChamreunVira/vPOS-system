<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Full access to every module'],
            ['name' => 'manager', 'description' => 'Runs the shop day to day'],
            ['name' => 'cashier', 'description' => 'Works the checkout counter'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [...$role, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        $modules = ['Dashboard', 'POS / Checkout', 'Products', 'Categories', 'Inventory', 'Orders & Refunds', 'Customers', 'Expenses', 'Payments', 'Reports', 'Employees', 'Settings'];
        $actions = ['view', 'create', 'update', 'delete'];
        $grants = [
            'Dashboard' => [$actions, ['view'], []],
            'POS / Checkout' => [$actions, ['view', 'create'], ['view', 'create']],
            'Products' => [$actions, ['view', 'create', 'update'], ['view']],
            'Categories' => [$actions, ['view', 'create', 'update'], ['view']],
            'Inventory' => [$actions, ['view', 'create', 'update'], ['view']],
            'Orders & Refunds' => [$actions, ['view', 'update'], ['view']],
            'Customers' => [$actions, ['view', 'create', 'update'], ['view', 'create']],
            'Expenses' => [$actions, ['view', 'create', 'update'], []],
            'Payments' => [$actions, ['view'], []],
            'Reports' => [$actions, ['view'], []],
            'Employees' => [$actions, ['view'], []],
            'Settings' => [$actions, [], []],
        ];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                DB::table('permissions')->updateOrInsert(
                    ['module' => $module, 'action' => $action],
                    ['updated_at' => now(), 'created_at' => now()],
                );
            }
        }

        DB::table('role_permissions')->delete();

        foreach ($roles as $roleIndex => $role) {
            $roleId = DB::table('roles')->where('name', $role['name'])->value('id');

            foreach ($modules as $module) {
                foreach ($grants[$module][$roleIndex] as $action) {
                    $permissionId = DB::table('permissions')
                        ->where('module', $module)
                        ->where('action', $action)
                        ->value('id');

                    DB::table('role_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
