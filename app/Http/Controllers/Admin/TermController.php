<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTermRequest;
use App\Http\Requests\Admin\TermIndexRequest;
use App\Http\Requests\Admin\UpdateTermRequest;
use App\Http\Resources\TermResource;
use App\Models\Term;
use App\Services\TermService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TermController extends Controller
{
    public function __construct(private TermService $termService) {}

    public function index(TermIndexRequest $request)
    { 
        try {
            $terms = $this->termService->getAllTermsForUserType($request->user_type, $request->type)->paginate(10);
            $pagination = PaginationHelper::paginate($terms);

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.list_retrieved'),
                'data' => TermResource::collection($terms),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
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
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.terms.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $term = Term::query()->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.retrieved'),
                'data' => new TermResource($term),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.terms.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreTermRequest $request)
    {
        try {
            $data = $request->validated();
            $term = $this->termService->createTerm($data);

            return response()->json([
                'status' => 'success',
                'message' => __('api.terms.created'),
                'data' => new TermResource($term),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.terms.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateTermRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $term = Term::query()->findOrFail($id);
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

    public function destroy($id)
    {
        try {
            $term = Term::query()->findOrFail($id);
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
