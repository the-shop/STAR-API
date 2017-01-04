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
        $userId = Auth::user()->id;

        if ($request->has('projectId')) {
            $projectId = $request->get('projectId');
        } else {
            $projectId = null;
        }

        $files = $request->file();

        $response = [];
        foreach ($files as $file) {
            GenericModel::setCollection('uploads');
            $upload = GenericModel::create();

            $fileName = time() . "." . $file->getClientOriginalExtension();

            $s3 = Storage::disk('s3');
            $filePath = $fileName;
            $s3->put($filePath, file_get_contents($file), 'public');

            $fileUrl = Storage::cloud()->url($fileName);

            $upload->projectId = $projectId;
            $upload->name = $fileName;
            $upload->fileUrl = $fileUrl;
            $upload->save();

            $response[] = $upload;
        }

        return $this->jsonSuccess($response);
    }

    public function getProjectUploads(Request $request)
    {
        GenericModel::setCollection('projects');
        $project = GenericModel::find($request->route('id'));
        if (!$project) {
            return $this->jsonError(['Project with given ID not found'], 404);
        }

        GenericModel::setCollection('uploads');
        $uploads = GenericModel::where('projectId', '=', $id)->get();

        return $this->jsonSuccess($uploads);
    }
}
