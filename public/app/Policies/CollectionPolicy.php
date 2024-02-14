<?php

namespace App\Policies;

use App\Collection;
use App\Enums\UserRole;
use App\Enums\RoleType;
use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CollectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any collections.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the collection.
     *
     * @param  \App\User  $user
     * @param  \App\Collection  $collection
     * @return mixed
     */
    public function view(User $user)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == RoleType::Vendor || $user->role_id == RoleType::School || $user->role_id == RoleType::VendorStaff || $user->role_id == RoleType::SchoolRepresentative) {
            return true;
        }
        $request = request();
        if ($user->client_id && $user->role_id == UserRole::VendorAdmin) {
            $allowedCollections = $this->getClientCollections();
        } else {
            $allowedCollections = $this->getAllowedCollections($user->role_id);
        }

        if (in_array($request->type, $allowedCollections['collection_type'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can create collections.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == RoleType::Vendor || $user->role_id == RoleType::School || $user->role_id == RoleType::VendorStaff || $user->role_id == RoleType::SchoolRepresentative) {
            return true;
        }
        $request = request();
        if ($user->client_id && $user->role_id == UserRole::VendorAdmin) {
            $allowedCollections = $this->getClientCollections();
        } else {
            $allowedCollections = $this->getAllowedCollections($user->role_id);
        }
        if (in_array($request->type, $allowedCollections['collection_type'])) {
            return true;
        } else {
            return false;
        }
        
    }

    /**
     * Determine whether the user can update the collection.
     *
     * @param  \App\User  $user
     * @param  \App\Collection  $collection
     * @return mixed
     */
    public function update(User $user)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == RoleType::Vendor || $user->role_id == RoleType::School || $user->role_id == RoleType::VendorStaff || $user->role_id == RoleType::SchoolRepresentative) {
            return true;
        }
        $request = request();
        if ($user->client_id && $user->role_id == UserRole::VendorAdmin) {
            $allowedCollections = $this->getClientCollections();
        } else {
            $allowedCollections = $this->getAllowedCollections($user->role_id);
        }
        if (in_array($request->type, $allowedCollections['collection_type'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine whether the user can delete the collection.
     *
     * @param  \App\User  $user
     * @param  \App\Collection  $collection
     * @return mixed
     */
    public function delete(User $user, Collection $collection)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == UserRole::Approver) {
            return true;
        }
        if ($user->id == $collection->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the collection.
     *
     * @param  \App\User  $user
     * @param  \App\Collection  $collection
     * @return mixed
     */
    public function restore(User $user, Collection $collection)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == UserRole::Approver) {
            return true;
        }
        if ($user->id == $collection->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the collection.
     *
     * @param  \App\User  $user
     * @param  \App\Collection  $collection
     * @return mixed
     */
    public function editCollection(User $user, Collection $collection)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == RoleType::Vendor || $user->role_id == RoleType::School || $user->role_id == RoleType::VendorStaff || $user->role_id == RoleType::SchoolRepresentative) {
            return true;
        }
        if ($user->client_id && $user->role_id == UserRole::VendorAdmin) {
            $allowedCollections = $this->getClientCollections();
        } else {
            $allowedCollections = $this->getAllowedCollections($user->role_id);
        }
        if (in_array($collection->collection_type, $allowedCollections['collection_type'])) {
            return true;
        } else {
            return false;
        }
    }

    public function getAllowedCollections($id)
    {
        $authCollections = [];
        $role = Role::find($id);
        if ($role) {
            $roleSlug = 'roles.'.$role->slug;
            $authCollections = config($roleSlug);
        }

        return $authCollections;
    }

    private function getClientCollections()
    {
        $roleSlug = 'roles.'.'client_admin';
        $authCollections = config($roleSlug);

        return $authCollections;
    }

    public function updateAny(User $user, Collection $collection)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == RoleType::Vendor || $user->role_id == RoleType::School || $user->role_id == RoleType::VendorStaff || $user->role_id == RoleType::SchoolRepresentative) {
            return true;
        }

        if ($user->client_id && $user->role_id == UserRole::VendorAdmin) {
            $allowedCollections = $this->getClientCollections();
        } else {
            $allowedCollections = $this->getAllowedCollections($user->role_id);
        }

        if (in_array($collection->collection_type, $allowedCollections['collection_type'])) {
            return true;
        } else {
            return false;
        }
    }

    public function publish(User $user, Collection $collection)
    {
        if ($user->role_id == UserRole::SuperAdmin || $user->role_id == UserRole::Approver || $user->role_id == RoleType::Vendor || $user->role_id == RoleType::School || $user->role_id == RoleType::VendorStaff || $user->role_id == RoleType::SchoolRepresentative) {
            return true;
        } elseif ($user->role_id == UserRole::VendorAdmin) {
            $allowedCollections = $this->getClientCollections();
            if (in_array($collection->collection_type, $allowedCollections['collection_type'])) {
                return true;
            }
        }

        return false;
    }
}
