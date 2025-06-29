<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storePubliclyAs('userfiles/images', $fileName, 'public');

            return response()->json([
                'filename' => $fileName,
                'url' => config('app.url') . "/$path",
                'uploaded' => 1
            ]);
        }

        return response()->json([
            'filename' => '',
            'url' => '',
            'uploaded' => 0
        ]);
    }
}
