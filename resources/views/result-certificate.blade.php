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

        // Default coordinate mapping computed once
        $defaultCoords = [
            'serial_no'          => ['y' => '4cm',  'x' => '17.5cm'],
            'duplicate'          => ['y' => '3.5cm', 'x' => '9.5cm'],
            'part_no'            => ['y' => '4.2cm',  'x' => '7.5cm'],
            'exam_date'          => ['y' => '4.2cm',  'x' => '7.5cm'],
            'reg_no'             => ['y' => '5.3cm',  'x' => '5cm'],
            'roll'               => ['y' => '5.3cm',  'x' => '10cm'],
            'number'             => ['y' => '5.3cm',  'x' => '14cm'],
            'candidate_image'    => ['y' => '5cm',  'x' => '16.6cm'],
            'student_name'       => ['y' => '6.1cm',  'x' => '6.5cm', 'max_width' => '9.2cm'],
            'parent_name'        => ['y' => '6.8cm',  'x' => '6.5cm', 'max_width' => '9.2cm'],
            'branch'             => ['y' => '7.5cm',    'x' => '6.5cm', 'max_width' => '9.2cm'],
            'institution_name'   => ['y' => '8.2cm',  'x' => '6.5cm', 'max_width' => '9.2cm'],
            'theory_header'      => ['y' => '10.5cm', 'x' => '2.5cm'],
            'theory_cols'        => ['label' => '3cm', 'total' => '14cm', 'pass' => '16cm', 'obtained' => '18cm'],
            'theory_row_ys'      => ['11.5cm', '12cm', '12.5cm', '13cm', '13.5cm'],
            'sessional_header'   => ['y' => '15.5cm', 'x' => '2.5cm'],
            'sessional_cols'     => ['label' => '3cm', 'total' => '14cm', 'pass' => '16cm', 'obtained' => '18cm'],
            'sessional_row_ys'   => ['16.5cm', '17cm', '17.5cm', '18cm', '18.5cm'],
            'total_marks'        => ['y' => '21cm', 'x' => '13.5cm'],
            'obtained_marks'     => ['y' => '21cm', 'x' => '18cm'],
            'back_papers'        => ['y' => '22.3cm', 'x' => '2.5cm'],
            'percentage'         => ['y' => '22.4cm', 'x' => '13.8cm'],
            'result_status'      => ['y' => '22.4cm', 'x' => '16.9cm'],
            'cautionary_text'    => ['y' => '24cm', 'x' => '1.7cm'],
            'signature'          => ['y' => '25.5cm',   'x' => '14.3cm'],
            'issue_date'         => ['y' => '25.5cm',   'x' => '4.7cm'],
        ];

        // Resolve default signature once
        $defaultSignaturePath = public_path('assets/pharmacy-certi-sign.jpeg');
        $cachedDefaultSignature = (file_exists($defaultSignaturePath) && is_file($defaultSignaturePath)) ? $defaultSignaturePath : null;
    @endphp

    @foreach($studentsList as $studentData)
        <div class="page-container">
            @php
                $coords = !empty($studentData['coords']) ? array_merge($defaultCoords, $studentData['coords']) : $defaultCoords;

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
                $serial_no = $studentData['serialNumber'] ?? $studentData['serial_no'] ?? '';
                $issue_date = $studentData['issueDate'] ?? $studentData['issue_date'] ?? '';

                // Exceptions: part_no and exam_date keep fallbacks
                $part_no =  $studentData['partNumber'] ?? $studentData['part_no'] ?? 'PART-I';
                $exam_date =  $studentData['examDate'] ?? $studentData['exam_date'] ?? 'HELD IN DECEMBER, 2025';

                $reg_no = $studentData['registrationNumber'] ?? $studentData['st_reg_number'] ?? '';
                $roll = $studentData['rollNo'] ?? $studentData['roll'] ?? $studentData['st_roll'] ?? '';
                $number = $studentData['no'] ?? $studentData['number'] ?? $studentData['st_number'] ?? '';

                $student_image = $studentData['photo'] ?? $studentData['photoUrl'] ?? $studentData['st_profile_img'] ?? null;
                $resolved_image_path = null;
                if (!empty($student_image)) {
                    $parsed_path = parse_url($student_image, PHP_URL_PATH);
                    $clean_path = $parsed_path !== false ? ltrim($parsed_path, '/') : ltrim($student_image, '/');
                    $relative_path = preg_replace('#^storage/#', '', $clean_path);

                    $candidate_paths = [
                        public_path($clean_path),
                        public_path('storage/' . $relative_path),
                        public_path('storage/uploads/' . $relative_path),
                        storage_path('app/public/' . $relative_path),
                        storage_path('app/public/uploads/' . $relative_path),
                    ];

                    foreach ($candidate_paths as $c_path) {
                        if (!empty($c_path) && file_exists($c_path) && is_file($c_path)) {
                            $resolved_image_path = $c_path;
                            break;
                        }
                    }
                }

                $student_name = $studentData['studentName'] ?? $studentData['st_full_name'] ?? '';
                $parent_name = $studentData['fatherMotherName'] ?? $studentData['st_gur_name'] ?? '';
                $branch = $studentData['branchOfStudy'] ?? $studentData['st_branch'] ?? '';
                $institution_name = $studentData['instName'] ?? $studentData['st_institute_name'] ?? '';

                // Process Subjects if passed from API
                $theory_subjects = [];
                $sessional_subjects = [];

                $rawSubjects = $studentData['subject'] ?? $studentData['subjects'] ?? [];
                if (!empty($rawSubjects) && is_array($rawSubjects)) {
                    foreach ($rawSubjects as $sub) {
                        $category = strtoupper($sub['subjectCategory'] ?? '');
                        $marks = $sub['marksObtained'] ?? '';
                        $isDistinction = !empty($sub['isDistinction']) || (is_numeric($marks) && (float)$marks >= 75);
                        $obtainedStr = $marks !== '' ? ($marks . ($isDistinction && !str_contains((string)$marks, '*') ? ' *' : '')) : '';

                        $formattedSub = [
                            'label'    => $sub['subjectName'] ?? '',
                            'total'    => $sub['fullMarks'] ?? '',
                            'pass'     => $sub['passMarks'] ?? '',
                            'obtained' => $obtainedStr,
                        ];
                        if ($category === 'SESSIONAL' || str_contains(strtoupper($sub['subjectName'] ?? ''), 'PRACTICAL')) {
                            $sessional_subjects[] = $formattedSub;
                        } else {
                            $theory_subjects[] = $formattedSub;
                        }
                    }
                }

                // Fallbacks if subjects array is empty (no fake hardcoded subjects)
                if (empty($theory_subjects)) {
                    $theory_subjects = $studentData['theory_subjects'] ?? [];
                }

                if (empty($sessional_subjects)) {
                    $sessional_subjects = $studentData['sessional_subjects'] ?? [];
                }

                // Footer
                $total_marks = $studentData['totalFullMarks'] ?? $studentData['total_marks'] ?? '';
                $obtained_marks = $studentData['totalMarksObtained'] ?? $studentData['obtained_marks'] ?? '';

                $back_papers = '';
                if (!empty($studentData['backPapers'])) {
                    $back_papers = is_array($studentData['backPapers'])
                        ? implode(', ', array_filter($studentData['backPapers']))
                        : (string) $studentData['backPapers'];
                } elseif (!empty($studentData['backPapersSubjectName'])) {
                    $back_papers = $studentData['backPapersSubjectName'];
                } elseif (!empty($studentData['back_papers'])) {
                    $back_papers = is_array($studentData['back_papers'])
                        ? implode(', ', array_filter($studentData['back_papers']))
                        : (string) $studentData['back_papers'];
                }

                $percentage = $studentData['percentage'] ?? '';
                $result_status = $studentData['result'] ?? $studentData['result_status'] ?? '';

                // Exceptions: cautionary_text and signature_img keep fallbacks
                $cautionary_text = $studentData['cautionaryText'] ?? $studentData['cautionary_text'] ?? '[*] => PASSED WITH DISTINCTION';
                $signature_img = $studentData['signatureUrl'] ?? $studentData['signature_img'] ?? null;
                $duplicate_text = $studentData['duplicate'] ?? $studentData['duplicate_text'] ?? null;
            @endphp

            <!-- Header / Candidate Info -->
            <div class="field bold" style="{{ $st['serial_no'] ?? '' }}">{{ $serial_no }}</div>

            @if(!empty($duplicate_text) && strtoupper($duplicate_text) === 'DUPLICATE')
                <div class="field bold uppercase" style="{{ $st['duplicate'] ?? 'top: 3.5cm; left: 9.5cm;' }}">{{ $duplicate_text }}</div>
            @endif

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
                @if(!empty($resolved_image_path))
                    <img src="{{ $resolved_image_path }}" style="width: 2.8cm; height: 3.3cm; object-fit: cover;">
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
                @elseif(!empty($cachedDefaultSignature))
                    <img src="{{ $cachedDefaultSignature }}" style="width: 3cm; height: 1.2cm; object-fit: contain;">
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
