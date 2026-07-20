<?php

namespace App\Policies;

use App\Models\User;

class AssetModelPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'models';
    }

    /**
     * READ ability for model files (index / show / download). Cascades from
     * assets.files because a model's file attachments (user manuals, spec
     * sheets, etc.) show up on the asset detail page and are legitimately
     * useful to anyone who can see the asset itself.
     */
    public function files(User $user, $item = null)
    {
        if ($user->hasAccess('assets.files')) {
            return true;
        }

        return $user->hasAccess($this->columnName().'.files');
    }

    /**
     * WRITE ability for model files (upload / delete). Strict: requires the
     * dedicated `models.files` grant. The read cascade above must NOT
     * short-circuit here or an admin who withheld `models.files` while
     * granting `assets.files` to routine technicians would still see them
     * mutating the shared model file catalog.
     */
    public function manageFiles(User $user, $item = null)
    {
        return $user->hasAccess($this->columnName().'.files');
    }
}
