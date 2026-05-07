<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'state_id'           =>  $this->state_id_pk,
            'state_name'         =>  $this->state_name,
        ];
    }
}
