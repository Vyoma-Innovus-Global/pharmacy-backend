<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            padding: 15px;
        }

        /* ── Header Box ── */
        .header-wrap {
            border: 2px solid #000;
            padding: 10px;
            /* Reduced padding to match image */
            margin-bottom: 14px;
            /* width: auto allows the border to stay within body padding */
            width: auto;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            /* Removed table-layout: fixed to allow the logo cell to be small */
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo-cell {
            /* Set to a specific width so the text can start right after it */
            width: 100px;
            text-align: left;
        }

        .header-logo {
            width: 85px;
            height: 85px;
            display: block;
        }

        .title-cell {
            text-align: left;
            /* Changed from center to left */
            padding-left: 10px;
            /* Space between logo and text */
        }

        .title-cell h2 {
            font-size: 20px;
            /* Increased to match your second image */
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.3;
            /* Tighter line height for the two-line title */
            letter-spacing: 0.2px;
            color: #000;
            margin: 0;
        }


        /* ── Info Bar ── */
        .info-bar {
            border: 2px solid #000;
            padding: 7px 10px;
            margin-bottom: 12px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            line-height: 1.6;
        }

        /* ── Main Table ── */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .main-table th {
            background-color: #fff;
            border: 1.5px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .main-table td {
            border: 1.5px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-size: 10.5px;
            vertical-align: middle;
        }

        .main-table td.left {
            text-align: left;
            padding-left: 6px;
        }

        /* ── Footer ── */
        .footer {
            font-size: 9.5px;
            text-align: center;
            margin-top: 10px;
            color: #333;
            border-top: 1px solid #ccc;
            padding-top: 6px;
        }
    </style>
</head>

<body>

    {{-- ── HEADER ── --}}
    <div class="header-wrap">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img class="header-logo" src="{{ public_path('assets/council_logo.png') }}" alt="Logo">
                </td>
                <td class="title-cell">
                    <h2>
                        WEST BENGAL STATE COUNCIL OF TECHNICAL &amp; VOCATIONAL
                        EDUCATION &amp; SKILL DEVELOPMENT
                    </h2>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── INFO BAR ── --}}
    <div class="info-bar">
        College: {{ $inst }} &nbsp;|&nbsp; Dept: {{ $dept }}<br>
        Sem: {{ $sem }} &nbsp;|&nbsp; Sub Name: {{ $subj }}
    </div>

    {{-- ── TABLE ── --}}
    <table class="main-table">
        <thead>
            <tr>
                <th>SL<br>NO</th>
                <th>REG NO</th>
                <th>ROLL</th>
                <th>NO</th>
                <th>ATTENDANCE</th>
                <th>INT<br>(30)</th>
                <th>Attendance<br>(10)</th>
                <th>VIVA<br>(20)</th>
                <th>SESS<br>EXT (40)</th>
                <th>TOTAL<br>(100)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="left">{{ $row->reg_no }}</td>
                    <td>{{ $row->roll }}</td>
                    <td>{{ $row->roll_no }}</td>
                    <td>PRESENT</td>
                    <td>{{ $row->marks ?? '-' }}</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align:center;">No data found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── FOOTER ── --}}
    <div class="footer">
        Generated by WBSCTVESD Student Management Portal &nbsp;|&nbsp;
        Date: {{ date('d/m/Y') }} &nbsp;|&nbsp;
        Subject: {{ $subj }}
    </div>

</body>

</html>