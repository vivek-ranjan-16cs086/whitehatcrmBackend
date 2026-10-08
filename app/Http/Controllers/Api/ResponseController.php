<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResponseResource;
use App\Http\Resources\SubResponseResource;
use Illuminate\Http\Request;
use App\Models\Response;
use App\Models\SubResponse;
use App\Imports\LeadsImport;
use Maatwebsite\Excel\Facades\Excel;

class ResponseController extends Controller
{
    /**
     * Response Dropdown
     */
    public function getResponses()
    {
        $responses = Response::orderBy('id')->get(); 

        return response()->json([
            'status' => true,
            'message' => 'Responses fetched successfully.',
            'data' => ResponseResource::collection($responses)
        ]);
    } 

    /**
     * Sub Response Dropdown
     */
    public function getSubResponses($responseId)
    {
        $subResponses = SubResponse::where('response_id', $responseId)
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Sub responses fetched successfully.',
            'data' => SubResponseResource::collection($subResponses)
        ]);
    } 
	
	public function import(Request $request)
	{
		try {

			$validator = \Validator::make($request->all(), [
				'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
			]);

			if ($validator->fails()) {
				return response()->json([
					'status' => false,
					'message' => $validator->errors()->first()
				], 422);
			}

			Excel::import(new LeadsImport, $request->file('file'));

			return response()->json([
				'status' => true, 
				'message' => 'Leads imported successfully.'
			]);

		} catch (\Exception $e) {

			return response()->json([
				'status' => false,
				'message' => $e->getMessage()
			], 500);

		}
	}
}