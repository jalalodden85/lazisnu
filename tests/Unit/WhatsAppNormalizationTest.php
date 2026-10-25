<?php

namespace Tests\Unit;

use App\Services\WhatsAppHelper;
use PHPUnit\Framework\TestCase;

class WhatsAppNormalizationTest extends TestCase
{
    public function test_normalizes_leading_zero_to_62(): void
    {
        $this->assertEquals('6281234567890', WhatsAppHelper::formatNumber('081234567890'));
        $this->assertEquals('6285707741811', WhatsAppHelper::formatNumber('0857-0774-1811'));
        $this->assertEquals('628123456789', WhatsAppHelper::formatNumber('0812 3456 789'));
    }

    public function test_preserves_already_formatted_62(): void
    {
        $this->assertEquals('6281234567890', WhatsAppHelper::formatNumber('6281234567890'));
        $this->assertEquals('6281234567890', WhatsAppHelper::formatNumber('+62 812-3456-7890'));
    }

    public function test_handles_leading_8(): void
    {
        $this->assertEquals('6281234567890', WhatsAppHelper::formatNumber('81234567890'));
    }

    public function test_returns_null_for_empty_or_invalid_numbers(): void
    {
        $this->assertNull(WhatsAppHelper::formatNumber(null));
        $this->assertNull(WhatsAppHelper::formatNumber(''));
        $this->assertNull(WhatsAppHelper::formatNumber('   '));
        $this->assertNull(WhatsAppHelper::formatNumber('123')); // too short
        $this->assertFalse(WhatsAppHelper::isValidNumber(null));
        $this->assertFalse(WhatsAppHelper::isValidNumber('08'));
        $this->assertTrue(WhatsAppHelper::isValidNumber('081234567890'));
    }

    public function test_generates_donation_thank_you_link(): void
    {
        $link = WhatsAppHelper::generateDonationThankYouLink('0857-0774-1811', 'Ahmad Dahlan', 'DON-20261025-ABCDE', 100000);
        $this->assertNotNull($link);
        $this->assertStringStartsWith('https://wa.me/6285707741811?text=', $link);
        $this->assertStringContainsString('Ahmad%20Dahlan', $link);
        $this->assertStringContainsString('DON-20261025-ABCDE', $link);
    }

    public function test_generates_koin_nu_thank_you_link(): void
    {
        $link = WhatsAppHelper::generateKoinNuThankYouLink('081234567890', 'Haji Mahmud', '001/02');
        $this->assertNotNull($link);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $link);
        $this->assertStringContainsString('Haji%20Mahmud', $link);
        $this->assertStringContainsString('%23001%2F02', $link);
    }
}
