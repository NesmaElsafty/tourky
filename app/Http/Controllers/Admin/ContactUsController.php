<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContactUsTypeRequest;
use App\Services\ContactUsService;

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

    public function show(ContactUsTypeRequest $request, $id)
    {
        try {
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

    public function store(ContactUsTypeRequest $request)
    {
        try {
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

    public function update(ContactUsTypeRequest $request, $id)
    {
        try {
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

    public function destroy(ContactUsTypeRequest $request, $id)
    {
        try {
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
