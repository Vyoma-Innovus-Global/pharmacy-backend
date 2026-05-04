<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Top-sheet Download</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background: #fff;
        }

        .box {
            width: 310px;
            max-height: 180px;
            padding: 5px;
            border: 1px solid black;
            margin-right: 0px;
            float: left;
            font-family: "Times New Roman", Times, serif;
        }

        .page-break {
            page-break-after: always;
            clear: both;
        }

        .logo-container img {
            width: 60px;
            height: auto;
        }

        .full-header-box {
            width: 100%;
			padding: 5px 10px;
            border: 2px solid black;
            box-sizing: border-box;
            position: relative;
            margin-bottom: 3px;
            background: #fff;

        }

        .main-section {
            width: 100%;
            border: 2px solid black;
            border-radius: 5px;
            padding: 5px 10px;
            box-sizing: border-box;
            margin-bottom: 15px;
            font-size: 12px;
            line-height: 1.4;
        }
		
		.signatures {
			display: block;  
		}

		.signature, .signature2 {
			float: left;  
			width: 50%;   
			text-align: center;
			margin-right: 10%;  
		}

		.signature2 {
			margin-right: 0;  
		}


    </style>
</head>
<body>

    <div class="full-header-box">
        <div class="logo-container" style="position:absolute; top:10px; left:15px;">
            <img src="{{ public_path('images/logo.png') }}">
        </div>
        <div style="text-align:center;">
            <p style="font-size:11px; font-weight:bold; margin-left:30;">WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL
                EDUCATION AND SKILL DEVELOPMENT</p>
            <p style="font-size:11px; font-weight:bold; margin:3px 0;">"Karigari Bhavan", 4th Floor, B/7, Action
                Area-III, Newtown, Rajarhat, Kolkata-700160</p>
            <h2 style="letter-spacing:3px; margin-top:5px;">TOP SHEET</h2>
        </div>
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
                <th>Institute:</th>
                <td>[ {{ $data[0]['inst_code'] }} ]  <b>({{ $roll}}) </b></td>
                <th>Subject:</th>
                <td>{{ $data[0]['subject'] }}</td>
            </tr>
            <tr>
                <th>Type :</th>
                <td>{{ $data[0]['exam_type'] }}</td>
                <th>Batch No: (Packet: )</th>
                <td></td>
            </tr>
        </table>

        <table border="1" style="font-size:12px;">
            <tr>
                <th>Roll Numbers of candidates Present</th>
                <th>Roll Numbers of candidates Absent</th>
                <th>Roll Numbers of Expelled/R.A. candidates whose answer scripts are sent separately</th>
            </tr>
            <tr>
                <td>
                        {{ collect($data)
                            ->where('is_present_external', 1)
                            ->pluck('number')
                            ->implode(', ') }}
                </td>
                <td>
                            {{ collect($data)
                            ->where('is_present_external', 0)
                            ->where('is_ra', 0)
                            ->pluck('number')
                            ->implode(', ') 
                            }}

                </td>
                <td>
                    {{ collect($data)
                        ->where('is_ra', 1)
                        ->pluck('number')
                        ->implode(', ') 
                    }}
                </td>
            </tr>
            <tr>
                <td>Total: {{ collect($data)->where('is_present_external', 1)->count() }}</td>
                <td>Total: {{ collect($data)->where('is_present_external', 0)->count() }}</td>
                <td>Total: {{ collect($data)->sum('is_expel') + collect($data)->sum('is_ra') }}</td>
            </tr>
        </table>

        <p>Total Number of answer scripts enclosed:<br>
        Total Number of loose sheets enclosed:</p>

        <div class="signatures">
			<div class="signature">
				__________________________<br>
				Signature of the Centre-in-charge
			</div>
			<div class="signature2">
				__________________________<br>
				Signature of Observer
			</div>
		</div>
		<br/>
        <p> {{ $data[0]['exam_date'] }}</p>

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