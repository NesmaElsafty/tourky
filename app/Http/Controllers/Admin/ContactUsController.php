<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ContactUsService;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    public function __construct(
        private ContactUsService $contactUsService
    ) {}

    public function index()
    {
        try {
            $contactUs = $this->contactUsService->getAllContactUs();

            return response()->json([
                'status' => 'success',
                'message' => __('api.contact_us.list_retrieved'),
                'data' => $contactUs,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.contact_us.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $request->validate([
                'type' => 'required|string|in:contact_us,social_media,instapay_data',
            ]);
            $contactUs = $this->contactUsService->getContactUsById($request->type, $id);
            if ($contactUs === false) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.contact_us.not_found'),
                ], 404);
            }
            if ($contactUs) {
                return response()->json([
                    'status' => 'success',
                    'message' => __('api.contact_us.retrieved'),
                    'data' => $contactUs,
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => __('api.contact_us.not_found'),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.contact_us.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|string|in:contact_us,social_media,instapay_data',
            ]);
            $data = $this->contactUsService->createContactUs($request->all(), $request->type);

            return response()->json([
                'status' => 'success',
                'message' => __('api.contact_us.created'),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.contact_us.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'type' => 'required|string|in:contact_us,social_media,instapay_data',
            ]);

            $data = $this->contactUsService->updateContactUs($request->all(), $request->type, $id);
            if ($data === false) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.contact_us.not_found'),
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'message' => __('api.contact_us.updated'),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.contact_us.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $request->validate([
                'type' => 'required|string|in:contact_us,social_media,instapay_data',
            ]);
            $data = $this->contactUsService->destroyContactUs($request->type, $id);
            if ($data) {
                return response()->json([
                    'status' => 'success',
                    'message' => __('api.contact_us.deleted'),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => __('api.contact_us.not_found'),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.contact_us.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
