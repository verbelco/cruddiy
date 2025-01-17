<?php

namespace App\Http\Resources\Manager\Crud\{modelName};

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\{modelName} */
class {modelName}Resource extends JsonResource
{
    public function toArray($request)
    {
        return [
{columns}
        ];
    }
}
