<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class MediaUrl
{
    /**
     * Convert a stored media path into a public URL.
     *
     * Absolute URLs and Next.js public paths (e.g. "/aset/foo.png") are kept
     * as-is. Relative paths returned by Storage are resolved through the
     * default disk, which may be the local public disk or an S3-compatible
     * bucket (e.g. Laravel Cloud Object Storage).
     */
    public static function toUrl(?string $path): string
    {
        if ($path === null || $path === '') {
            return '';
        }

        if (preg_match('#^https?://#', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        $url = Storage::url($path);

        // Local disks may return a relative "/storage/..." URL. Make it
        // absolute so API clients can use the value directly.
        if (str_starts_with($url, '/')) {
            return rtrim((string) config('app.url'), '/').$url;
        }

        return $url;
    }
}
