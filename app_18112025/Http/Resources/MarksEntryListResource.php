<?php

namespace App\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarksEntryListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $special_paper_code = $request->special_paper_code;
        $special_paper_type = $request->special_paper_type;

        $theory = $this->theoryMarks($request->paper_type, $special_paper_code, $special_paper_type);
        $practical = $this->practicalMarks($request->paper_type, $special_paper_code, $special_paper_type);
        $project = $this->projectMarks($request->paper_type, $special_paper_code, $special_paper_type);
        $approve = $this->checkApproval($request->paper_type, $special_paper_code, $special_paper_type);
        $final = $this->checkFinal($request->paper_type, $special_paper_code, $special_paper_type);

        $present = (bool)$this->presentAttend($request->mark_type);
        $absent = (bool)$this->absentAttend($request->mark_type);
        $ra = (bool)$this->raAttend($request->mark_type);

        $fname = optional($this->student)->first_name;
        $mname = optional($this->student)->middle_name;
        $lname = optional($this->student)->last_name;

        return [
            'reg_no' => $this->reg_no,
            'stu_name' => Str::replace('  ', ' ', "{$fname} {$mname} {$lname}"),

            'is_present' => $present,
            'is_absent' => $absent,
            'is_ra' => $ra,

            'theory' => !is_null($theory) ? (is_integer($theory) ? (int)$theory : $theory) : ($absent ? 'AB' : ($ra ? 'RA' : null)),
            'practical' => !is_null($practical) ? (is_integer($practical) ? (int)$practical : $practical) : ($absent ? 'AB' : ($ra ? 'RA' : null)),
            'project' => !is_null($project) ? (is_integer($project) ? (int)$project : $project) : ($absent ? 'AB' : ($ra ? 'RA' : null)),

            'max_theory' => (int)$this->papermarks->theory_marks,
            'max_practical' => (int)$this->papermarks->practical_marks,
            'max_project' => (int)$this->papermarks->project_marks,

            'is_approved' => $approve,
            'is_final' => $final,
        ];
    }

    private function theoryMarks($paper_type, $special_paper, $special_paper_type)
    {
        if ($paper_type == 'paper1') {
            return optional($this->markxi)->p1_theory;
        }
        if ($paper_type == 'paper2') {
            return optional($this->markxi)->p2_theory;
        }
        if ($paper_type == 'paper3') {
            return optional($this->markxi)->p3_theory;
        }
        if ($paper_type == 'paper4') {
            return optional($this->markxi)->p4_theory;
        }
        if ($paper_type == 'paper5') {
            if (!is_null($special_paper) && $special_paper_type == 'paper5_1') {
                return optional($this->markxi)->p5_1_theory;
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper5_2') {
                return optional($this->markxi)->p5_2_theory;
            } else {
                return optional($this->markxi)->p5_1_theory;
            }
        }
        if ($paper_type == 'paper6') {
            if (!is_null($special_paper) && $special_paper_type == 'paper6_1') {
                return optional($this->markxi)->p6_1_theory;
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper6_2') {
                return optional($this->markxi)->p6_2_theory;
            } else {
                return optional($this->markxi)->p6_1_theory;
            }
        }
        if ($paper_type == 'paper7') {
            if (!is_null($special_paper) && $special_paper_type == 'paper7_1') {
                return optional($this->markxi)->p7_1_theory;
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper7_2') {
                return optional($this->markxi)->p7_2_theory;
            } else {
                return optional($this->markxi)->p7_1_theory;
            }
        }
    }

    private function practicalMarks($paper_type, $special_paper, $special_paper_type)
    {
        if ($paper_type == 'paper1') {
            return optional($this->markxi)->p1_practical;
        }
        if ($paper_type == 'paper2') {
            return optional($this->markxi)->p2_practical;
        }
        if ($paper_type == 'paper3') {
            return optional($this->markxi)->p3_practical;
        }
        if ($paper_type == 'paper4') {
            return optional($this->markxi)->p4_practical;
        }
        if ($paper_type == 'paper5') {
            if (!is_null($special_paper) && $special_paper_type == 'paper5_1') {
                return optional($this->markxi)->p5_1_practical;
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper5_2') {
                return optional($this->markxi)->p5_2_practical;
            } else {
                return optional($this->markxi)->p5_1_practical;
            }
        }
        if ($paper_type == 'paper6') {
            if (!is_null($special_paper) && $special_paper_type == 'paper6_1') {
                return optional($this->markxi)->p6_1_practical;
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper6_2') {
                return optional($this->markxi)->p6_2_practical;
            } else {
                return optional($this->markxi)->p6_1_practical;
            }
        }
        if ($paper_type == 'paper7') {
            if (!is_null($special_paper) && $special_paper_type == 'paper7_1') {
                return optional($this->markxi)->p7_1_practical;
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper7_2') {
                return optional($this->markxi)->p7_2_practical;
            } else {
                return optional($this->markxi)->p7_1_practical;
            }
        }
    }

    private function projectMarks($paper_type, $special_paper, $special_paper_type)
    {
        if ($paper_type == 'paper1') {
            return optional($this->markxi)->p1_project;
        }
        if ($paper_type == 'paper2') {
            return optional($this->markxi)->p2_project;
        }
        if ($paper_type == 'paper3') {
            return optional($this->markxi)->p3_project;
        }
        if ($paper_type == 'paper4') {
            return optional($this->markxi)->p4_project;
        }
        if ($paper_type == 'paper5') {
            if (!is_null($special_paper) && $special_paper_type == 'paper5_1') {
                return optional($this->markxi)->p5_1_project;
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper5_2') {
                return optional($this->markxi)->p5_2_project;
            } else {
                return optional($this->markxi)->p5_1_project;
            }
        }
        if ($paper_type == 'paper6') {
            if (!is_null($special_paper) && $special_paper_type == 'paper6_1') {
                return optional($this->markxi)->p6_1_project;
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper6_2') {
                return optional($this->markxi)->p6_2_project;
            } else {
                return optional($this->markxi)->p6_1_project;
            }
        }
        if ($paper_type == 'paper7') {
            if (!is_null($special_paper) && $special_paper_type == 'paper7_1') {
                return optional($this->markxi)->p7_1_project;
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper7_2') {
                return optional($this->markxi)->p7_2_project;
            } else {
                return optional($this->markxi)->p7_1_project;
            }
        }
    }

    private function checkApproval($paper_type, $special_paper, $special_paper_type)
    {
        if ($paper_type == 'paper1') {
            return json_decode(optional($this->approvexi)->is_p1_approved);
        }

        if ($paper_type == 'paper2') {
            return json_decode(optional($this->approvexi)->is_p2_approved);
        }

        if ($paper_type == 'paper3') {
            return json_decode(optional($this->approvexi)->is_p3_approved);
        }

        if ($paper_type == 'paper4') {
            return json_decode(optional($this->approvexi)->is_p4_approved);
        }

        if ($paper_type == 'paper5') {
            if (!is_null($special_paper) && $special_paper_type == 'paper5_1') {
                return json_decode(optional($this->approvexi)->is_p5_1_approved);
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper5_2') {
                return json_decode(optional($this->approvexi)->is_p5_2_approved);
            } else {
                return json_decode(optional($this->approvexi)->is_p5_1_approved);
            }
        }

        if ($paper_type == 'paper6') {
            if (!is_null($special_paper) && $special_paper_type == 'paper6_1') {
                return json_decode(optional($this->approvexi)->is_p6_1_approved);
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper6_2') {
                return json_decode(optional($this->approvexi)->is_p6_2_approved);
            } else {
                return json_decode(optional($this->approvexi)->is_p6_1_approved);
            }
        }

        if ($paper_type == 'paper7') {
            if (!is_null($special_paper) && $special_paper_type == 'paper7_1') {
                return json_decode(optional($this->approvexi)->is_p7_1_approved);
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper7_2') {
                return json_decode(optional($this->approvexi)->is_p7_2_approved);
            } else {
                return json_decode(optional($this->approvexi)->is_p7_1_approved);
            }
        }
    }

    private function checkFinal($paper_type, $special_paper, $special_paper_type)
    {
        if ($paper_type == 'paper1') {
            return json_decode(optional($this->markxi)->is_p1_final);
        }

        if ($paper_type == 'paper2') {
            return json_decode(optional($this->markxi)->is_p2_final);
        }

        if ($paper_type == 'paper3') {
            return json_decode(optional($this->markxi)->is_p3_final);
        }

        if ($paper_type == 'paper4') {
            return json_decode(optional($this->markxi)->is_p4_final);
        }

        if ($paper_type == 'paper5') {
            if (!is_null($special_paper) && $special_paper_type == 'paper5_1') {
                return json_decode(optional($this->markxi)->is_p5_1_final);
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper5_2') {
                return json_decode(optional($this->markxi)->is_p5_2_final);
            } else {
                return json_decode(optional($this->markxi)->is_p5_1_final);
            }
        }

        if ($paper_type == 'paper6') {
            if (!is_null($special_paper) && $special_paper_type == 'paper6_1') {
                return json_decode(optional($this->markxi)->is_p6_1_final);
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper6_2') {
                return json_decode(optional($this->markxi)->is_p6_2_final);
            } else {
                return json_decode(optional($this->markxi)->is_p6_1_final);
            }
        }

        if ($paper_type == 'paper7') {
            if (!is_null($special_paper) && $special_paper_type == 'paper7_1') {
                return json_decode(optional($this->markxi)->is_p7_1_final);
            } elseif (!is_null($special_paper) && $special_paper_type == 'paper7_2') {
                return json_decode(optional($this->markxi)->is_p7_2_final);
            } else {
                return json_decode(optional($this->markxi)->is_p7_1_final);
            }
        }
    }

    private function presentAttend($mark_type)
    {
        return match ($mark_type) {
            'MARK_THEORY' => $this->is_theory_present,
            'MARK_PRACTICAL' => $this->is_practical_present,
            'MARK_PROJECT' => $this->is_project_present,
        };
    }
    private function absentAttend($mark_type)
    {
        return match ($mark_type) {
            'MARK_THEORY' => $this->is_theory_absent,
            'MARK_PRACTICAL' => $this->is_practical_absent,
            'MARK_PROJECT' => $this->is_project_absent,
        };
    }
    private function raAttend($mark_type)
    {
        return match ($mark_type) {
            'MARK_THEORY' => $this->is_theory_ra,
            'MARK_PRACTICAL' => $this->is_practical_ra,
            'MARK_PROJECT' => $this->is_project_ra,
        };
    }
}
