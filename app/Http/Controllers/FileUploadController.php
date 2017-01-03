<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests;

class FileUploadController extends Controller
{
    public function uploadFile(Request $request)
    {
        $file = $request->file('image');

        $file_name = time() . "." . $file->getClientOriginalExtension();

        $s3 = Storage::disk('s3');
        $file_path = '/starapi/tests/' . $file_name;
        $s3->put($file_path, file_get_contents($file), 'public');
    }
}
