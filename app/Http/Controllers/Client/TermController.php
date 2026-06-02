<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\TermTypeRequest;
use App\Http\Resources\TermResource;
use App\Services\TermService;

class TermController extends Controller
{
    public function __construct(private TermService $termService) {}

    public function index(TermTypeRequest $request)
    {
        try {
            $terms = $this->termService->getActiveTermsForUserType('client', $request->type)->get();

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.list_retrieved'),
                'data' => TermResource::collection($terms),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.terms.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
