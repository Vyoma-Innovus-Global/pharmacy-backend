<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EvaluatorDashboardController extends Controller
{
    /**
     * Get the Marks Entry Dashboard data for an Evaluator.
     * Returns institutes grouped with total_courses and pending_subjects.
     *
     * GET /evaluator/marks-entry-dashboard
     */
    public function dashboard(Request $request)
    {
        try {
            $evaluator_id = $request->query('evaluator_id');

            // In a real implementation, you would query the DB based on $evaluator_id
            // to fetch only institutes allocated to that evaluator.
            // e.g.: $allocated = EvaluatorAllocation::where('ev_id', $evaluator_id)->get();
            // For now, we return rich dummy data filtered/tagged by evaluator_id.

            $institutes = [
                [
                    'inst_id'          => 'INST001',
                    'inst_code'        => 'PH-01',
                    'inst_name'        => 'Govt Pharmacy College',
                    'inst_type'        => 'internal',
                    'inst_address'     => 'Kolkata, West Bengal',
                    'total_courses'    => 2,
                    'pending_subjects' => 4,
                ],
                [
                    'inst_id'          => 'INST002',
                    'inst_code'        => 'PH-02',
                    'inst_name'        => 'State Pharmacy Institute',
                    'inst_type'        => 'internal',
                    'inst_address'     => 'Howrah, West Bengal',
                    'total_courses'    => 2,
                    'pending_subjects' => 3,
                ],
                [
                    'inst_id'          => 'INST003',
                    'inst_code'        => 'PH-03',
                    'inst_name'        => 'Private Pharmacy Institute',
                    'inst_type'        => 'external',
                    'inst_address'     => 'Durgapur, West Bengal',
                    'total_courses'    => 2,
                    'pending_subjects' => 2,
                ],
                [
                    'inst_id'          => 'INST004',
                    'inst_code'        => 'PH-04',
                    'inst_name'        => 'Elite Medical & Pharmacy College',
                    'inst_type'        => 'external',
                    'inst_address'     => 'Siliguri, West Bengal',
                    'total_courses'    => 3,
                    'pending_subjects' => 5,
                ],
                [
                    'inst_id'          => 'INST005',
                    'inst_code'        => 'PH-05',
                    'inst_name'        => 'Bengal College of Pharmacy',
                    'inst_type'        => 'internal',
                    'inst_address'     => 'Barasat, West Bengal',
                    'total_courses'    => 1,
                    'pending_subjects' => 2,
                ],
                [
                    'inst_id'          => 'INST006',
                    'inst_code'        => 'PH-06',
                    'inst_name'        => 'North Bengal Pharmacy Institute',
                    'inst_type'        => 'external',
                    'inst_address'     => 'Jalpaiguri, West Bengal',
                    'total_courses'    => 2,
                    'pending_subjects' => 0,
                ],
            ];

            return response()->json([
                'error'        => false,
                'evaluator_id' => $evaluator_id,
                'institutes'   => $institutes,
                'summary'      => [
                    'total_institutes'       => count($institutes),
                    'internal_institutes'    => count(array_filter($institutes, fn($i) => $i['inst_type'] === 'internal')),
                    'external_institutes'    => count(array_filter($institutes, fn($i) => $i['inst_type'] === 'external')),
                    'total_pending_subjects' => array_sum(array_column($institutes, 'pending_subjects')),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get course list for a specific institute.
     *
     * GET /evaluator/marks-entry-courses?inst_id=INST001
     */
    public function courseList(Request $request)
    {
        $request->validate([
            'inst_id' => 'required|string',
        ]);

        try {
            $inst_id = $request->inst_id;

            // Dummy course data per institute
            $courseMap = [
                'INST001' => [
                    [
                        'course_id'     => 'COURSE001',
                        'course_code'   => 'DPHARM-1',
                        'course_name'   => 'D.Pharm 1st Year',
                        'total_pending' => 2,
                        'subjects'      => [
                            ['subject_code' => 'ER20-11T', 'subject_name' => 'Pharmaceutics Theory'],
                            ['subject_code' => 'ER20-11P', 'subject_name' => 'Pharmaceutics Practical'],
                        ],
                    ],
                    [
                        'course_id'     => 'COURSE002',
                        'course_code'   => 'DPHARM-2',
                        'course_name'   => 'D.Pharm 2nd Year',
                        'total_pending' => 2,
                        'subjects'      => [
                            ['subject_code' => 'ER20-21T', 'subject_name' => 'Pharmacology Theory'],
                            ['subject_code' => 'ER20-21P', 'subject_name' => 'Pharmacology Practical'],
                        ],
                    ],
                ],
                'INST002' => [
                    [
                        'course_id'     => 'COURSE003',
                        'course_code'   => 'DPHARM-1',
                        'course_name'   => 'D.Pharm 1st Year',
                        'total_pending' => 3,
                        'subjects'      => [
                            ['subject_code' => 'ER20-11T', 'subject_name' => 'Pharmaceutics Theory'],
                            ['subject_code' => 'ER20-12T', 'subject_name' => 'Pharmaceutical Chemistry Theory'],
                            ['subject_code' => 'ER20-13P', 'subject_name' => 'Human Anatomy Practical'],
                        ],
                    ],
                    [
                        'course_id'     => 'COURSE004',
                        'course_code'   => 'DPHARM-2',
                        'course_name'   => 'D.Pharm 2nd Year',
                        'total_pending' => 0,
                        'subjects'      => [
                            ['subject_code' => 'ER20-21T', 'subject_name' => 'Pharmacology Theory'],
                        ],
                    ],
                ],
            ];

            // Default courses for any institute not explicitly mapped
            $defaultCourses = [
                [
                    'course_id'     => 'COURSE_DEFAULT1',
                    'course_code'   => 'DPHARM-1',
                    'course_name'   => 'D.Pharm 1st Year',
                    'total_pending' => 2,
                    'subjects'      => [
                        ['subject_code' => 'ER20-11T', 'subject_name' => 'Pharmaceutics Theory'],
                        ['subject_code' => 'ER20-11P', 'subject_name' => 'Pharmaceutics Practical'],
                    ],
                ],
                [
                    'course_id'     => 'COURSE_DEFAULT2',
                    'course_code'   => 'DPHARM-2',
                    'course_name'   => 'D.Pharm 2nd Year',
                    'total_pending' => 1,
                    'subjects'      => [
                        ['subject_code' => 'ER20-21T', 'subject_name' => 'Pharmacology Theory'],
                    ],
                ],
            ];

            $courses = $courseMap[$inst_id] ?? $defaultCourses;

            return response()->json([
                'error'   => false,
                'inst_id' => $inst_id,
                'courses' => $courses,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
