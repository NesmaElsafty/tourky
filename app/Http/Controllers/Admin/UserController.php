<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CaptainDocumentService;
use App\Services\CaptainRatingService;
use App\Services\UserService;
use App\Support\CaptainDocumentCollections;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\CompanyResource;
class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private CaptainRatingService $captainRatingService,
        private CaptainDocumentService $captainDocumentService,
    ) {}

    public function index(UserIndexRequest $request)
    {
        try {
            $filters = $request->validated();

            $actor = $request->user();
            $paginateFilters = [
                'type' => $filters['type'],
                'search' => $filters['search'] ?? null,
                'language' => $filters['language'] ?? null,
                'role_id' => $filters['role_id'] ?? null,
                'created_from' => $filters['created_from'] ?? null,
                'created_to' => $filters['created_to'] ?? null,
                'per_page' => (int) ($filters['per_page'] ?? 10),
                'only_trashed' => false,
            ];
            // Company role: only clients with company_id = authenticated admin id (other filters apply in that scope).
            if ($actor instanceof User && $actor->isCompanyOperator()) {
                $paginateFilters['for_company_owner_id'] = $actor->id;
            }

            $users = $this->userService->paginateUsers($paginateFilters);

            if($actor instanceof User && $actor->isCompanyOperator()){
                $users =User::where('company_id', $actor->id)->paginate(10);
            }

            $pagination = PaginationHelper::paginate($users);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.list_retrieved', [
                    'type' => __('api.users.type_list_labels.'.$filters['type']),
                ]),
                'data' => UserResource::collection($users),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // get client by phone number and
    public function getClientByPhoneNumber($phone)
    {
        try {
            $client = User::where('phone', $phone)->where('type', 'client')->first();
            if($client === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.users.client_not_found'),
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'message' => __('api.users.client_retrieved'),
                'data' => new UserResource($client),
            ]);
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
            $users = User::onlyTrashed()->paginate(10);
            $pagination = PaginationHelper::paginate($users);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.blocklist_retrieved', [
                    'type' => __('api.users.type_list_labels.client'),
                ]),
                'data' => UserResource::collection($users),
                'pagination' => $pagination,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // companies list
    public function companiesList(Request $request)
    {
        try {
            // company is user type admin and has role company
            $companies = User::where('type', 'admin')->whereHas('role', function ($query) {
                $query->where('name_en', 'Company');
            })->paginate(10);
            $pagination = PaginationHelper::paginate($companies);
            return response()->json([
                'status' => 'success',
                'message' => __('api.users.companies_list_retrieved'),
                'data' => CompanyResource::collection($companies),
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $data = $request->validated();

            $actor = $request->user();
            $denied = $this->ensureManagePermissionForUserType($actor, $data['type'], creating: true);
            if ($denied !== null) {
                return $denied;
            }

            $payload = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'type' => $data['type'],
                'language' => $data['language'],
                'role_id' => $data['type'] === 'admin' ? (int) $data['role_id'] : null,
            ];
            if ($actor instanceof User && $actor->isCompanyOperator()) {
                $payload['type'] = 'client';
                $payload['role_id'] = null;
                $payload['company_id'] = $actor->id;
            }

            if ($payload['type'] === 'captain') {
                $payload['license_expiry_date'] = $data['license_expiry_date'] ?? null;
            }

            $user = $this->userService->createUser($payload);

            if ($user->type === 'captain') {
                $this->captainDocumentService->syncFromRequest($user, $request);
                $user = $user->fresh(['role']);
            }

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

            $actor = $request->user();
            $denied = $this->ensureViewPermissionForUser($actor, $user);
            if ($denied !== null) {
                return $denied;
            }

            $this->attachCaptainRatingProfile($user);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.retrieved'),
                'data' => new UserResource($user->load('car')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.users.server_error'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        try {
            $user = $this->userService->findActiveUserForAdmin($id);

            $actor = $request->user();
            $denied = $this->ensureManagePermissionForUserType($actor, $user->type, creating: false);
            if ($denied !== null) {
                return $denied;
            }
            $deniedCompany = $this->ensureCompanyOperatorOwnsClient($actor, $user);
            if ($deniedCompany !== null) {
                return $deniedCompany;
            }

            $data = $request->validated();

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
            if ($user->type === 'captain' && array_key_exists('license_expiry_date', $data)) {
                $update['license_expiry_date'] = $data['license_expiry_date'];
            }

            if ($user->type === 'captain' && array_key_exists('balance', $data)) {
                $update['balance'] = $data['balance'];
            }

            $user = $this->userService->updateUser($user, $update);

            if ($user->type === 'captain') {
                $this->captainDocumentService->syncFromRequest($user, $request);
                $user = $user->fresh(['role']);
            }

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
            $user = User::query()->findOrFail($id);
            /** @var \App\Models\User $actor */
            $actor = $request->user();

            $denied = $this->ensureManagePermissionForUserType($actor, $user->type, creating: false);
            if ($denied !== null) {
                return $denied;
            }
            $deniedCompany = $this->ensureCompanyOperatorOwnsClient($actor, $user);
            if ($deniedCompany !== null) {
                return $deniedCompany;
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

    public function restore(Request $request, int $id)
    {
        try {
            $trashed = User::onlyTrashed()->with(['role'])->findOrFail($id);

            $actor = $request->user();
            $denied = $this->ensureManagePermissionForUserType($actor, $trashed->type, creating: false);
            if ($denied !== null) {
                return $denied;
            }
            $deniedCompany = $this->ensureCompanyOperatorOwnsClient($actor, $trashed);
            if ($deniedCompany !== null) {
                return $deniedCompany;
            }

            $user = $this->userService->restoreUser($id);

            return response()->json([
                'status' => 'success',
                'message' => __('api.users.restored'),
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

    private function ensureListPermission(?User $actor, string $type): ?JsonResponse
    {
        if ($actor === null || ! $actor->hasPermission($this->listTypeViewPermission($type))) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.auth.forbidden_permission'),
            ], 403);
        }

        if ($actor->isCompanyOperator() && $type !== 'client') {
            return response()->json([
                'status' => 'error',
                'message' => __('api.auth.forbidden_permission'),
            ], 403);
        }

        return null;
    }

    private function listTypeViewPermission(string $type): string
    {
        return match ($type) {
            'client' => 'clients.view',
            'captain' => 'captains.view',
            'admin' => 'admin-users.view',
        };
    }

    /**
     * @return JsonResponse|null
     */
    private function ensureViewPermissionForUser(?User $actor, User $target): ?JsonResponse
    {
        if ($actor === null || ! $actor->hasPermission($this->listTypeViewPermission($target->type))) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.auth.forbidden_permission'),
            ], 403);
        }

        return $this->ensureCompanyOperatorOwnsClient($actor, $target);
    }

    /**
     * @return JsonResponse|null
     */
    private function ensureManagePermissionForUserType(?User $actor, string $type, bool $creating): ?JsonResponse
    {
        $permission = match ($type) {
            'client' => 'clients.manage',
            'captain' => 'captains.manage',
            'admin' => $creating ? 'admin-users.create' : 'admin-users.update',
        };

        if ($actor === null || ! $actor->hasPermission($permission)) {
            return response()->json([
                'status' => 'error',
                'message' => __('api.auth.forbidden_permission'),
            ], 403);
        }

        if ($actor->isCompanyOperator() && $type !== 'client') {
            return response()->json([
                'status' => 'error',
                'message' => __('api.auth.forbidden_permission'),
            ], 403);
        }

        return null;
    }

    /**
     * @return JsonResponse|null
     */
    private function ensureCompanyOperatorOwnsClient(?User $actor, User $target): ?JsonResponse
    {
        if ($actor?->isCompanyOperator()) {
            if ($target->type !== 'client' || (int) $target->company_id !== (int) $actor->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.auth.forbidden_permission'),
                ], 403);
            }
        }

        return null;
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
