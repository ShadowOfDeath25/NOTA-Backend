<?php

namespace App\Observers;

use Illuminate\Support\Str;

class AssignsUUID
{
    public function creating($model)
    {
        if (empty($model->uuid)) {
            $model->uuid = (string)Str::uuid();
        }
    }
}
