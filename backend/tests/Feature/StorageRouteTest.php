<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageRouteTest extends TestCase
{
    public function test_public_disk_is_served_without_storage_link(): void
    {
        $path = 'storage-route-test.txt';

        Storage::disk('public')->put($path, 'isi-file-publik');

        try {
            $response = $this->get('/storage/'.$path);

            $response->assertOk();
            $this->assertStringContainsString('isi-file-publik', $response->streamedContent());
        } finally {
            Storage::disk('public')->delete($path);
        }
    }
}
