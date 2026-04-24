<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
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
                'message' => __('api.roles.list_retrieved'),
                'data' => RoleResource::collection($roles),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => __('api.roles.server_error')], 500);
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
                'message' => __('api.roles.created'),
                'data' => new RoleResource($role),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
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
                'message' => __('api.roles.updated'),
                'data' => new RoleResource($role),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $role = $this->roleService->getRoleById($id);

            return response()->json([
                'status' => 'success',
                'message' => __('api.roles.retrieved'),
                'data' => new RoleResource($role),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => __('api.roles.server_error')], 500);
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
                'message' => __('api.roles.deleted'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => __('api.roles.server_error')], 500);
        }
    }
}
