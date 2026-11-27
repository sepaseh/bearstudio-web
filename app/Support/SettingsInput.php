<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use stdClass;

final class SettingsInput
{
    public const FIELDS = ['site', 'lightTheme', 'darkTheme', 'profiles', 'lightLogoId', 'darkLogoId'];

    public static function changes(stdClass $input, array $currentProfiles): array
    {
        $data = self::object($input, 'settings', self::FIELDS);
        $site = self::site($data['site']);
        $light = self::palette($data['lightTheme'], 'lightTheme');
        $dark = self::palette($data['darkTheme'], 'darkTheme');
        $profiles = self::profiles($data['profiles'], $currentProfiles);

        return [
            'site_json' => json_encode($site, JSON_THROW_ON_ERROR),
            'light_theme_json' => json_encode($light, JSON_THROW_ON_ERROR),
            'dark_theme_json' => json_encode($dark, JSON_THROW_ON_ERROR),
            'image_profiles_json' => json_encode($profiles, JSON_THROW_ON_ERROR),
            'light_logo_id' => self::logo($data['lightLogoId'], 'lightLogoId'),
            'dark_logo_id' => self::logo($data['darkLogoId'], 'darkLogoId'),
        ];
    }

    private static function site(mixed $value): array
    {
        $site = self::object($value, 'site', [
            'brandName', 'description', 'contactEmail', 'contactTitle', 'contactDescription', 'socialLinks', 'contact',
        ]);
        $site['brandName'] = self::text($site['brandName'], 'brandName', 500, required: true);
        $site['description'] = self::text($site['description'], 'description', 10000);
        $site['contactTitle'] = self::text($site['contactTitle'], 'contactTitle', 500);
        $site['contactDescription'] = self::text($site['contactDescription'], 'contactDescription', 10000);
        if ($site['contactEmail'] !== null) {
            $site['contactEmail'] = self::text($site['contactEmail'], 'contactEmail', 254, required: true);
            if (! filter_var($site['contactEmail'], FILTER_VALIDATE_EMAIL)) {
                AccessInput::invalid('contactEmail', 'Supply a valid email address or null.');
            }
        }
        if (! is_array($site['socialLinks']) || count($site['socialLinks']) > 20) {
            AccessInput::invalid('socialLinks', 'Supply at most twenty links.');
        }
        $site['socialLinks'] = array_map(self::socialLink(...), $site['socialLinks']);
        $contact = self::object($site['contact'], 'contact', ['phone', 'whatsAppNumber']);
        foreach ($contact as $key => $number) {
            if ($number === null) {
                continue;
            }
            $number = is_string($number) ? trim($number) : '';
            if (preg_match('/\A\+[1-9][0-9]{6,14}\z/', $number) !== 1) {
                AccessInput::invalid('contact.'.$key, 'Use + followed by 7–15 ASCII digits or null.');
            }
            $contact[$key] = $number;
        }
        $site['contact'] = $contact;

        return $site;
    }

    private static function socialLink(mixed $value): array
    {
        $link = self::object($value, 'socialLinks', ['label', 'url']);
        $label = self::text($link['label'], 'label', 120, required: true);
        $url = self::text($link['url'], 'url', 2048, required: true);
        $parts = parse_url($url);
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || ! isset($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || preg_match('/[\p{C}\s\\\\]/u', $url) !== 0) {
            AccessInput::invalid('url', 'Use an absolute HTTP(S) address without credentials.');
        }

        return ['label' => $label, 'url' => $url];
    }

    private static function palette(mixed $value, string $field): array
    {
        $colors = self::object($value, $field, array_keys(SiteSettings::PALETTE_TOKENS));
        foreach ($colors as $key => $color) {
            if (! is_string($color) || preg_match('/\A#[0-9a-f]{6}\z/i', $color) !== 1) {
                AccessInput::invalid($field.'.'.$key, 'Use six-digit hexadecimal colors beginning with #.');
            }
            $colors[$key] = strtolower($color);
        }

        return $colors;
    }

    private static function profiles(mixed $value, array $current): array
    {
        $contexts = ['article', 'project', 'service', 'content', 'logo', 'video', 'thumbnail'];
        $profiles = self::object($value, 'profiles', ['logo'], array_diff($contexts, ['logo']));
        foreach ($contexts as $context) {
            $profile = $profiles[$context] ?? null;
            if ($context !== 'logo' && $profile === null) {
                continue;
            }
            $field = 'profiles.'.$context;
            $thumbnail = $context === 'thumbnail';
            $fields = $thumbnail ? ['width', 'height'] : ['width', 'height', 'size'];
            $dimensions = self::object($profile, $field, $fields);
            $limit = $thumbnail ? 1024 : 8192;
            $width = self::integer($dimensions['width'], $field.'.width', 1, $limit);
            $height = self::integer($dimensions['height'], $field.'.height', 1, $limit);
            if ($width * $height > 16_000_000 || ($context === 'content' && $width <= $height)) {
                AccessInput::invalid($field, 'Use at most 16 megapixels and landscape dimensions for the content profile.');
            }
            if (! $thumbnail) {
                self::integer($dimensions['size'], $field.'.size', 1024, 10 * 1024 * 1024);
            }
            $current[$context] = $dimensions;
        }

        return $current;
    }

    private static function logo(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }
        $id = AccessInput::ids([$value], $field)[0];
        if (! DB::table('images')->where('id', $id)->where('context', 'logo')->exists()) {
            AccessInput::invalid($field, 'Choose an existing ready logo image or null.');
        }

        return $id;
    }

    private static function object(mixed $value, string $field, array $required, array $optional = []): array
    {
        if (! $value instanceof stdClass) {
            AccessInput::invalid($field, 'Supply the requested fields as an object.');
        }
        $data = get_object_vars($value);
        $keys = array_keys($data);
        if (array_diff($required, $keys) !== [] || array_diff($keys, $required, $optional) !== []) {
            AccessInput::invalid($field, 'Supply all required fields and only supported fields.');
        }

        return $data;
    }

    private static function text(mixed $value, string $field, int $max, bool $required = false): string
    {
        if (! is_string($value) || str_contains($value, "\0")) {
            AccessInput::invalid($field, 'Supply valid text.');
        }
        $text = trim($value);
        if (mb_strlen($text, 'UTF-8') > $max || ($required && $text === '')) {
            AccessInput::invalid($field, 'Supply text of at most '.$max.' characters.');
        }

        return $text;
    }

    private static function integer(mixed $value, string $field, int $min, int $max): int
    {
        if (! is_int($value) || $value < $min || $value > $max) {
            AccessInput::invalid($field, "Use an integer from $min to $max.");
        }

        return $value;
    }
}
