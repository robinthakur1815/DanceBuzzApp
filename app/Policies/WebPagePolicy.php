<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\User;
use App\WebPage;
use Illuminate\Auth\Access\HandlesAuthorization;

class WebPagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any web pages.
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
     * Determine whether the user can view the web page.
     *
     * @param  \App\User  $user
     * @param  \App\WebPage  $webPage
     * @return mixed
     */
    public function view(User $user, WebPage $webPage)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create web pages.
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
     * Determine whether the user can update the web page.
     *
     * @param  \App\User  $user
     * @param  \App\WebPage  $webPage
     * @return mixed
     */
    public function update(User $user, WebPage $webPage)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the web page.
     *
     * @param  \App\User  $user
     * @param  \App\WebPage  $webPage
     * @return mixed
     */
    public function delete(User $user, WebPage $webPage)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the web page.
     *
     * @param  \App\User  $user
     * @param  \App\WebPage  $webPage
     * @return mixed
     */
    public function restore(User $user, WebPage $webPage)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the web page.
     *
     * @param  \App\User  $user
     * @param  \App\WebPage  $webPage
     * @return mixed
     */
    public function forceDelete(User $user, WebPage $webPage)
    {
        if ($user->role_id = UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }
}
