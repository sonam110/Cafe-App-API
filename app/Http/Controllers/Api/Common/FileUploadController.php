<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DB;
use Image;
class FileUploadController extends Controller
{
    public function fileUploads(Request $request)
    {
        try {
            $query = FileUpload::select('*')->orderBy('id_inc', 'ASC');
            if(!empty($request->store_id))
            {
                $query->where('store_id', $request->store_id);
            }

            if(!empty($request->file_name))
            {
                $query->where('file_name', 'LIKE', '%'.$request->file_name.'%');
            }

            if(!empty($request->file_type))
            {
                $query->where('file_type', $request->file_type);
            }

            if(!empty($request->per_page_record))
            {
                $perPage = $request->per_page_record;
                $page = $request->input('page', 1);
                $total = $query->count();
                $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                $pagination =  [
                    'data' => $result,
                    'total' => $total,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'last_page' => ceil($total / $perPage)
                ];
                $query = $pagination;
            }
            else
            {
                $query = $query->get();
            }

            return response(prepareResult(false, $query, trans('translate.fetched_records')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        if($request->is_multiple==1)
        {
            $validation = \Validator::make($request->all(),[ 
                'file'     => 'required|array|max:20000|min:1'
            ]);
        }
        else
        {
            $validation = \Validator::make($request->all(),[ 
                'file'     => 'required|max:10000',
            ]);
        }
        if ($validation->fails()) {
            return prepareResult(false,$validation->errors()->first() ,$validation->errors(), 500);
        }
        try
        {
            $file = $request->file;
            $destinationPath = 'uploads/';
            $fileArray = array();
            $formatCheck = ['doc','docx','png','jpeg','jpg','pdf','svg','mp4','tif','tiff','bmp','gif','eps','raw','jfif','webp','pem','csv'];

            if($request->is_multiple==1)
            {
                foreach ($file as $key => $value) 
                {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if(!in_array($extension, $formatCheck))
                    {
                        return prepareResult(false,'Only allowed : doc,docx,png,jpeg,jpg,pdf,svg,mp4,tif,tiff,bmp,gif,eps,raw,jfif,webp,pem,csv' ,[], 500);
                    }

                    $fileName   = time().'-'.rand(0,99999).'.' . $value->getClientOriginalExtension();
                    $extension = $value->getClientOriginalExtension();
                    $fileSize = $value->getSize();

                    if($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png')
                    {
                        //Thumb image generate
                        $imgthumb = Image::make($value->getRealPath());
                        $imgthumb->resize(100, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        $imgthumb->save($destinationPath.$fileName);
                    }
                    else
                    {
                        $value->move($destinationPath, $fileName);
                    }

                    //Create File Log
                    $file_name  = $fileName;
                    $file_type  = $extension;
                    $file_location  = env('CDN_DOC_URL').$destinationPath.$fileName;
                    $file_size  = $fileSize;

                    $fileSave = $this->CreateFileUploadRecord($file_name,$file_type,$file_location,$file_size);
                    
                    $fileArray[] = [
                        'file_name'         => env('CDN_DOC_URL').$destinationPath.$fileName,
                        'file_extension'    => $value->getClientOriginalExtension(),
                        'uploading_file_name' => $value->getClientOriginalName(),
                    ];
                }

                return prepareResult(true,'Uploaded Successfully!' ,$fileArray, 200);
            }
            else
            {
                $fileName   = time().'-'.rand(0,99999).'.' . $file->getClientOriginalExtension();
                $extension = strtolower($file->getClientOriginalExtension());
                $fileSize = $file->getSize();
                if(!in_array($extension, $formatCheck))
                {
                    return prepareResult(false,'Only allowed : doc,docx,png,jpeg,jpg,pdf,svg,mp4,tif,tiff,bmp,gif,eps,raw,jfif,webp,pem,csv' ,[], 500);
                }

                if($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png')
                {
                    //Thumb image generate
                    $imgthumb = Image::make($file->getRealPath());
                    $imgthumb->resize(100, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $imgthumb->save($destinationPath.$fileName);
                }
                else
                {
                    $file->move($destinationPath, $fileName);
                }

                //Create File Log
                $file_name  = $fileName;
                $file_type  = $extension;
                $file_location  = env('CDN_DOC_URL').$destinationPath.$fileName;
                $file_size  = $fileSize;
                
                $fileSave = $this->CreateFileUploadRecord($file_name,$file_type,$file_location,$file_size);

                $fileInfo = [
                    'file_name'         => env('CDN_DOC_URL').$destinationPath.$fileName,
                    'file_extension'    => $file->getClientOriginalExtension(),
                    'uploading_file_name' => $file->getClientOriginalName(),
                ];
                return prepareResult(true,'Uploaded Successfully!' ,$fileInfo, 200);
            }   
        } catch (\Throwable $e) {
            Log::error($e);
            return prepareResult(false,'Oops! Something went wrong.' ,$e->getMessage(), 500);
        }
    }

    private function CreateFileUploadRecord($file_name,$file_type,$file_location,$file_size)
    {
        $fileupload = new FileUpload;
        $fileupload->file_name = $file_name;
        $fileupload->file_type = $file_type;
        $fileupload->file_location = $file_location;
        $fileupload->file_size = $file_size;
        $fileupload->save();
        return $fileupload;
    }
}
