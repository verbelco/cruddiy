<?php

namespace App\Http\Resources\Manager\Crud;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class {modelName}Resource
 *
 * @mixin \App\Models\{modelName}
 * */
class {modelName}Resource extends JsonResource
{
    public function toArray($request)
    {
        return [
{columns}
        ];
    }
}
