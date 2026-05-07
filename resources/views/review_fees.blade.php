<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Receipt</title>

    <style>
        body {
            background-image: url("{{ public_path('images/logo_bg.png') }}");
            background-position: center;
            background-repeat: no-repeat;
            background-size: 35%;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #222;
            margin: 20px;
        }

        .header {
            text-align: center;
            border: 2px double #333;
            position: relative;
        }

        .header img {
            position: absolute;
            top: 10px;
            left: 15px;
            width: 60px;
        }

        .header h1 {
            font-size: 16px;
            color: #2d0660;
            margin: 5px 0;
        }

        .header p {
            font-size: 10px;
            margin: 2px 0;
        }

        .main-section {
            border: 1px solid #555;
            border-radius: 5px;
            padding: 15px 25px;
            margin-top: 15px;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.2);
        }

        .title {
            text-align: center;
            font-size: 18px;
            text-transform: uppercase;
            text-decoration: underline;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .logo-container {
            width: 10%;
            text-align: left;
        }

        .logo-container img {
            width: 40px;
            display: block;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 13px;
        }

        td {
            padding: 6px 8px;
            vertical-align: top;
        }

        td:first-child {
            width: 45%;
            font-weight: bold;
            color: #222;
        }

        tr:nth-child(odd) td {
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 25px;
            text-align: right;
            font-size: 12px;
            color: #333;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .subjects-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .subject-chip {
            background-color: #e6e6fa;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="logo-container"style="position:absolute;">
            <img src="{{ public_path('images/logo.png') }}" alt="Left Logo">
        </div>
        <div style="text-align: center;flex-grow: 1;">
            <p style="line-height:1;margin:10.13px 0px 0px 40px;text-align:center;">
                <span style="color:#2d0660;font-family:Cambria;font-size:11px;">
                    <span style="font-stretch:115%;">
                        <strong>WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL EDUCATION AND SKILL
                            DEVELOPMENT</strong>
                    </span>
                </span>
            </p>

            <p style="line-height:11.53px;margin:0px 0px 0px 40px;text-align:center;text-indent:0px;">
                <span style="font-family:'Trebuchet MS', Helvetica, sans-serif;font-size:10px;">
                    {Erstwhile West Bengal State Council of Technical Education}
                </span>
            </p>
            <p style="line-height:11.53px;margin:0px 130.13px 0px 128.93px;text-align:center;text-indent:0px;">
                <span style="font-family:'Trebuchet MS', Helvetica, sans-serif;font-size:10px;">
                    (A Statutory Body under Government of West Bengal Act XXVI of 2013)
                </span>
            </p>

            <p style="line-height:11.53px;margin:0px 0px 0px 40px;text-align:center;text-indent:0px;">
                <span style="font-family:'Trebuchet MS', Helvetica, sans-serif;font-size:10px;">
                    Karigari Bhavan, 4th Floor, Plot No. B/7, Action Area-III, Newtown, Rajarhat, Kolkata 700160
                </span>
            </p>

        </div>
    </div>

    <div class="main-section">
        <div class="title">Review Fees Money Receipt</div>

        <table>
            <tbody>
                <tr>
                    <td>Payment Transaction ID:</td>
                    <td>{{ $data->payment_transactions_id }}</td>
                </tr>
                <tr>
                    <td>Application Form No:</td>
                    <td>{{ $data->s_appl_form_num }}</td>
                </tr>
                <tr>
                    <td>Registration Number:</td>
                    <td>{{ $data->reg_no }}</td>
                </tr>
                <tr>
                    <td>Roll Number:</td>
                    <td>{{ $data->roll_number }}</td>
                </tr>
                <tr>
                    <td>Name of the Candidate:</td>
                    <td>{{ $data->s_candidate_name }}</td>
                </tr>
                <tr>
                    <td>Date of Birth:</td>
                    <td>{{ $data->s_dob }}</td>
                </tr>
                <tr>
                    <td>Mobile No:</td>
                    <td>{{ $data->s_phone }}</td>
                </tr>
                <tr>
                    <td>Institute Code:</td>
                    <td>{{ $data->s_inst_code }}</td>
                </tr>
                <tr>
                    <td>Review Applied Subjects:</td>
                    <td>
                        @if(!empty($data->subjects) && count($data->subjects) > 0)
                            <div class="subjects-list">
                                @foreach($data->subjects as $s)
                                    <span class="subject-chip">{{ $s->subject_name }} ({{ $s->general_code }})</span>
                                @endforeach
                            </div>
                        @else
                            No subjects applied
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Academic Session:</td>
                    <td>{{ $data->academic_session }}</td>
                </tr>
                <tr>
                    <td>Year of Examination:</td>
                    <td>{{ $data->exam_year }}</td>
                </tr>
                <tr>
                    <td>Payment Mode:</td>
                    <td>{{ $data->trans_mode }}</td>
                </tr>
                <tr>
                    <td>Transaction Date:</td>
                    <td>{{ $data->trans_time }}</td>
                </tr>
                
            </tbody>
        </table>

        <div class="footer">
            <p><strong>Date:</strong> {{ now()->format('d-m-Y') }}</p>
            <p><strong>Signature of Authorized Officer</strong></p>
        </div>
    </div>

</body>
</html>
