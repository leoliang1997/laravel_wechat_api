<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LessonPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function teacher(User $user)
    {
        return $user->isTeacher();
    }

    public function student(User $user)
    {
        return $user->isStudent();
    }
}
