<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockMunicipalityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'block_muni_id'         =>  $this->id,
            
            'block_muni_name'       =>  $this->name,
  
            'block_muni_active'     =>  $this->active_status,
            //'block_muni_district_id'=>  $this->district_id,
            'block_muni_subdiv'     =>  $this->subdivision_id,
          
            'block_muni_dist_name'  =>  DistrictResource::collection($this->whenLoaded('district')),
        ];
    }
}
