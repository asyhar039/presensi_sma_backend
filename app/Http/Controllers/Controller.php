<?php

namespace App\Http\Controllers;

use App\Models\User;

abstract class Controller
{
    protected function authUser(): User
    {
        /** @var User $user */
        $user = auth()->user();
        return $user;
    }
}