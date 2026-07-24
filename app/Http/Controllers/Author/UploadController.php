<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function presignedUrl(Request $request)
    {

        $request->validate([

            'file_name' => 'required',

            'folder' => 'required'

        ]);

        $path = sprintf(

            "%s/%s/%s_%s",
            'user_'.auth()->id(),
            $request->folder,
            uniqid(),
            $request->file_name

        );

        Log::info($path);

        $disk = Storage::disk('r2');

        $client = $disk->getClient();

        $command = $client->getCommand(
            'PutObject',
            [

                'Bucket' =>
                config(
                    'filesystems.disks.r2.bucket'
                ),

                'Key' => $path

            ]
        );

        $presignedRequest =
            $client->createPresignedRequest(
                $command,
                '+30 minutes'
            );

        return response()->json([

            'upload_url' =>
            (string)
            $presignedRequest
                ->getUri(),

            'path' => $path

        ]);
    }

    public function destroy(Request $request)
    {

        $request->validate([
            'path' => 'required|string'
        ]);

        Storage::disk('r2')
            ->delete($request->path);

        return response()->json([
            'message' => 'Deleted'
        ]);
    }
}
