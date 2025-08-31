<?php

namespace App\Console\Commands;

use App\Models\PckCredential;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class PckCredentialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pck:credentials
                           {action : Action to perform (list|create|update|disable|enable)}
                           {--tenant-id= : Tenant ID}
                           {--username= : PCK username}
                           {--password= : PCK password}
                           {--license= : PCK license number}
                           {--ip-whitelist=* : IP addresses to whitelist}';

    /**
     * The console command description.
     */
    protected $description = 'Manage PCK credentials for tenants';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listCredentials(),
            'create' => $this->createCredential(),
            'update' => $this->updateCredential(),
            'disable' => $this->disableCredential(),
            'enable' => $this->enableCredential(),
            default => $this->invalidAction($action),
        };
    }

    private function listCredentials(): int
    {
        $credentials = PckCredential::with(['avdeling', 'avdelingAlternative'])
            ->orderBy('tenant_id')
            ->get();

        if ($credentials->isEmpty()) {
            $this->info('No PCK credentials found.');
            return Command::SUCCESS;
        }

        $this->info('PCK Credentials:');
        $this->line('');

        $headers = ['Tenant ID', 'Username', 'License', 'Status', 'Last Seen', 'IP Whitelist'];
        $rows = [];

        foreach ($credentials as $credential) {
            $tenantName = $credential->avdeling->navn ?? 
                         $credential->avdelingAlternative->Navn ?? 
                         "Tenant {$credential->tenant_id}";

            $lastSeen = $credential->last_seen_at 
                ? $credential->last_seen_at->diffForHumans()
                : 'Never';

            $ipWhitelist = $credential->ip_whitelist 
                ? implode(', ', $credential->ip_whitelist)
                : 'All IPs allowed';

            $rows[] = [
                $credential->tenant_id . " ({$tenantName})",
                $credential->pck_username,
                $credential->pck_license,
                $credential->is_enabled ? '<info>Enabled</info>' : '<error>Disabled</error>',
                $lastSeen,
                $ipWhitelist,
            ];
        }

        $this->table($headers, $rows);
        return Command::SUCCESS;
    }

    private function createCredential(): int
    {
        $tenantId = $this->option('tenant-id');
        $username = $this->option('username');
        $password = $this->option('password');
        $license = $this->option('license');

        // Interactive mode if options not provided
        if (!$tenantId) {
            $tenantId = $this->ask('Enter tenant ID');
        }
        
        if (!$username) {
            $username = $this->ask('Enter PCK username');
        }
        
        if (!$password) {
            $password = $this->secret('Enter PCK password');
        }
        
        if (!$license) {
            $license = $this->ask('Enter PCK license number');
        }

        // Validation
        $validator = Validator::make([
            'tenant_id' => $tenantId,
            'username' => $username,
            'password' => $password,
            'license' => $license,
        ], [
            'tenant_id' => 'required|integer|min:1',
            'username' => 'required|string|min:3|max:100',
            'password' => 'required|string|min:8',
            'license' => 'required|string',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  - {$error}");
            }
            return Command::FAILURE;
        }

        // Check for existing credential
        $existing = PckCredential::where('tenant_id', $tenantId)
            ->where('pck_username', $username)
            ->first();

        if ($existing) {
            $this->error("Credential already exists for tenant {$tenantId} with username {$username}");
            return Command::FAILURE;
        }

        // Get IP whitelist
        $ipWhitelist = $this->option('ip-whitelist');
        if (empty($ipWhitelist) && $this->confirm('Do you want to set IP whitelist?', false)) {
            $ipInput = $this->ask('Enter IP addresses (comma-separated)');
            if ($ipInput) {
                $ipWhitelist = array_map('trim', explode(',', $ipInput));
            }
        }

        // Create credential
        $credential = PckCredential::create([
            'tenant_id' => $tenantId,
            'pck_username' => $username,
            'pck_password' => $password,
            'pck_license' => $license,
            'wsdl_version' => '1.98',
            'is_enabled' => true,
            'ip_whitelist' => $ipWhitelist ?: null,
        ]);

        $this->info("PCK credential created successfully (ID: {$credential->id})");
        return Command::SUCCESS;
    }

    private function updateCredential(): int
    {
        $tenantId = $this->option('tenant-id');
        $username = $this->option('username');

        if (!$tenantId || !$username) {
            $this->error('Both --tenant-id and --username are required for update');
            return Command::FAILURE;
        }

        $credential = PckCredential::where('tenant_id', $tenantId)
            ->where('pck_username', $username)
            ->first();

        if (!$credential) {
            $this->error("Credential not found for tenant {$tenantId} with username {$username}");
            return Command::FAILURE;
        }

        $updates = [];

        // Update password if provided
        if ($password = $this->option('password')) {
            $updates['pck_password'] = $password;
        } elseif ($this->confirm('Update password?', false)) {
            $password = $this->secret('Enter new PCK password');
            if ($password) {
                $updates['pck_password'] = $password;
            }
        }

        // Update license if provided
        if ($license = $this->option('license')) {
            $updates['pck_license'] = $license;
        } elseif ($this->confirm('Update license?', false)) {
            $license = $this->ask('Enter new PCK license number', $credential->pck_license);
            if ($license !== $credential->pck_license) {
                $updates['pck_license'] = $license;
            }
        }

        // Update IP whitelist if provided
        $ipWhitelist = $this->option('ip-whitelist');
        if (!empty($ipWhitelist)) {
            $updates['ip_whitelist'] = $ipWhitelist;
        } elseif ($this->confirm('Update IP whitelist?', false)) {
            $currentIps = $credential->ip_whitelist 
                ? implode(', ', $credential->ip_whitelist)
                : '';
            
            $ipInput = $this->ask('Enter IP addresses (comma-separated, empty for no restrictions)', $currentIps);
            
            if ($ipInput === '') {
                $updates['ip_whitelist'] = null;
            } else {
                $updates['ip_whitelist'] = array_map('trim', explode(',', $ipInput));
            }
        }

        if (empty($updates)) {
            $this->info('No changes made.');
            return Command::SUCCESS;
        }

        $credential->update($updates);
        $this->info('PCK credential updated successfully');

        return Command::SUCCESS;
    }

    private function disableCredential(): int
    {
        return $this->toggleCredential(false);
    }

    private function enableCredential(): int
    {
        return $this->toggleCredential(true);
    }

    private function toggleCredential(bool $enabled): int
    {
        $tenantId = $this->option('tenant-id');
        $username = $this->option('username');

        if (!$tenantId || !$username) {
            $this->error('Both --tenant-id and --username are required');
            return Command::FAILURE;
        }

        $credential = PckCredential::where('tenant_id', $tenantId)
            ->where('pck_username', $username)
            ->first();

        if (!$credential) {
            $this->error("Credential not found for tenant {$tenantId} with username {$username}");
            return Command::FAILURE;
        }

        $credential->update(['is_enabled' => $enabled]);
        
        $status = $enabled ? 'enabled' : 'disabled';
        $this->info("PCK credential {$status} successfully");

        return Command::SUCCESS;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}");
        $this->line('Available actions: list, create, update, disable, enable');
        return Command::FAILURE;
    }
}