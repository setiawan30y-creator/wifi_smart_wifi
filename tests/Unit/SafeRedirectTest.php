<?php

namespace Tests\Unit;

use App\Support\SafeRedirect;
use Tests\TestCase;

class SafeRedirectTest extends TestCase
{
    public function test_allows_internal_paths_and_urls(): void
    {
        $this->assertSame('/customers?page=2', SafeRedirect::to('/customers?page=2', '/x'));
        $this->assertSame(url('/odps'), SafeRedirect::to(url('/odps'), '/x'));
    }

    public function test_blocks_external_targets(): void
    {
        foreach (['https://contoh-luar.test', '//contoh-luar.test', '/\\contoh-luar.test', 'javascript:alert(1)', url('/') . '.contoh-luar.test'] as $target) {
            $this->assertSame('/fallback', SafeRedirect::to($target, '/fallback'), $target);
        }
    }
}
