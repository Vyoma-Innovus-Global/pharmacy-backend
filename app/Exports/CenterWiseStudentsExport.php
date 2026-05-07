<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CenterWiseStudentsExport implements FromCollection, WithHeadings
{
    protected $part_sem, $exam_year, $center_code;

    public function __construct($part_sem, $exam_year, $center_code)
    {
        $this->part_sem = $part_sem;
        $this->exam_year = $exam_year;
        $this->center_code = $center_code;
    }

    public function collection()
    {
        return DB::table('exam_attendance_pone AS ea')
            ->select(
                DB::raw("(rolln.roll ||  '-' || rolln.no_prefix || rolln.number) AS roll_number"),
                'reg.s_appl_reg_no',
                'rolln.enrl_type',
                'reg.s_candidate_name',
                'inst.i_name AS institute_name',
                'ea.ea_inst_code',
                'ea.ea_center_code',
                'ea.ea_part_sem',
                'ea.ea_exam_year',

                // subject id list
                DB::raw("STRING_AGG(ea.ea_subject_id::text, ',') AS subject_ids"),

                // subject Q-CODE list
                DB::raw("STRING_AGG(sub.q_code::text, ',') AS q_codes")
            )

            // JOIN register table
            ->join('pharmacy_register_student_final AS reg', 'reg.s_appl_form_num', '=', 'ea.ea_form_number')

            // JOIN institute master
            ->join('institute_master AS inst', 'inst.i_code', '=', 'ea.ea_inst_code')

            // JOIN roll table
            ->join('pharmacy_roll_no AS rolln', 'rolln.form_no', '=', 'reg.s_appl_form_num')

            // JOIN subject master (IMPORTANT)
            ->leftJoin('pharmacy_subjects_master AS sub', 'sub.subject_id', '=', 'ea.ea_subject_id')

            ->where('ea.ea_part_sem', $this->part_sem)
            ->where('rolln.part_sem', $this->part_sem)
            ->where('ea.ea_center_code', $this->center_code)
            ->where('ea.ea_exam_year', $this->exam_year)
            ->where('rolln.exam_year', $this->exam_year)
            ->where('ea.ea_center_type', 'AWAY')

            ->groupBy(
                'ea.ea_inst_code',
                'ea.ea_center_code',
                'ea.ea_part_sem',
                'ea.ea_exam_year',
                'reg.s_appl_reg_no',
                'reg.s_candidate_name',
                'inst.i_name',
                'rolln.enrl_type',
                'rolln.roll',
                'rolln.no_prefix',
                'rolln.number'
            )

            ->orderBy('roll_number')
            ->get();

    }

    public function headings(): array
    {
        return [
            'Roll No',
            'Reg No',
            'Type',
            'Candidate Name',
            'Institute Name',
            'Institute Code',
            'Center Code',
            'Part/Sem',
            'Exam Year',
            'Subject IDs',
            'Q-Codes'
        ];
    }
}
