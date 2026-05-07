<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "part" => $this->part,
            "inst_code" => $this->inst_code,
            "exam_year" => (string) $this->exam_year,
            "academic_session" => $this->academic_session,
            "mark_type" => $this->mark_type,
            'mark_name' => str_replace('_', ' ', $this->mark_type),
            "schedule_type" => $this->schedule_type,
            'schedule_name' => str_replace('_', ' ', $this->schedule_type),
            "start_date" => Carbon::parse($this->start_date)->format("Y-m-d h:i A"),
            "end_date" => Carbon::parse($this->end_date)->format("Y-m-d h:i A"),
            "late_date" => !is_null($this->late_start_at) ? Carbon::parse($this->late_start_at)->format("Y-m-d h:i A") : null,
            'is_active' => (bool) $this->active_status,
            'is_active_always' => (bool) $this->is_active_always,
        ];
    }
}
