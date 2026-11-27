<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Access;
use App\Support\AdministratorPassword;
use App\Support\AdministratorSession;
use App\Support\ApiError;
use App\Support\ArticleHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InputSafetyTest extends TestCase
{
    public static function mobileInputs(): array
    {
        return [
            ['۹۱۲۳۴۵۶۷۸۹', '9123456789'], ['٩١٢٣٤٥٦٧٨٩', '9123456789'],
            [' 9123456789 ', '9123456789'], ['09123456789', null],
            ['+989123456789', null], ['912345678', null], [str_repeat('9', 65), null],
        ];
    }

    #[DataProvider('mobileInputs')]
    public function test_mobile_identity_is_canonical_and_bounded(string $input, ?string $expected): void
    {
        self::assertSame($expected, AdministratorSession::canonicalMobile($input));
    }

    public static function passwords(): array
    {
        return [['Abcdefghij1!', true], ['shortA1!', false], [str_repeat('A', 129).'a1!', false],
            ['abcdefghijkl1!', false], ['ABCDEFGHIJKL1!', false], ['Abcdefghijkl!', false],
            ['Abcdefghijk12', false], ["Abcdefghij1!\0", false], [null, false]];
    }

    #[DataProvider('passwords')]
    public function test_password_policy(mixed $input, bool $expected): void
    {
        self::assertSame($expected, AdministratorPassword::valid($input));
    }

    public function test_legacy_bcrypt_cannot_authenticate_a_truncated_long_password(): void
    {
        $prefix = str_repeat('a', 72);
        $hash = password_hash($prefix, PASSWORD_BCRYPT, ['cost' => 4]);
        self::assertTrue(AdministratorPassword::verify($prefix, $hash));
        self::assertFalse(AdministratorPassword::verify($prefix.'different', $hash));
    }

    public function test_article_html_discards_active_content_and_unsafe_attributes(): void
    {
        $html = ArticleHtml::normalize('<p onclick="alert(1)">Safe<strong> text</strong><script>alert(1)</script><a href="javascript:alert(1)">link</a><img src=x onerror=alert(1)></p>');
        self::assertSame('<p>Safe<strong> text</strong><a>link</a></p>', $html);
        self::assertSame('<a href="https://example.test/path">Safe</a>', ArticleHtml::normalize('<a href="https://example.test/path">Safe</a>'));
    }

    public function test_invisible_article_body_is_rejected(): void
    {
        $this->expectException(ApiError::class);
        ArticleHtml::normalize('<script>invisible</script><p> </p>');
    }

    public function test_permissions_are_a_sorted_union_without_duplicates(): void
    {
        self::assertSame(['content.read', 'content.write'], Access::effectivePermissions([
            '["content.write","content.read"]', '["content.read"]',
        ]));
    }
}
