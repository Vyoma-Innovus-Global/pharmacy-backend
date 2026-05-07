<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $subj }} Marks - {{$inst}}</title>

    <style>
        html,
        body {
            margin: 0;
            padding: 5px 0px 5px 0px;
            width: 100%;
            height: auto;
        }

        body {
            background-image: url("assets/logo_bg.png");
            background-position: center;
            background-repeat: no-repeat;
            background-size: 35%;
            font-family: Arial, Helvetica, sans-serif;
        }


        @page {
            size: A4 potrait;
            margin: 5mm;
        }

        /* HEADER */
        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 14px;
            font-weight: normal;
            margin-bottom: 10px;
        }

        /* INFO ROW */
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
            font-size: 10px;
        }

        .info-table td {
            padding: 2px;
        }

        /* MAIN OUTER TABLE */
        .outer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }

        .outer-table td {
            vertical-align: top;
            width: 50%;
            padding: 2px;
        }
        .outer-table td:first-child {
            border-right: 1px dotted  #000;
        }

        /* INNER TABLE */
        .inner-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .inner-table th,
        .inner-table td {
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
        }

        .inner-table th {
            text-align: center;
        }

        .marks {
            text-align: center;
            font-weight: bold;
        }

        /* FOOTER */
        .footer {
            width: 100%;
            margin-top: 20px;
            font-size: 12px;
            overflow: hidden;
        }

        .signature {
            width: 45%;
            float: left;
        }

        .signature.right {
            float: right;
        }


        .signature-box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 5px;
        }

        .footer-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
            font-size: 12px;
        }

        .footer-table td {
            width: 50%;
            vertical-align: top;
            padding: 5px;
        }
        .logo-container img {
            width: 30px;
            height: auto;
            display: block;
            margin-left: 10px;
        }
    </style>
</head>

<body>

    <!-- ================= SIDE BY SIDE TABLES ================= -->
    <table class="outer-table">
        <tr>

            <!-- TABLE 1 -->
            <td>
                <table class="inner-table">
                    <thead>
                        <!-- HEADER -->
                        <tr>
                            <th colspan="4" style="border:none; padding-bottom:6px;">
                                <div class="logo-container"style="position:absolute; margin-top:12px; margin-left:0px;">
                                    <img src="{{ public_path('images/logo.png') }}" alt="Left Logo">
                                </div>
                                <div style="text-align:center;">
                                    <div style="font-size:12px; font-weight:bold;">
                                        WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL EDUCATION AND SKILL
                                        DEVELOPMENT
                                    </div>
                                    <div style="font-size:10px;">
                                        {{ $inst }}
                                    </div>
                                </div>
                            </th>
                        </tr>

                        <!-- INFO ROW -->
                        <tr>
                            <th colspan="4" style="border:none; padding-bottom:6px;">
                                <table width="100%" style="font-size:10px; border-collapse:collapse;">
                                    <tr>
                                        <td><strong>DEPT: </strong> {{ $dept }}</td>
                                        <td><strong>SEM: </strong> {{ $sem }}</td>
                                        <td><strong>SUB: </strong> {{ $subj }}</td>
                                        <td><strong>Full Marks:</strong> {{ $fm }}</td>
                                    </tr>
                                </table>
                            </th>
                        </tr>

                        <!-- TABLE TITLE -->
                        <tr>
                            <th colspan="4">Student Marks - Table 1</th>
                        </tr>

                        <!-- COLUMN HEADERS -->
                        <tr>
                            <th style="width:15%;">SL</th>
                            <th style="width:35%;">ROLL</th>
                            <th style="width:35%;">NO</th>
                            <th style="width:15%;">Marks</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($data as $i => $student)
                            <tr>
                                <td style="width:15%;">{{ $i + 1 }}</td>
                                <td style="width:35%;">{{ $student->roll }}</td>
                                <td style="width:35%;">{{ $student->roll_no }}</td>
                                <td class="marks" style="width:15%;">{{ $student->marks }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>

            <!-- TABLE 2 -->
            <td>
                <table class="inner-table">
                    <thead>
                        <!-- HEADER -->
                        <tr>
                            <th colspan="4" style="border:none; padding-bottom:6px;">
                                <div class="logo-container"style="position:absolute; margin-top:12px; margin-left:0px;">
                                    <img src="{{ public_path('images/logo.png') }}" alt="Left Logo">
                                </div>
                                <div style="text-align:center;">
                                    <div style="font-size:12px; font-weight:bold;">
                                        WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL EDUCATION AND SKILL
                                        DEVELOPMENT
                                    </div>
                                    <div style="font-size:10px;">
                                        {{ $inst }}
                                    </div>
                                </div>
                            </th>
                        </tr>

                        <!-- INFO ROW -->
                        <tr>
                            <th colspan="4" style="border:none; padding-bottom:6px;">
                                <table width="100%" style="font-size:10px; border-collapse:collapse;">
                                    <tr>
                                        <td><strong>DEPT: </strong> {{ $dept }}</td>
                                        <td><strong>SEM: </strong> {{ $sem }}</td>
                                        <td><strong>SUB: </strong> {{ $subj }}</td>
                                        <td><strong>Full Marks:</strong> {{ $fm }}</td>
                                    </tr>
                                </table>
                            </th>
                        </tr>

                        <!-- TABLE TITLE -->
                        <tr>
                            <th colspan="4">Student Marks - Table 2</th>
                        </tr>

                        <!-- COLUMN HEADERS -->
                        <tr>
                            <th style="width:15%;">SL</th>
                            <th style="width:35%;">ROLL</th>
                            <th style="width:35%;">NO</th>
                            <th style="width:15%;">Marks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $i => $student)
                            <tr>
                                <td style="width:15%;">{{ $i + 1 }}</td>
                                <td style="width:35%;">{{ $student->roll }}</td>
                                <td style="width:35%;">{{ $student->roll_no }}</td>
                                <td class="marks" style="width:15%;">{{ $student->marks }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>

        </tr>
    </table>

     <!-- FOOTER -->
      <!-- <div>
        Generated by WBSCVTESD Student Management Portal | Date: {{ date('d/m/Y') }}| Subject: {{ $subj }}
      </div> -->


    <!-- FOOTER -->
    <table class="footer-table">
        <tr>
            <td>
                <strong>Examiner Name : </strong> {{ $evaluator }}
                <div class="signature-box">
                    Signature & Date<br>
                    Scrutinizer Name<br><br>
                    Signature & Date<br>
                </div>
            </td>

            <td>
                <strong>Examiner Name : </strong> {{ $evaluator }}
                <div class="signature-box">
                    Signature & Date<br>
                    Scrutinizer Name<br><br>
                    Signature & Date<br>
                </div>
            </td>
        </tr>
    </table>


</body>

</html>