<?php

namespace App\Policies;

use App\Collection as Blog;
use App\Enums\UserRole;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlogPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the blog.
     *
     * @param  \App\User  $user
     * @param  \App\Blog  $blog
     * @return mixed
     */
    public function view(User $user, Blog $blog)
    {
        return true;
    }

    /**
     * Determine whether the user can create blogs.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        // if ($user->email_verified_at) {
        return true;
        // }
        // return false;
    }

    /**
     * Determine whether the user can update the blog.
     *
     * @param  \App\User  $user
     * @param  \App\Blog  $blog
     * @return mixed
     */
    public function update(User $user, Blog $blog)
    {
        // return true;
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == UserRole::Approver) {
            return true;
        }
        if ($user->id == $blog->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can publish the blog.
     *
     * @param  \App\User  $user
     * @param  \App\Blog  $blog
     * @return mixed
     */
    public function publish(User $user, Blog $blog)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == UserRole::Approver) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the blog.
     *
     * @param  \App\User  $user
     * @param  \App\Blog  $blog
     * @return mixed
     */
    public function delete(User $user, Blog $blog)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == UserRole::Approver) {
            return true;
        }
        if ($user->id == $blog->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the blog.
     *
     * @param  \App\User  $user
     * @param  \App\Blog  $blog
     * @return mixed
     */
    public function restore(User $user, Blog $blog)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == UserRole::Approver) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the blog.
     *
     * @param  \App\User  $user
     * @param  \App\Blog  $blog
     * @return mixed
     */
    public function forceDelete(User $user, Blog $blog)
    {
        if ($user->role_id == UserRole::SuperAdmin) {
            return true;
        }

        return false;
    }
}
