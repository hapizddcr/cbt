<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaAssetsTest extends TestCase
{
    public function test_manifest_json_is_served(): void
    {
        $response = $this->get('/manifest.json');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json');
        $body = $response->json();
        $this->assertSame('Sistem CBT', $body['name']);
        $this->assertSame('standalone', $body['display']);
        $this->assertSame('/1a3a6c', '/'.ltrim($body['theme_color'], '#'));
        $this->assertNotEmpty($body['icons']);
    }

    public function test_sw_js_is_served(): void
    {
        $response = $this->get('/sw.js');
        $response->assertOk();
        $this->assertStringContainsString('javascript', $response->headers->get('content-type'));
        $this->assertStringContainsString('CACHE_NAME', $response->getContent());
    }

    public function test_icon_assets_exist(): void
    {
        $this->assertTrue(file_exists(public_path('icons/icon.svg')));
        $this->assertTrue(file_exists(public_path('icons/icon-192.png')));
        $this->assertTrue(file_exists(public_path('icons/icon-512.png')));
    }

    public function test_exam_layout_includes_pwa_meta_tags(): void
    {
        // Render a minimal page that uses the exam layout via the take view
        // Just check the raw layout file contains the PWA meta tags
        $layout = file_get_contents(resource_path('views/layouts/exam.blade.php'));
        $this->assertStringContainsString('rel="manifest"', $layout);
        $this->assertStringContainsString('theme-color', $layout);
        $this->assertStringContainsString('apple-mobile-web-app', $layout);
        $this->assertStringContainsString('viewport-fit=cover', $layout);
        $this->assertStringContainsString('serviceWorker.register', $layout);
        $this->assertStringContainsString('@media (max-width: 768px)', $layout);
        $this->assertStringContainsString('@media (max-width: 480px)', $layout);
    }
}
