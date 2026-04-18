<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CaptainService
{
    /**
     * @return Builder<User>
     */
    public function getAllCaptains(): Builder
    {
        return User::query()
            ->where('type', 'captain')
            ->whereNull('deleted_at');
    }

    public function getCaptainById(int $id): User
    {
        return $this->getAllCaptains()->findOrFail($id);
    }

    /**
     * @param  array{name: string, phone: string, password: string}  $data
     */
    public function createCaptain(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'type' => 'captain',
            'language' => 'en',
            'role_id' => null,
            'email' => null,
        ]);
    }

    public function updateCaptain(Request $request, int $id): User
    {
        $captain = $this->getCaptainById($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'phone')->where(
                    fn ($query) => $query->where('type', 'captain'),
                )->ignore($captain->id),
            ],
            'password' => 'sometimes|nullable|string|min:6|confirmed',
        ]);
        if (isset($data['name'])) {
            $captain->name = $data['name'];
        }
        if (isset($data['phone'])) {
            $captain->phone = $data['phone'];
        }
        if (! empty($data['password'])) {
            $captain->password = $data['password'];
        }
        $captain->save();

        return $captain->fresh();
    }

    public function deleteCaptain(int $id): void
    {
        $this->getCaptainById($id)->delete();
    }
}
