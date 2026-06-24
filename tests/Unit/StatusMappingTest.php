<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StatusMappingTest extends TestCase
{
    private function mapSingleStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }
        $normalized = strtolower(trim($status));
        if (in_array($normalized, ['on progress', 'in_purchasing', 'in_release'])) {
            return 'approved';
        }
        return $status;
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        return match (strtolower(trim($status))) {
            'all' => null,
            'fulfilled', 'terpenuhi', 'released', 'on progress', 'in_purchasing', 'in_release' => 'approved',
            default => strtolower(trim($status)),
        };
    }

    public function test_map_single_status()
    {
        $this->assertEquals('approved', $this->mapSingleStatus('on progress'));
        $this->assertEquals('approved', $this->mapSingleStatus('in_purchasing'));
        $this->assertEquals('approved', $this->mapSingleStatus('in_release'));
        $this->assertEquals('approved', $this->mapSingleStatus('approved'));
        $this->assertEquals('rejected', $this->mapSingleStatus('rejected'));
        $this->assertEquals('pending', $this->mapSingleStatus('pending'));
        $this->assertNull($this->mapSingleStatus(null));
    }

    public function test_normalize_status()
    {
        $this->assertEquals('approved', $this->normalizeStatus('on progress'));
        $this->assertEquals('approved', $this->normalizeStatus('in_purchasing'));
        $this->assertEquals('approved', $this->normalizeStatus('in_release'));
        $this->assertEquals('approved', $this->normalizeStatus('fulfilled'));
        $this->assertEquals('approved', $this->normalizeStatus('terpenuhi'));
        $this->assertEquals('approved', $this->normalizeStatus('released'));
        $this->assertEquals('rejected', $this->normalizeStatus('rejected'));
        $this->assertEquals('pending', $this->normalizeStatus('pending'));
        $this->assertNull($this->normalizeStatus('all'));
        $this->assertNull($this->normalizeStatus(null));
    }
}
