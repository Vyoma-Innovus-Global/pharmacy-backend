<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GetTeacherInfoByInstituteTest extends TestCase
{
    public function test_it_returns_teacher_information_for_an_institute(): void
    {
        $teachers = [
            [
                'teacherId' => 303,
                'teacherName' => 'ANKUR MAITY',
                'teacherInstCode' => 'CIP',
                'teacherInstName' => 'CALCUTTA INSTITUTE',
                'teacherPhoneNumber' => '8145594997',
                'totalAssignedReview' => 0,
            ],
        ];

        DB::shouldReceive('selectOne')
            ->once()
            ->with(
                'SELECT public.fn_get_teacherinfobyinst(?::varchar) AS data',
                ['CIP']
            )
            ->andReturn((object) ['data' => json_encode($teachers)]);

        $response = $this->withoutMiddleware()
            ->getJson('/api/review/teacher-info-by-institute?inst_code=cip');

        $response
            ->assertOk()
            ->assertJson([
                'error' => false,
                'data' => $teachers,
            ]);
    }

    public function test_it_requires_an_institute_code(): void
    {
        DB::shouldReceive('selectOne')->never();

        $this->withoutMiddleware()
            ->getJson('/api/review/teacher-info-by-institute')
            ->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonValidationErrors('inst_code', 'data');
    }
}
