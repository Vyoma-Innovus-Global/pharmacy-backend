<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubdivisionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'subdivision_id'           =>  $this->id,
            'subdivision_name'         =>  $this->name,
            // 'd_name'            =>  $this->district->d_name,
            'district_id'              =>  $this->district_id,
            'schcd'                 =>  $this->schcd,
           

            //'block_municipality'  =>  BlockMunicipalityResource::collection($this->whenLoaded('district')),
        ];
    }
}
