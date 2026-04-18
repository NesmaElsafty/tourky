<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\TermResource;
use App\Models\Term;
use App\Services\TermService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TermController extends Controller
{
    public function __construct(private TermService $termService) {}

    public function index(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:terms_conditions,privacy_policy,FAQ',
                'user_type' => ['required', Rule::in(Term::USER_TYPES)],
            ]);
            $terms = $this->termService->getAllTermsForUserType($request->user_type, $request->type);
            $pagination = PaginationHelper::paginate($terms);

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.list_retrieved'),
                'data' => TermResource::collection($terms),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.terms.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexAll(Request $request)
    {
        try {
            $terms = $this->termService->getTermsPaginated(
                (int) ($request->per_page ?? 10),
                onlyActive: false,
            );
            $pagination = PaginationHelper::paginate($terms);

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.list_retrieved'),
                'data' => TermResource::collection($terms),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.terms.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Term $term)
    {
        try {
            if (! $term->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.terms.not_found'),
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.retrieved'),
                'data' => new TermResource($term),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.terms.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name_en' => 'required|string|max:255',
                'name_ar' => 'required|string|max:255',
                'description_en' => 'nullable|string|max:255',
                'description_ar' => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean',
                'type' => ['required', Rule::in(Term::TYPES)],
                'user_type' => ['required', Rule::in(Term::USER_TYPES)],
            ]);
            $term = $this->termService->createTerm($data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.created'),
                'data' => new TermResource($term),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.terms.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Term $term)
    {
        try {
            $data = $request->validate([
                'name_en' => 'sometimes|required|string|max:255',
                'name_ar' => 'sometimes|required|string|max:255',
                'description_en' => 'nullable|string|max:255',
                'description_ar' => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean',
                'type' => ['sometimes', 'required', Rule::in(Term::TYPES)],
                'user_type' => ['sometimes', 'required', Rule::in(Term::USER_TYPES)],
            ]);
            $term = $this->termService->updateTerm($term, $data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.updated'),
                'data' => new TermResource($term),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.terms.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Term $term)
    {
        try {
            $this->termService->deleteTerm($term);

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.deleted'),
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
