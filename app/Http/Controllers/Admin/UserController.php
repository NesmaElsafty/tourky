<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CaptainRatingService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private CaptainRatingService $captainRatingService,
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $this->validateListFilters($request);

            $users = $this->userService->paginateUsers([
                'type' => $filters['type'],
                'search' => $filters['search'] ?? null,
                'language' => $filters['language'] ?? null,
                'role_id' => $filters['role_id'] ?? null,
                'created_from' => $filters['created_from'] ?? null,
                'created_to' => $filters['created_to'] ?? null,
                'per_page' => (int) ($filters['per_page'] ?? 10),
                'only_trashed' => false,
            ]);
            $pagination = PaginationHelper::paginate($users);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.list_retrieved', [
                    'type' => __('api.users.type_list_labels.'.$filters['type']),
                ]),
                'data' => UserResource::collection($users),
                'pagination' => $pagination,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function blocklist(Request $request)
    {
        try {
            $filters = $this->validateListFilters($request);

            $users = $this->userService->paginateUsers([
                'type' => $filters['type'],
                'search' => $filters['search'] ?? null,
                'language' => $filters['language'] ?? null,
                'role_id' => $filters['role_id'] ?? null,
                'created_from' => $filters['created_from'] ?? null,
                'created_to' => $filters['created_to'] ?? null,
                'per_page' => (int) ($filters['per_page'] ?? 10),
                'only_trashed' => true,
            ]);
            $pagination = PaginationHelper::paginate($users);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.blocklist_retrieved', [
                    'type' => __('api.users.type_list_labels.'.$filters['type']),
                ]),
                'data' => UserResource::collection($users),
                'pagination' => $pagination,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $phoneRule = Rule::unique('users', 'phone')->where(
                fn ($query) => $query->where('type', $request->input('type')),
            );
            $emailRule = Rule::unique('users', 'email')->where(
                fn ($query) => $query->where('type', $request->input('type')),
            );

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => ['required', 'string', 'max:50', $phoneRule],
                'email' => ['nullable', 'string', 'email', 'max:255', $emailRule],
                'password' => 'required|string|min:6|confirmed',
                'type' => ['required', Rule::in(['admin', 'captain', 'client'])],
                'language' => ['required', Rule::in(['en', 'ar'])],
                'role_id' => [
                    Rule::requiredIf($request->input('type') === 'admin'),
                    'nullable',
                    'integer',
                    'exists:roles,id',
                ],
            ], $this->storeValidationMessages());

            $payload = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'type' => $data['type'],
                'language' => $data['language'],
                'role_id' => $data['type'] === 'admin' ? (int) $data['role_id'] : null,
            ];

            $user = $this->userService->createUser($payload);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.created'),
                'data' => new UserResource($user->load('role')),
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $user = $this->userService->findUserForAdmin($id, true);
            $this->attachCaptainRatingProfile($user);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.retrieved'),
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $user = $this->userService->findActiveUserForAdmin($id);

            $phoneRule = Rule::unique('users', 'phone')->where(
                fn ($query) => $query->where('type', $user->type),
            )->ignore($user->id);

            $emailRule = Rule::unique('users', 'email')->where(
                fn ($query) => $query->where('type', $user->type),
            )->ignore($user->id);

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'phone' => ['sometimes', 'required', 'string', 'max:50', $phoneRule],
                'email' => ['nullable', 'string', 'email', 'max:255', $emailRule],
                'password' => 'sometimes|nullable|string|min:6|confirmed',
                'language' => ['sometimes', 'required', Rule::in(['en', 'ar'])],
                'role_id' => ['sometimes', 'nullable', 'integer', 'exists:roles,id'],
            ], $this->updateValidationMessages());

            $update = [];
            foreach (['name', 'phone', 'email', 'language'] as $field) {
                if (array_key_exists($field, $data)) {
                    $update[$field] = $data[$field];
                }
            }
            if (array_key_exists('email', $update) && $update['email'] === '') {
                $update['email'] = null;
            }
            if (! empty($data['password'])) {
                $update['password'] = $data['password'];
            }
            if ($user->type === 'admin' && array_key_exists('role_id', $data) && $data['role_id'] !== null) {
                $update['role_id'] = (int) $data['role_id'];
            }

            $user = $this->userService->updateUser($user, $update);
            $this->attachCaptainRatingProfile($user);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.updated'),
                'data' => new UserResource($user),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        try {
            $user = $this->userService->findActiveUserForAdmin($id);
            $actor = $request->user();
            if ($actor === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.unauthorized'),
                ], 401);
            }

            $this->userService->softDeleteUser($user, $actor);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.blocked'),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restore(int $id)
    {
        try {
            $user = $this->userService->restoreUser($id);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.restored'),
                'data' => new UserResource($user),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.not_in_blocklist'),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array{type: string, search?: string, language?: string, role_id?: int, created_from?: string, created_to?: string, per_page?: int}
     */
    private function validateListFilters(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['admin', 'captain', 'client'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'language' => ['sometimes', 'nullable', Rule::in(['en', 'ar'])],
            'role_id' => ['sometimes', 'nullable', 'integer', 'exists:roles,id'],
            'created_from' => ['sometimes', 'nullable', 'date'],
            'created_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:created_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ], [
            'type.required' => __('api.users.validation_type_required'),
            'type.in' => __('api.users.validation_type_invalid'),
            'per_page.integer' => __('api.users.validation_per_page_integer'),
            'per_page.min' => __('api.users.validation_per_page_min'),
            'per_page.max' => __('api.users.validation_per_page_max'),
            'created_to.after_or_equal' => __('api.users.validation_created_range'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function storeValidationMessages(): array
    {
        return [
            'name.required' => __('api.users.validation_name_required'),
            'phone.required' => __('api.users.validation_phone_required'),
            'phone.unique' => __('api.users.validation_phone_unique'),
            'email.unique' => __('api.users.validation_email_unique'),
            'password.required' => __('api.users.validation_password_required'),
            'type.required' => __('api.users.validation_type_required'),
            'type.in' => __('api.users.validation_type_invalid'),
            'language.required' => __('api.users.validation_language_required'),
            'role_id.required' => __('api.users.validation_role_required'),
            'role_id.exists' => __('api.users.validation_role_invalid'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function updateValidationMessages(): array
    {
        return [
            'phone.unique' => __('api.users.validation_phone_unique'),
            'email.unique' => __('api.users.validation_email_unique'),
            'role_id.exists' => __('api.users.validation_role_invalid'),
        ];
    }

    /**
     * Extra fields for {@see UserResource} when the user is a captain (admin users API).
     */
    private function attachCaptainRatingProfile(User $user): void
    {
        if ($user->type !== 'captain') {
            return;
        }

        $id = (int) $user->id;
        $this->captainRatingService->aggregateForCaptainIds([$id]);
        $agg = $this->captainRatingService->aggregateForCaptainId($id);
        $user->setAttribute('captain_rating_average', $agg['average']);
        $user->setAttribute('captain_ratings_count', $agg['count']);
        $user->setAttribute('captain_feedback_entries', $this->captainRatingService->feedbackEntriesForCaptain($id));
    }
}
