<?php

namespace App\Http\Controllers;

use App\GenericModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests;

class FileUploadController extends Controller
{
    public function uploadFile(Request $request)
    {
        $user_id = Auth::user()->id;

        if ($request->has('projectId')) {
            $project_id = $request->get('projectId');
        } else {
            $project_id = null;
        }

        $files = $request->file();

        foreach ($files as $file) {
            GenericModel::setCollection('uploads');
            $upload = GenericModel::create();

            $file_name = time() . "." . $file->getClientOriginalExtension();

            $s3 = Storage::disk('s3');
            $file_path = '/starapi/tests/' . $file_name;
            $s3->put($file_path, file_get_contents($file), 'public');

            $fileUrl = Storage::url($file);

            $upload->profileId = $user_id;
            $upload->projectId = $project_id;
            $upload->name = $file_name;
            $upload->fileUrl = $fileUrl;
            $upload->save();
        }
    }
}
