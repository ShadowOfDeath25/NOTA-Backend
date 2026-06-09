<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Note;
use App\Models\Space;
use App\Models\User;

class NotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Note $note): bool
    {
        return $note->user_id === $user->id
            || ($note->space_id && $note->space->users()->where('user_id', $user->id)->exists());
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Space $space = null): bool
    {
        return ! $space || in_array($space->userRole($user), [Role::ADMIN->value, Role::OWNER->value, Role::EDITOR->value]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Note $note, ?Space $space = null): bool
    {
        return $space ? in_array([Role::ADMIN->value, Role::OWNER->value, Role::EDITOR->value], $space->userRole($user)) : $note->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Note $note, ?Space $space = null): bool
    {
        return $space ? in_array([Role::ADMIN->value, Role::OWNER->value, Role::EDITOR->value], $space->userRole($user)) : $note->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Note $note, ?Space $space = null): bool
    {
        return $space ? in_array([Role::ADMIN->value, Role::OWNER->value, Role::EDITOR->value], $space->userRole($user)) : $note->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Note $note, ?Space $space = null): bool
    {
        return $space ? in_array([Role::ADMIN->value, Role::OWNER->value, Role::EDITOR->value], $space->userRole($user)) : $note->user_id === $user->id;
    }
}
