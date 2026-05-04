<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admit Card</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background: #fff;
        }

        /* Header Box */
        .full-header-box {
            width: 100%;
			padding: 2px 2px;
            border: 2px solid black;
            box-sizing: border-box;
            position: relative;
            margin-bottom: 3px;
            background: #fff;

        }

        .header-text {
            text-align: center;
            flex: 1;
        }

        .logo-container img {
            width: 60px;
            height: auto;
        }

        .photo-box {
            width: 120px;
            height: 100px;
            border: 2px solid black;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background: #fff;
        }

        .photo-box img {
            width: 90%;
            height: 90%;
            object-fit: cover;
            position: relative;
            left: 5px;
            right: 2px;
            top: 5px;
            bottom: 2px;
        }

        /* Main Section Box */
        .main-section {
            width: 100%;
            border: 2px solid black;
            border-radius: 5px;
            padding: 2px 2px;
            box-sizing: border-box;
            margin-bottom: 15px;
            font-size: 10px;
            line-height: 1.3;
        }

        .right {
            float: right;
        }

        /* Rows in Main Section */
        .info-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }

        /* .label {
            font-weight: bold;
            width: 150px;
        }

        .value {
            font-weight: normal;
            margin-right: 25px;
        } */

        .label {
            font-weight: bold;
            width: 160px;
            /* fixed width for labels */
        }

        .value {
            font-weight: normal;
        }

        /* Exam Schedule Table */
        .exam-section {
            width: 100%;
            border: 2px solid black;
            /* main border */
            border-radius: 5px;
            padding: 2px 2px;
            box-sizing: border-box;
            background-color: #fff;
            margin-bottom: 15px;
            font-size: 12px;
        }

        .exam-section p {
            font-style: italic;
            margin-bottom: 4px;
        }

        .exam-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .exam-table th,
        .exam-table td {
            border: 1px solid black;
            padding: 2px;
            text-align: center;
        }

        /* Footer */
        .footer {
        position: relative;
        font-size: 14px;
        margin-top: 20px;
    }

    .footer-image {
        position: fixed;
        bottom: 140px;
        right: 55px;
        width: 98px;
        height: 33px;
    }

    .footer-text {
        position: fixed;
        bottom: 90px; /* adjust as needed */
        left: 55px;
        font-size: 14px;
    }

    .footer-role {
        position: fixed;
        bottom: 90px; /* adjust as needed */
        right: 55px;
        text-align: right;
        font-size: 16px;
    }

        .main-section-table {
            width: 80%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .main-section-table td {
            vertical-align: top;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <!-- Header -->
    <div class="full-header-box">
        <div class="logo-container" style="position:absolute; top:10px; left:15px;">
            <img src="{{ public_path('images/logo.png') }}">
        </div>
        <div style="text-align:center;">
            <p style="font-size:13px; font-weight:bold; margin:0;">WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL
                EDUCATION AND SKILL DEVELOPMENT</p>
            <p style="font-size:11px; font-weight:bold; margin:3px 0;">"Karigari Bhavan", 4th Floor, B/7, Action
                Area-III, Newtown, Rajarhat, Kolkata-700160</p>
            <h2 style="letter-spacing:8px; margin-top:5px;">ADMIT</h2>
        </div>
    </div>

    <!-- Main Section -->
    <div class="main-section" style="position: relative">
        <table class="main-section-table">
            <tr>
                <td class="label">Name :</td>
                <td class="value">{{ $data['st_full_name'] }}</td>
                <td class="label">Father/Guardian Name :</td>
                <td class="value">{{ $data['st_gur_name'] }}</td>
            </tr>
            <tr>
                <td class="label">Name of Institute :</td>
                <td class="value">{{ $data['st_institute_name'] }} ({{ $data['st_institute'] }})</td>
				<td class="label">Branch/Trade :</td>
                <td class="value">{{ $data['st_course'] }}</td>
            </tr>
            <tr>
                <td class="label">Registration Number :</td>
                <td class="value">{{ $data['st_reg_number'] }}</td>
				<td class="label">Part/Semester :</td>
                <td class="value">{{ $data['st_part_sem'] }}</td>
            </tr>
            <tr>
                <td class="label">Roll :</td>
                <td class="value">{{ $data['st_roll'] }}</td>
                <td class="label">Number :</td>
                <td class="value">{{ $data['st_admit_num'] }}</td>
            </tr>
            <tr>
                <td class="label">Month of Examination :</td>
                <td class="value">{{ $data['exam_month'] }}</td>
                <td class="label">Year of Examination :</td>
                <td class="value">{{ $data['st_exm_yr'] }}</td>
            </tr>
			<tr>
                <td class="label">Appearance Type :</td>
                <td class="value"><strong>{{ $data['st_apr_type'] }}</strong></td>
            </tr>
        </table>
        <div style="position:absolute; top:10px; right:10px;">
            <div class="photo-box">
                @if (!empty($data['st_profile_img']))
                    <img src="{{ public_path('storage/' . $data['st_profile_img']) }}">
                @endif
            </div>
        </div>
    </div>
    <div class="main-section">
		<h3 style="text-align:center;"><strong>Center of Examination :</strong>{{ $data['st_exm_center'] }}, <strong>Address :</strong> {{ $data['st_exm_cnt_address'] }}</h3>
    </div>
    <!-- Exam Schedule -->
    <div class="exam-section">
        <p style="font-size: larger; font-weight: bold; margin: auto;">EXAMINATION SCHEDULE FOR THEORETICAL EXAMINATIONS</p>
        <table class="exam-table">
            <thead>
                <tr>
                    <th>Sl.</th>
                    <th>Date Of Examination</th>
                    <th>Subject(s) Apearing</th>
                    <th>Paper Code</th>
                    <th>Time / Half</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['st_exam_schedule'] as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['exam_date'] }}</td>
                        <td>{{ $item['exam_subj'] }}</td>
                        <td>{{ $item['exam_subj_code'] }}</td>
                        <td>{{ $item['exam_time'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
<p>NOTE: All students should report at the Away Examination Centre with the downloaded Admit Card along with a Photo Identity Card issued by the government. They should enter the premises of the away examination centre at least 1 (one) hour before the start of examination on each day, and they must enter the examination hall 30 (thirty) minutes before commencement of the examination. No student will be allowed to sit for the examination if this is not followed.</p>
    

    <!-- Footer -->
    <div class="footer">
		<img src="{{ public_path('assets/pharmacy-certi-sign.jpeg') }}" class="footer-image">

		<span class="footer-text">
			Date of download: {{ now()->format('d-m-Y') }}
		</span>

		<div class="footer-role">
			Senior Administrative Officer
			<div>(Examination)</div>
        </div>
	</div>



</body>

</html>
