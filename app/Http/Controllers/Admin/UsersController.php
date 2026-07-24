<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    function index(Request $request)
    {

        $perPage =
            $request->per_page ?? 10;

        $search =
            $request->search;

        $query = User::query();

        if ($search) {

            $query->where(
                'name',
                'like',
                "%{$search}%"
            );
        }

        return $query
            ->paginate($perPage);
    }
    
}
