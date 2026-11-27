<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AdministratorSession
{
    private const KEY = 'bearstudio.administrator';

    private const ABSOLUTE_SECONDS = 8 * 60 * 60;

    private const INACTIVITY_SECONDS = 15 * 60;

    private const MAX_ACCOUNT_FAILURES = 5;

    public static function canonicalMobile(string $mobile): ?string
    {
        if (mb_strlen($mobile, 'UTF-8') > 64) {
            return null;
        }

        $mobile = strtr(preg_replace('/^\s+|\s+$/u', '', $mobile) ?? '', [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_match('/\A9[0-9]{9}\z/', $mobile) === 1 ? $mobile : null;
    }

    public function requireCsrf(Request $request): void
    {
        $token = $request->header('X-CSRF-TOKEN');
        if (! is_string($token) || ! hash_equals($request->session()->token(), $token)) {
            ApiError::abort(400, 'CSRF_VALIDATION_FAILED', 'Fetch a fresh request token and try again.');
        }
    }

    public function clearInvalidSession(Request $request): void
    {
        $this->current($request, touch: false);
    }

    public function require(Request $request, ?string $permission = null, bool $write = false): array
    {
        $administrator = $this->current($request, touch: true);
        if ($administrator === null) {
            ApiError::abort(401, 'UNAUTHENTICATED', 'Sign in to continue.');
        }

        if ($permission !== null && ! in_array($permission, $administrator['permissions'], true)) {
            ApiError::abort(403, 'FORBIDDEN', 'You do not have permission for this operation.');
        }

        if ($write) {
            $this->requireCsrf($request);
        }

        return $administrator;
    }

    public function requireAccessWrite(Request $request, string $permission): array
    {
        $actor = $this->require($request, $permission, write: true);
        $issuedAt = DB::table('administrator_sessions')->where('id', $request->session()->getId())->value('issued_at');
        if ($issuedAt === null || $issuedAt <= time() - self::INACTIVITY_SECONDS) {
            ApiError::abort(403, 'REAUTH_REQUIRED', 'Sign in again to manage access.');
        }

        return $actor;
    }

    public function signIn(Request $request, string $mobile, string $password): bool
    {
        return WriteTransaction::run(function () use ($request, $mobile, $password): bool {
            $now = time();
            $administrator = DB::table('administrators')->where('mobile', $mobile)->first();
            if ($administrator === null) {
                // Give an unknown account the same password-work cost as a known account.
                Hash::driver('bcrypt')->make(bin2hex(random_bytes(32)));

                return false;
            }

            $passwordMatches = AdministratorPassword::verify($password, $administrator->password_hash);
            if (! $administrator->is_active || $administrator->locked_until > $now) {
                return false;
            }

            $savedAdministrator = DB::table('administrators')->where('id', $administrator->id);
            if (! $passwordMatches) {
                $failures = $administrator->locked_until === null ? $administrator->failed_attempts + 1 : 1;
                $savedAdministrator->update([
                    'failed_attempts' => $failures,
                    'locked_until' => $failures >= self::MAX_ACCOUNT_FAILURES ? $now + self::INACTIVITY_SECONDS : null,
                ]);

                return false;
            }

            $savedAdministrator->update(['failed_attempts' => 0, 'locked_until' => null]);
            $this->forget($request);
            DB::table('administrator_sessions')->where('expires_at', '<=', $now)->delete();
            DB::table('administrator_sessions')->insert([
                'id' => $request->session()->getId(),
                'administrator_id' => $administrator->id,
                'security_stamp' => $administrator->security_stamp,
                'issued_at' => $now,
                'last_activity_at' => $now,
                'expires_at' => $now + self::ABSOLUTE_SECONDS,
            ]);
            $request->session()->put(self::KEY, true);

            return true;
        });
    }

    public function signOut(Request $request): void
    {
        WriteTransaction::run(function () use ($request): void {
            $this->require($request, write: true);
            $this->forget($request);
        });
    }

    private function current(Request $request, bool $touch): ?array
    {
        $session = $request->session();
        if (! $session->has(self::KEY)) {
            return null;
        }

        $now = time();
        $administrator = DB::table('administrator_sessions as ticket')
            ->join('administrators as admin', 'admin.id', '=', 'ticket.administrator_id')
            ->where('ticket.id', $session->getId())
            ->where('ticket.expires_at', '>', $now)
            ->where('ticket.last_activity_at', '>', $now - self::INACTIVITY_SECONDS)
            ->whereColumn('ticket.security_stamp', 'admin.security_stamp')
            ->where('admin.is_active', true)
            ->first(['admin.id', 'admin.mobile', 'admin.name']);

        if ($administrator === null) {
            $this->forget($request);

            return null;
        }

        if ($touch) {
            DB::table('administrator_sessions')->where('id', $session->getId())
                ->where('last_activity_at', '<', $now)
                ->update(['last_activity_at' => $now]);
        }

        $roles = Access::memberships($administrator->id);

        return [
            'id' => $administrator->id,
            'mobile' => $administrator->mobile,
            'name' => $administrator->name,
            'roles' => array_map(static fn ($role) => ['id' => $role->id, 'name' => $role->name], $roles),
            'permissions' => Access::effectivePermissions(array_column($roles, 'permissions')),
        ];
    }

    private function forget(Request $request): void
    {
        DB::table('administrator_sessions')->where('id', $request->session()->getId())->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
