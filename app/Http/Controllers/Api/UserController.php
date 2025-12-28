<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\IndexUserRequest;
use App\Http\Requests\Api\User\StoreUserRequest;
use App\Http\Requests\Api\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    use ApiResponse;

    public function index(IndexUserRequest $request)
    {
        $query = User::query();

        if ($request->getSearch()) {
            $search = $request->getSearch();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->role($request->input('role'));
        }

        $query->orderBy($request->getSortBy(), $request->getSortDir());

        $users = $query->paginate($request->getPerPage());

        return $this->paginatedResponse(
            'User berhasil ditampilkan',
            UserResource::collection($users)
        );
    }

    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'       => $validated['name'],
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
                'created_by' => Auth::id(),
            ]);

            $user->assignRole('admin');

            return $user;
        });

        return $this->createdResponse(
            'User berhasil dibuat',
            new UserResource($user)
        );
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user = DB::transaction(function () use ($request, $user) {
            $user->update($request->validated());
            $user->refresh();

            return $user;
        });

        return $this->successResponse(
            'User berhasil diperbarui',
            new UserResource($user)
        );
    }

    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->update([
                'deleted_by' => Auth::id(),
            ]);

            $user->delete();
        });

        return $this->noContentResponse();
    }
}
