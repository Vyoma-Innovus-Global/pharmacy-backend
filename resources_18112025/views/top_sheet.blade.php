<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Top-sheet Download</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header p {
            margin: 4px 0;
            font-size: 14px;
        }

        .main-section {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            padding: 5px;
            font-size: 13px;
            text-align: left;
        }

        table[border="1"] th, table[border="1"] td {
            border: 1px solid black;
        }

        .signature {
            text-align: right;
            margin-top: 30px;
        }

        .note {
            font-size: 12px;
            margin-top: 20px;
            line-height: 1.4;
        }

        .enclosures {
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <p><strong>WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL EDUCATION AND SKILL DEVELOPMENT</strong></p>
        <p><strong>(Formerly WEST BENGAL STATE COUNCIL OF TECHNICAL EDUCATION)</strong></p>
        <p><strong>"Karigari Bhavan", 4th Floor, Plot No. B/7, Action Area-III, Newtown, Rajarhat, Kolkata-700160</strong></p>
    </div>

    <div class="main-section">
        <table>
            <tr>
                <th width="100px">Centre:</th>
                <td>{{ $data[0]['centre_name'] }}</td>
                <th width="100px">Date:</th>
                <td>{{ $data[0]['exam_date'] }}</td>
            </tr>
            <tr>
                <th>Subject:</th>
                <td>{{ $data[0]['subject'] }}</td>
            </tr>
            <tr>
                <th>Half / Special:</th>
                <td></td>
                <th>Batch No: (Packet: )</th>
                <td></td>
            </tr>
        </table>

        <table border="1">
            <tr>
                <th>Roll Numbers of candidates Present in the subject</th>
                <th>Roll Numbers of Expelled/R.A. candidates whose answer scripts are sent separately</th>
            </tr>
            <tr>
                <td>
                    @foreach($data as $student)
                        {{ $student['roll_num'] }},
                    @endforeach
                </td>
                <td></td>
            </tr>
            <tr>
                <td>Total: {{ count($data) }}</td>
                <td>Total: {{ collect($data)->sum('is_expel') + collect($data)->sum('is_ra') }}</td>
            </tr>
        </table>

        <p>Total Number of answer scripts enclosed:<br>
        Total Number of loose sheets enclosed:</p>

        <div class="signature">
            __________________________<br>
            Signature of the Officer-in-charge
        </div>

        <p>The {{ $data[0]['exam_date'] }}</p>

        <div class="enclosures">
            <p>Enclosures:</p>
            <p>1. Scripts</p>
            <p>2. Question Paper</p>
        </div>

        <div class="note">
            <strong>N.B.</strong> - Immediately on receipt of each packet, the Examiner is requested to count its contents and verify the figures given in the above statement. Any discrepancy should be reported to the Controller of Examinations immediately. The enclosed receipt forms should be filled in and returned within three (3) days. Otherwise, it will be assumed that the statement on the Top Sheet is correct.
        </div>
    </div>

</body>
</html>