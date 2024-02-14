<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\User;
use App\WebSection;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebSectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any web sections.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the web section.
     *
     * @param  \App\User  $user
     * @param  \App\WebSection  $webSection
     * @return mixed
     */
    public function view(User $user, WebSection $webSection)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create web sections.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the web section.
     *
     * @param  \App\User  $user
     * @param  \App\WebSection  $webSection
     * @return mixed
     */
    public function update(User $user, WebSection $webSection)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the web section.
     *
     * @param  \App\User  $user
     * @param  \App\WebSection  $webSection
     * @return mixed
     */
    public function delete(User $user, WebSection $webSection)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the web section.
     *
     * @param  \App\User  $user
     * @param  \App\WebSection  $webSection
     * @return mixed
     */
    public function restore(User $user, WebSection $webSection)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the web section.
     *
     * @param  \App\User  $user
     * @param  \App\WebSection  $webSection
     * @return mixed
     */
    public function forceDelete(User $user, WebSection $webSection)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }
}
