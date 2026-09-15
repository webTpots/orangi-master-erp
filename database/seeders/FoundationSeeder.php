<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        // ── Organization ──
        $orgId = DB::table('organizations')->insertGetId([
            'name' => 'Orangi',
            'slug' => 'orangi',
            'settings' => json_encode(['timezone' => 'Asia/Kolkata', 'currency' => 'INR']),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Company ──
        $companyId = DB::table('companies')->insertGetId([
            'organization_id' => $orgId,
            'name' => 'ORANGI',
            'legal_name' => 'KRISHNA SUNNY SHINGALA',
            'gstin' => '24CAOPB2618F1Z2',
            'address' => 'SHOP NO 134 Soham Arcade Gaurav Path Road',
            'city' => 'Surat',
            'state' => 'Gujarat',
            'pincode' => '395009',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Warehouse ──
        $warehouseId = DB::table('warehouses')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'address' => 'Soham Arcade, Gaurav Path Road, Adajan',
            'city' => 'Surat',
            'state' => 'Gujarat',
            'pincode' => '395009',
            'is_default' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Marketplaces ──
        DB::table('marketplaces')->insert([
            [
                'company_id' => $companyId,
                'name' => 'Meesho',
                'code' => 'meesho',
                'is_active' => true,
                'config' => json_encode([
                    'cutoff_time' => '11:00',
                    'sla_days' => 2,
                    'label_format' => 'pdf',
                    'manifest_format' => 'pdf',
                    'settlement_format' => 'xlsx',
                    'return_window_days' => 7,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $companyId,
                'name' => 'Flipkart',
                'code' => 'flipkart',
                'is_active' => true,
                'config' => json_encode([
                    'cutoff_time' => '12:00',
                    'sla_days' => 2,
                    'label_format' => 'pdf',
                    'settlement_format' => 'xlsx',
                    'return_window_days' => 10,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ── Roles & Permissions ──
        $roles = [
            'super_admin' => 'Super Admin',
            'business_owner' => 'Business Owner',
            'operations_manager' => 'Operations Manager',
            'purchase_manager' => 'Purchase Manager',
            'warehouse_manager' => 'Warehouse Manager',
            'finance' => 'Finance',
            'accounts' => 'Accounts',
            'qa' => 'QA',
            'viewer' => 'Viewer',
        ];

        foreach ($roles as $slug => $name) {
            Role::firstOrCreate(['name' => $slug]);
        }

        $permissions = [
            // Core
            'company.manage', 'warehouse.manage', 'user.manage', 'settings.manage',
            // Product
            'product.view', 'product.create', 'product.edit', 'product.delete', 'product.export',
            'sku.map', 'sku.approve_mapping',
            // Vendor
            'vendor.view', 'vendor.create', 'vendor.edit',
            'po.view', 'po.create', 'po.approve', 'po.receive',
            // Inventory
            'inventory.view', 'inventory.adjust', 'inventory.count', 'inventory.opening_stock',
            'inventory.export', 'inventory.reconcile',
            // Orders
            'order.view', 'order.process', 'order.cancel',
            'label.upload', 'label.view', 'label.print',
            'manifest.upload', 'manifest.view', 'manifest.reconcile',
            'scan.perform', 'scan.view',
            // Returns
            'return.view', 'return.inspect', 'return.process',
            'claim.view', 'claim.create', 'claim.resolve',
            // Financial
            'payment.view', 'payment.upload', 'payment.reconcile',
            'gst.view', 'gst.upload', 'gst.reconcile',
            'bank.view', 'bank.upload', 'bank.reconcile',
            'financial.view', 'financial.export',
            // Reports
            'report.view', 'report.export', 'report.financial',
            // Import
            'import.upload', 'import.approve',
            // System
            'exception.view', 'exception.resolve',
            'audit.view',
            'notification.manage',
            'period.lock', 'period.unlock',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Assign all permissions to super_admin
        Role::findByName('super_admin')->syncPermissions($permissions);

        // Business owner gets almost everything
        Role::findByName('business_owner')->syncPermissions(
            array_filter($permissions, fn ($p) => !in_array($p, ['company.manage', 'user.manage', 'period.unlock']))
        );

        // Operations
        Role::findByName('operations_manager')->syncPermissions([
            'product.view', 'product.create', 'product.edit', 'sku.map',
            'vendor.view', 'po.view', 'po.create',
            'inventory.view', 'inventory.adjust',
            'order.view', 'order.process', 'order.cancel',
            'label.upload', 'label.view', 'label.print',
            'manifest.upload', 'manifest.view', 'manifest.reconcile',
            'scan.perform', 'scan.view',
            'return.view', 'return.inspect', 'return.process',
            'report.view', 'report.export',
            'import.upload',
            'exception.view', 'exception.resolve',
        ]);

        // ── Admin User ──
        $userId = DB::table('users')->insertGetId([
            'name' => 'Sunny',
            'email' => 'sunny@tpots.co',
            'email_verified_at' => now(),
            'phone' => '8511000586',
            'password' => Hash::make('roy110394'),
            'organization_id' => $orgId,
            'company_id' => $companyId,
            'role' => 'super_admin',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign role
        $user = \App\Models\User::find($userId);
        $user->assignRole('super_admin');

        // ── Financial Periods ──
        for ($m = 1; $m <= 12; $m++) {
            DB::table('financial_periods')->insert([
                'company_id' => $companyId,
                'year' => 2026,
                'month' => $m,
                'start_date' => "2026-{$m}-01",
                'end_date' => date('Y-m-t', strtotime("2026-{$m}-01")),
                'status' => $m < 9 ? 'closed' : 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
