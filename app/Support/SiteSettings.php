<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use stdClass;

final class SiteSettings
{
    public const PALETTE_TOKENS = [
        'colorBgBase' => 'background',
        'colorBgContainer' => 'card',
        'colorBorder' => 'border',
        'colorLink' => 'link',
        'colorPrimary' => 'ring',
        'colorTextBase' => 'foreground',
        'colorTextDescription' => 'muted-foreground',
    ];

    private const LIGHT_DEFAULTS = ['#eeece6', '#e0dfd7', '#23251f', '#23251f', '#a97621', '#23251f', '#62665b'];

    private const DARK_DEFAULTS = ['#191b18', '#242821', '#e8e8df', '#e8e8df', '#edbd60', '#e8e8df', '#a3a79a'];

    public static function current(): stdClass
    {
        return DB::table('app_settings')->where('id', 1)->first()
            ?? ApiError::abort(503, 'STORAGE_UNAVAILABLE', 'Site settings are unavailable.');
    }

    public static function profiles(): array
    {
        return self::decode(self::current()->image_profiles_json);
    }

    public static function publicRecord(?stdClass $row = null): array
    {
        $row ??= self::current();

        return [
            'site' => self::decode($row->site_json),
            'lightTheme' => self::decode($row->light_theme_json),
            'darkTheme' => self::decode($row->dark_theme_json),
            'lightLogo' => self::logoRecord($row->light_logo_id),
            'darkLogo' => self::logoRecord($row->dark_logo_id),
            'profiles' => self::decode($row->image_profiles_json),
        ];
    }

    public static function adminRecord(): array
    {
        $row = self::current();

        return self::publicRecord($row) + [
            'lightLogoId' => $row->light_logo_id,
            'darkLogoId' => $row->dark_logo_id,
        ];
    }

    public static function themeStyle(array $settings): string
    {
        $style = '';
        foreach (['light' => self::LIGHT_DEFAULTS, 'dark' => self::DARK_DEFAULTS] as $mode => $values) {
            $defaults = array_combine(array_keys(self::PALETTE_TOKENS), $values);
            foreach (self::PALETTE_TOKENS as $field => $token) {
                $color = $settings[$mode.'Theme'][$field] ?? '';
                if ($color !== $defaults[$field] && preg_match('/\A#[0-9a-f]{6}\z/i', $color) === 1) {
                    $suffix = $token === 'border' ? '26' : '';
                    $style .= "--site-$mode-$token:$color$suffix;";
                }
            }
        }

        return $style;
    }

    public static function emailLink(string $email): string
    {
        $separator = strrpos($email, '@');

        return 'mailto:'.rawurlencode(substr($email, 0, $separator)).'@'.rawurlencode(substr($email, $separator + 1));
    }

    private static function decode(string $json): array
    {
        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

    private static function logoRecord(?string $id): ?array
    {
        if ($id === null) {
            return null;
        }

        $image = DB::table('images')->where('id', $id)->where('context', 'logo')->first(['width', 'height']);
        if ($image === null) {
            return null;
        }

        $url = '/api/v1/public/images/'.$id;

        return ['id' => $id, 'url' => $url, 'width' => $image->width, 'height' => $image->height, 'thumb' => $url.'/thumbnail'];
    }
}
