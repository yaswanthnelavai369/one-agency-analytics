<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Free', 'slug' => 'free', 'price_monthly' => 0, 'price_yearly' => 0,
                'client_limit' => 1, 'user_limit' => 2, 'project_limit' => 1, 'storage_limit_mb' => 500,
                'report_limit_monthly' => 5, 'export_limit_monthly' => 5, 'ai_credit_limit_monthly' => 20,
                'api_call_limit_monthly' => 1000, 'integration_limit' => 2, 'support_level' => 'community',
                'branding_allowed' => false, 'custom_domain_allowed' => false, 'sort_order' => 1],

            ['name' => 'Starter', 'slug' => 'starter', 'price_monthly' => 29, 'price_yearly' => 290,
                'client_limit' => 5, 'user_limit' => 5, 'project_limit' => 10, 'storage_limit_mb' => 5000,
                'report_limit_monthly' => 50, 'export_limit_monthly' => 50, 'ai_credit_limit_monthly' => 200,
                'api_call_limit_monthly' => 10000, 'integration_limit' => 5, 'support_level' => 'email',
                'branding_allowed' => false, 'custom_domain_allowed' => false, 'sort_order' => 2],

            ['name' => 'Professional', 'slug' => 'professional', 'price_monthly' => 99, 'price_yearly' => 990,
                'client_limit' => 20, 'user_limit' => 15, 'project_limit' => 50, 'storage_limit_mb' => 25000,
                'report_limit_monthly' => 250, 'export_limit_monthly' => 250, 'ai_credit_limit_monthly' => 1000,
                'api_call_limit_monthly' => 50000, 'integration_limit' => 15, 'support_level' => 'priority',
                'branding_allowed' => true, 'custom_domain_allowed' => false, 'sort_order' => 3],

            ['name' => 'Agency', 'slug' => 'agency', 'price_monthly' => 249, 'price_yearly' => 2490,
                'client_limit' => 75, 'user_limit' => 50, 'project_limit' => null, 'storage_limit_mb' => 100000,
                'report_limit_monthly' => null, 'export_limit_monthly' => null, 'ai_credit_limit_monthly' => 5000,
                'api_call_limit_monthly' => 250000, 'integration_limit' => null, 'support_level' => 'priority',
                'branding_allowed' => true, 'custom_domain_allowed' => true, 'sort_order' => 4],

            ['name' => 'Enterprise', 'slug' => 'enterprise', 'price_monthly' => 0, 'price_yearly' => 0,
                'client_limit' => null, 'user_limit' => null, 'project_limit' => null, 'storage_limit_mb' => null,
                'report_limit_monthly' => null, 'export_limit_monthly' => null, 'ai_credit_limit_monthly' => null,
                'api_call_limit_monthly' => null, 'integration_limit' => null, 'support_level' => 'dedicated',
                'branding_allowed' => true, 'custom_domain_allowed' => true, 'sort_order' => 5,
                'description' => 'Custom pricing — contact sales.'],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge(['uuid' => Str::uuid(), 'is_active' => true], $plan)
            );
        }
    }
}
