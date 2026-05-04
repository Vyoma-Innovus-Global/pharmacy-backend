<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuperUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->u_id,
            'user_ref' => $this->u_ref,
            'user_name' => $this->u_username,
            'inst_code' => $this->u_inst_code ?? '',
            'inst_name' => $this->u_inst_name ?? '',
            'full_name' => $this->u_fullname,
            'user_role' => ($this->u_role_id == 3) ? "Collage Admin" : "Council Admin",
            'user_role_id' => $this->u_role_id,
            'is_default_pwd' => $this->is_default_password
        ];
    }
}
