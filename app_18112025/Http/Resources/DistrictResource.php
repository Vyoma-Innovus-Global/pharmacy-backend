<?php
 
namespace App\Http\Resources;
 
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
 
class DistrictResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'district_id'           =>  $this->district_id_pk,
            'district_name'         =>  $this->district_name,
            // 'district_code'            =>  $this->d_code,
        ];
    }
}