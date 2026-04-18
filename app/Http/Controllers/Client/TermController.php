<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\TermResource;
use App\Services\TermService;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function __construct(private TermService $termService) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:terms_conditions,privacy_policy,FAQ',
            ]);
            $terms = $this->termService->getActiveTermsForUserType('client', $request->type);

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
