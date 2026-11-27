<?php

namespace App\Console\Commands;

use App\Support\Access;
use App\Support\AdministratorPassword;
use App\Support\AdministratorSession;
use App\Support\WriteTransaction;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateAdministrator extends Command
{
    protected $signature = 'admin:create {--from-stdin : Read mobile, name, password and confirmation from four input lines}';

    protected $description = 'Provision an administrator with the full permission catalog';

    public function handle(): int
    {
        $fromStdin = $this->option('from-stdin');
        $mobile = AdministratorSession::canonicalMobile($this->readValue($fromStdin, 'Mobile') ?? '');
        $name = trim($this->readValue($fromStdin, 'Display name') ?? '');
        $password = $this->readValue($fromStdin, 'Password', secret: true);
        $confirmation = $this->readValue($fromStdin, 'Repeat password', secret: true);

        if ($mobile === null || $name === '' || ! mb_check_encoding($name, 'UTF-8') || mb_strlen($name, 'UTF-8') > 120) {
            $this->error('Use a canonical Iranian mobile number and a display name of 1–120 characters.');

            return self::FAILURE;
        }
        if (! AdministratorPassword::valid($password) || $password !== $confirmation) {
            $this->error(AdministratorPassword::POLICY.' Enter the same password twice.');

            return self::FAILURE;
        }

        try {
            $created = WriteTransaction::run(function () use ($mobile, $name, $password): bool {
                if (DB::table('administrators')->where('mobile', $mobile)->exists()) {
                    return false;
                }

                $id = (string) Str::uuid();
                DB::table('administrators')->insert([
                    'id' => $id,
                    'mobile' => $mobile,
                    'name' => $name,
                    'password_hash' => AdministratorPassword::hash($password),
                    'security_stamp' => (string) Str::uuid(),
                    'permissions' => '[]',
                ]);

                DB::table('administrator_roles')->insert([
                    'administrator_id' => $id,
                    'role_id' => $this->fullAccessRole(),
                ]);
                Access::audit(null, 'user.provisioned', $id, ['roleCount' => 1]);

                return true;
            });
            if (! $created) {
                $this->error('An administrator already uses this mobile number.');

                return self::FAILURE;
            }

        } catch (QueryException) {
            $this->error('The administrator could not be stored. Verify the database and try again.');

            return self::FAILURE;
        }

        $this->info('Administrator created.');

        return self::SUCCESS;
    }

    private function fullAccessRole(): string
    {
        $permissions = array_column(Access::catalog(), 'id');
        foreach (DB::table('roles')->orderBy('sort_order')->get() as $role) {
            if (array_diff($permissions, Access::roleRecord($role)['permissions']) === []) {
                return $role->id;
            }
        }

        $id = (string) Str::uuid();
        $name = 'System administrator '.$id;
        DB::table('roles')->insert([
            'id' => $id,
            'name' => $name,
            'normalized_name' => mb_strtoupper($name, 'UTF-8'),
            'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR),
            'sort_order' => (DB::table('roles')->max('sort_order') ?? -1) + 1,
        ]);

        return $id;
    }

    private function readValue(bool $fromStdin, string $label, bool $secret = false): ?string
    {
        if ($fromStdin) {
            $line = fgets(STDIN, 513);

            return $line === false || strlen($line) === 512 ? null : rtrim($line, "\r\n");
        }

        return $secret ? $this->secret($label, fallback: false) : $this->ask($label);
    }
}
