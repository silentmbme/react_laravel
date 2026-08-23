<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Services\MarketplaceSettings;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function presignedUrl(Request $request, MarketplaceSettings $settings)
    {
        $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
            'folder' => ['required', 'string', 'max:100'],
        ]);

        $path = sprintf('user_%s/%s/%s_%s', auth()->id(), trim($request->folder, '/'), uniqid(), basename($request->file_name));
        $disk = $settings->r2Disk();
        $bucket = $settings->r2Bucket();
        abort_unless($bucket, 422, 'Cloud storage is not configured.');

        $command = $disk->getClient()->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key' => $path,
        ]);

        $presignedRequest = $disk->getClient()->createPresignedRequest($command, '+30 minutes');

        return response()->json([
            'upload_url' => (string) $presignedRequest->getUri(),
            'path' => $path,
        ]);
    }

    public function destroy(Request $request, MarketplaceSettings $settings)
    {
        $request->validate(['path' => ['required', 'string']]);
        abort_unless(str_starts_with($request->path, 'user_'.auth()->id().'/'), 403, 'You can only delete your own files.');

        $settings->r2Disk()->delete($request->path);

        return response()->json(['message' => 'Deleted']);
    }
}
