<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Http\Resources\PermissionResource;

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

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name_en' => 'required|string|max:255',
                'name_ar' => 'required|string|max:255',
                'description_en' => 'required|string|max:255',
                'description_ar' => 'required|string|max:255',
                'permissions' => 'required|array',
                'permissions.*' => 'required|exists:permissions,id',
                'parent_id' => 'nullable|exists:roles,id',
            ]);
            $role = $this->roleService->createRole($request->all());

            if (isset($request->permissions)) {
                $role->permissions()->attach($request->permissions);
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

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name_en' => 'nullable|string|max:255',
                'name_ar' => 'nullable|string|max:255',
                'description_en' => 'nullable|string|max:255',
                'description_ar' => 'nullable|string|max:255',
                'permissions' => 'nullable|array',
                'permissions.*' => 'required|exists:permissions,id',
                'parent_id' => 'nullable|exists:roles,id',
            ]);
            $role = $this->roleService->updateRole($request->all(), $id);
            if (isset($request->permissions)) {
                $role->permissions()->sync($request->permissions);
            }

            return response()->json([
                'status' => 'success',
                'message' => $this->localizedMessage('api.roles.updated'),
                'data' => new RoleResource($role),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $this->localizedMessage('api.roles.server_error')], 500);
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
            return response()->json(['status' => 'error', 'message' => $this->localizedMessage('api.roles.server_error')], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // detach all permissions from the role
            $role = $this->roleService->getRoleById($id);
            $role->permissions()->detach();
            $role->delete();

            return response()->json([
                'status' => 'success',
                'message' => $this->localizedMessage('api.roles.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $this->localizedMessage('api.roles.server_error')], 500);
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
