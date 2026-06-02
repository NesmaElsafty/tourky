<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use App\Models\Role;
use App\Models\Permission;
use App\Http\Resources\PermissionResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller
{
    private function localizedMessage(string $key): string
    {
        $language = auth()->user()?->language;
        $locale = in_array($language, ['en', 'ar'], true) ? $language : 'en';

        return __($key, [], $locale);
    }

    // use service to get the data
    public function __construct(private RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        try {
            $roles = $this->roleService->getAllRoles();

            return response()->json([
                'status' => 'success',
                'message' => $this->localizedMessage('api.roles.list_retrieved'),
                'data' => RoleResource::collection($roles),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $this->localizedMessage('api.roles.server_error')], 500);
        }
    }

    public function store(StoreRoleRequest $request)
    {
        try {
            $data = $request->validated();
            $role = $this->roleService->createRole($data);

            if (isset($data['permissions'])) {
                $role->permissions()->attach($data['permissions']);
            }

            return response()->json([
                'status' => 'success',
                'message' => $this->localizedMessage('api.roles.created'),
                'data' => new RoleResource($role),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $this->localizedMessage('api.roles.server_error')], 500);
        }
    }

    public function update(UpdateRoleRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $role = Role::query()->findOrFail($id);
            $role = $this->roleService->updateRole($data, $role);

            if (isset($data['permissions'])) {
                $role->permissions()->sync($data['permissions']);
            }

            return response()->json([
                'status' => 'success',
                'message' => $this->localizedMessage('api.roles.updated'),
                'data' => new RoleResource($role),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $role = $this->roleService->getRoleById($id);

            return response()->json([
                'status' => 'success',
                'message' => $this->localizedMessage('api.roles.retrieved'),
                'data' => new RoleResource($role),
            ]);
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                throw $e;
            }
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // detach all permissions from the role
            $role = Role::query()->findOrFail($id);
            if($role->users()->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('api.roles.has_users'),
                ], 400);
            }
            $role->permissions()->detach();
            $role->delete();
            return response()->json([
                'status' => 'success',
                'message' => __('api.roles.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // get all permissions
    public function getPermissions()
    {
        try {
            $permissions = Permission::all();
            return response()->json([
                'status' => 'success',
                'message' => $this->localizedMessage('api.permissions.list_retrieved'),
                'data' => PermissionResource::collection($permissions),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $this->localizedMessage('api.permissions.server_error')], 500);
        }
    }
}
