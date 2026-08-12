<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Certificate</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 210mm;
            height: 297mm;
            font-family: "Times New Roman", Times, serif;
            font-size: 13px;
            color: #000;
        }

        .page-container {
            position: relative;
            width: 210mm;
            height: 297mm;
            margin: 0;
            padding: 0;
        }

        .field {
            position: absolute;
            white-space: nowrap;
            font-family: "Times New Roman", Times, serif;
        }

        .bold {
            font-weight: bold;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @php
        $studentsList = isset($students) ? $students : (isset($data) ? [$data] : [[]]);
    @endphp

    @foreach($studentsList as $studentData)
        <div class="page-container">
            @php
                // -------------------------------------------------------------
                // SCALABLE COORDINATE CONFIGURATION (Y = Top, X = Left)
                // Edit any coordinate here to rapidly update field placement
                // -------------------------------------------------------------
                $coords = array_merge([
                    // Header / Candidate Info
                    'serial_no'          => ['y' => '4.2cm',  'x' => '17.5cm'],
                    'part_no'            => ['y' => '4.6cm',  'x' => '7.5cm'],
                    'exam_date'          => ['y' => '4.6cm',  'x' => '7.5cm'],
                    'reg_no'             => ['y' => '5.5cm',  'x' => '5cm'],
                    'roll'               => ['y' => '5.5cm',  'x' => '10cm'],
                    'number'             => ['y' => '5.5cm',  'x' => '14cm'],
                    'candidate_image'    => ['y' => '5.3cm',  'x' => '16cm'],
                    'student_name'       => ['y' => '6.5cm',  'x' => '6.5cm', 'max_width' => '9.2cm'],
                    'parent_name'        => ['y' => '7.2cm',  'x' => '6.5cm', 'max_width' => '9.2cm'],
                    'branch'             => ['y' => '8cm',    'x' => '6.5cm', 'max_width' => '9.2cm'],
                    'institution_name'   => ['y' => '8.8cm',  'x' => '6.5cm', 'max_width' => '9.2cm'],

                    // Theoretical Section
                    'theory_header'      => ['y' => '10.5cm', 'x' => '2.5cm'],
                    'theory_cols'        => ['label' => '3cm', 'total' => '14cm', 'pass' => '16cm', 'obtained' => '18cm'],
                    'theory_row_ys'      => ['11.5cm', '12cm', '12.5cm', '13cm', '13.5cm'],

                    // Sessional Section
                    'sessional_header'   => ['y' => '15.5cm', 'x' => '2.5cm'],
                    'sessional_cols'     => ['label' => '3cm', 'total' => '14cm', 'pass' => '16cm', 'obtained' => '18cm'],
                    'sessional_row_ys'   => ['16.5cm', '17cm', '17.5cm', '18cm', '18.5cm'],

                    // Footer Section
                    'total_marks'        => ['y' => '20.5cm', 'x' => '13.5cm'],
                    'obtained_marks'     => ['y' => '20.5cm', 'x' => '18cm'],
                    'back_papers'        => ['y' => '21.8cm', 'x' => '2.5cm'],
                    'percentage'         => ['y' => '22.3cm', 'x' => '13.8cm'],
                    'result_status'      => ['y' => '22.3cm', 'x' => '16.9cm'],
                    'cautionary_text'    => ['y' => '23.5cm', 'x' => '1.7cm'],
                    'signature'          => ['y' => '24cm',   'x' => '14.3cm'],
                    'issue_date'         => ['y' => '25cm',   'x' => '4.7cm'],
                ], $studentData['coords'] ?? $data['coords'] ?? []);

                // Pre-build CSS position style strings array
                $st = [];
                foreach ($coords as $k => $c) {
                    if (is_array($c) && isset($c['y'], $c['x'])) {
                        $style = 'top: ' . $c['y'] . '; left: ' . $c['x'] . ';';
                        if (!empty($c['max_width'])) {
                            $style .= ' max-width: ' . $c['max_width'] . '; white-space: normal; line-height: 1.15;';
                        }
                        $st[$k] = $style;
                    }
                }

                // Data bindings (supports both API response payload keys and standard keys)
                $serial_no = $studentData['serialNumber'] ?? $studentData['serial_no'] ?? 'P1/01191/11-24';
                $issue_date = $studentData['issueDate'] ?? $studentData['issue_date'] ?? '13th June, 2025';

                $part_no = $studentData['partNumber'] ?? $studentData['part_no'] ?? 'PART-I';
                $exam_date = $studentData['examDate'] ?? $studentData['exam_date'] ?? 'HELD IN NOVEMBER, 2024';

                $reg_no = $studentData['registrationNumber'] ?? $studentData['st_reg_number'] ?? 'PHARM232400007';
                $roll = $studentData['roll'] ?? $studentData['rollNo'] ?? $studentData['st_roll'] ?? 'PBCDAPHARM1';
                $number = $studentData['number'] ?? $studentData['no'] ?? $studentData['st_number'] ?? '10000008';

                $student_image = $studentData['photoUrl'] ?? $studentData['photo'] ?? $studentData['st_profile_img'] ?? null;
                $student_name = $studentData['studentName'] ?? $studentData['st_full_name'] ?? 'SANDEEPAN SAHA';
                $parent_name = $studentData['fatherMotherName'] ?? $studentData['st_gur_name'] ?? 'PRANAB SAHA';
                $branch = $studentData['branchOfStudy'] ?? $studentData['st_branch'] ?? 'PHARMACY';
                $institution_name = $studentData['instName'] ?? $studentData['st_institute_name'] ?? 'B.C.D.A. COLLEGE OF PHARMACY & TECHNOLOGY, CAMPUS-2';

                // Process Subjects if passed from API
                $theory_subjects = [];
                $sessional_subjects = [];

                if (!empty($studentData['subjects']) && is_array($studentData['subjects'])) {
                    foreach ($studentData['subjects'] as $sub) {
                        $category = strtoupper($sub['subjectCategory'] ?? '');
                        $formattedSub = [
                            'label'    => $sub['subjectName'] ?? '',
                            'total'    => $sub['fullMarks'] ?? 100,
                            'pass'     => $sub['passMarks'] ?? 40,
                            'obtained' => ($sub['marksObtained'] ?? '') . (!empty($sub['isDistinction']) ? ' *' : ''),
                        ];
                        if ($category === 'SESSIONAL' || str_contains(strtoupper($sub['subjectName'] ?? ''), 'PRACTICAL')) {
                            $sessional_subjects[] = $formattedSub;
                        } else {
                            $theory_subjects[] = $formattedSub;
                        }
                    }
                }

                // Fallbacks if subjects array is empty
                if (empty($theory_subjects)) {
                    $theory_subjects = $studentData['theory_subjects'] ?? [
                        ['label' => 'PHARMACEUTICS', 'total' => '100', 'pass' => '40', 'obtained' => '49'],
                        ['label' => 'PHARMACEUTICAL CHEMISTRY', 'total' => '100', 'pass' => '40', 'obtained' => '68'],
                        ['label' => 'PHARMACOGNOSY', 'total' => '100', 'pass' => '40', 'obtained' => '54'],
                        ['label' => 'HUMAN ANATOMY & PHYSIOLOGY', 'total' => '100', 'pass' => '40', 'obtained' => '51'],
                        ['label' => 'SOCIAL PHARMACY', 'total' => '100', 'pass' => '40', 'obtained' => '64'],
                    ];
                }

                if (empty($sessional_subjects)) {
                    $sessional_subjects = $studentData['sessional_subjects'] ?? [
                        ['label' => 'PHARMACEUTICS (PRACTICAL)', 'total' => '100', 'pass' => '40', 'obtained' => '96 *'],
                        ['label' => 'PHARMACEUTICAL CHEMISTRY (PRACTICAL)', 'total' => '100', 'pass' => '40', 'obtained' => '98 *'],
                        ['label' => 'PHARMACOGNOSY (PRACTICAL)', 'total' => '100', 'pass' => '40', 'obtained' => '94 *'],
                        ['label' => 'HUMAN ANATOMY & PHYSIOLOGY (PRACTICAL)', 'total' => '100', 'pass' => '40', 'obtained' => '98 *'],
                        ['label' => 'SOCIAL PHARMACY (PRACTICAL)', 'total' => '100', 'pass' => '40', 'obtained' => '91 *'],
                    ];
                }

                // Footer
                $total_marks = $studentData['totalFullMarks'] ?? $studentData['total_marks'] ?? '1000';
                $obtained_marks = $studentData['totalMarksObtained'] ?? $studentData['obtained_marks'] ?? '763';
                
                $back_papers = $studentData['backPapersSubjectName'] ?? null;
                if (empty($back_papers) && !empty($studentData['backPapers']) && is_array($studentData['backPapers'])) {
                    $back_papers = implode(', ', $studentData['backPapers']);
                }
                $back_papers = $back_papers ?? $studentData['back_papers'] ?? 'NIL';

                $percentage = $studentData['percentage'] ?? '76.3';
                $result_status = $studentData['result'] ?? $studentData['result_status'] ?? 'PASS';
                $cautionary_text = $studentData['cautionaryText'] ?? $studentData['cautionary_text'] ?? '[*] => PASSED WITH DISTINCTION';
                $signature_img = $studentData['signatureUrl'] ?? $studentData['signature_img'] ?? null;
            @endphp

            <!-- Header / Candidate Info -->
            <div class="field bold" style="{{ $st['serial_no'] ?? '' }}">{{ $serial_no }}</div>

            <div class="field bold" style="{{ $st['part_no'] ?? '' }}">
                <span>{{ $part_no }}</span>
                @if(!empty($exam_date))
                    <span style="margin-left: 15px;">{{ $exam_date }}</span>
                @endif
            </div>

            <div class="field bold" style="{{ $st['reg_no'] ?? '' }}">{{ $reg_no }}</div>
            <div class="field bold" style="{{ $st['roll'] ?? '' }}">{{ $roll }}</div>
            <div class="field bold" style="{{ $st['number'] ?? '' }}">{{ $number }}</div>

            <div class="field" style="{{ $st['candidate_image'] ?? '' }}">
                @if(!empty($student_image) && file_exists(public_path('storage/' . $student_image)))
                    <img src="{{ public_path('storage/' . $student_image) }}" style="width: 2.8cm; height: 3.3cm; object-fit: cover;">
                @elseif(!empty($student_image) && file_exists(public_path($student_image)))
                    <img src="{{ public_path($student_image) }}" style="width: 2.8cm; height: 3.3cm; object-fit: cover;">
                @else
                    <div style="width: 2.8cm; height: 3.3cm; border: 1px dashed #999; text-align: center; line-height: 3.3cm; font-size: 11px; color: #555;">(image)</div>
                @endif
            </div>

            <div class="field bold uppercase" style="{{ $st['student_name'] ?? '' }}">{{ $student_name }}</div>
            <div class="field bold uppercase" style="{{ $st['parent_name'] ?? '' }}">{{ $parent_name }}</div>
            <div class="field bold uppercase" style="{{ $st['branch'] ?? '' }}">{{ $branch }}</div>
            <div class="field bold uppercase" style="{{ $st['institution_name'] ?? '' }}">{{ $institution_name }}</div>

            <!-- Theoretical Section -->
            <div class="field bold" style="{{ $st['theory_header'] ?? '' }}">Theoretical</div>

            @foreach($theory_subjects as $index => $subject)
                @php
                    $y_pos = $coords['theory_row_ys'][$index] ?? (11.5 + ($index * 0.5)) . 'cm';
                    $t_cols = $coords['theory_cols'];
                @endphp
                <div class="field bold uppercase" style="top: {{ $y_pos }}; left: {{ $t_cols['label'] }};">{{ $subject['label'] }}</div>
                <div class="field bold" style="top: {{ $y_pos }}; left: {{ $t_cols['total'] }};">{{ $subject['total'] }}</div>
                <div class="field bold" style="top: {{ $y_pos }}; left: {{ $t_cols['pass'] }};">{{ $subject['pass'] }}</div>
                <div class="field bold" style="top: {{ $y_pos }}; left: {{ $t_cols['obtained'] }};">{{ $subject['obtained'] }}</div>
            @endforeach

            <!-- Sessional Section -->
            <div class="field bold" style="{{ $st['sessional_header'] ?? '' }}">Sessional</div>

            @foreach($sessional_subjects as $index => $subject)
                @php
                    $y_pos = $coords['sessional_row_ys'][$index] ?? (16.5 + ($index * 0.5)) . 'cm';
                    $s_cols = $coords['sessional_cols'];
                @endphp
                <div class="field bold uppercase" style="top: {{ $y_pos }}; left: {{ $s_cols['label'] }};">{{ $subject['label'] }}</div>
                <div class="field bold" style="top: {{ $y_pos }}; left: {{ $s_cols['total'] }};">{{ $subject['total'] }}</div>
                <div class="field bold" style="top: {{ $y_pos }}; left: {{ $s_cols['pass'] }};">{{ $subject['pass'] }}</div>
                <div class="field bold" style="top: {{ $y_pos }}; left: {{ $s_cols['obtained'] }};">{{ $subject['obtained'] }}</div>
            @endforeach

            <!-- Footer Section -->
            <div class="field bold" style="{{ $st['total_marks'] ?? '' }}">{{ $total_marks }}</div>
            <div class="field bold" style="{{ $st['obtained_marks'] ?? '' }}">{{ $obtained_marks }}</div>
            <div class="field bold" style="{{ $st['back_papers'] ?? '' }}">{{ $back_papers }}</div>
            <div class="field bold" style="{{ $st['percentage'] ?? '' }}">{{ $percentage }}</div>
            <div class="field bold" style="{{ $st['result_status'] ?? '' }}">{{ $result_status }}</div>
            <div class="field bold" style="{{ $st['cautionary_text'] ?? '' }}">{{ $cautionary_text }}</div>

            <div class="field" style="{{ $st['signature'] ?? '' }}">
                @if(!empty($signature_img) && file_exists(public_path('storage/' . $signature_img)))
                    <img src="{{ public_path('storage/' . $signature_img) }}" style="width: 3cm; height: 1.2cm; object-fit: contain;">
                @elseif(file_exists(public_path('assets/pharmacy-certi-sign.jpeg')))
                    <img src="{{ public_path('assets/pharmacy-certi-sign.jpeg') }}" style="width: 3cm; height: 1.2cm; object-fit: contain;">
                @else
                    <div style="font-style: italic; font-size: 11px;">(signature)</div>
                @endif
            </div>

            <div class="field bold" style="{{ $st['issue_date'] ?? '' }}">{{ $issue_date }}</div>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
