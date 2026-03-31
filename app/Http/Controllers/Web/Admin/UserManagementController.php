<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(): Response
    {
        $users = User::with('profile')
            ->latest()
            ->paginate(20);

        return Inertia::render('admin/users', [
            'users' => UserResource::collection($users),
        ]);
    }
}
