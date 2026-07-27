<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GetReviewStudentDetailsByTeacherIdTest extends TestCase
{
    public function test_it_returns_review_student_details_for_a_teacher(): void
    {
        $students = [
            [
                'instCode' => 'AIHE',
                'marksStatus' => 'EXAM',
                'reviewMarks' => 45,
                'subjectCode' => 'SOPH',
                'studentRegistrationNumber' => 'PHARM242500625',
            ],
        ];

        DB::shouldReceive('selectOne')
            ->once()
            ->with(
                'SELECT public.fn_get_reviewstudentdetailsbyteacherid(?::bigint) AS data',
                [1051]
            )
            ->andReturn((object) ['data' => json_encode($students)]);

        $response = $this->withoutMiddleware()
            ->getJson('/api/review/student-details-by-teacher?teacher_id=1051');

        $response
            ->assertOk()
            ->assertJson([
                'error' => false,
                'data' => $students,
            ]);
    }

    public function test_it_requires_a_valid_teacher_id(): void
    {
        DB::shouldReceive('selectOne')->never();

        $this->withoutMiddleware()
            ->postJson('/api/review/student-details-by-teacher', [
                'teacher_id' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', true)
            ->assertJsonValidationErrors('teacher_id', 'data');
    }
}
