<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Sticker Card</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 10px;
            background: #fff;
        }

        

        .page-break {
            page-break-after: always;
            clear: both;
        }

        .logo-container img {
            width: 70px;
            height: auto;
            display: block;
            margin-left: 20px;
        }

        .full-header-box {
            width: 100%;
            border: 2px solid black;
            padding: 20px 10px;
            box-sizing: border-box;
            position: relative;
            margin-bottom: 10px;
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
            
        }
		.box {
            width: 310px;
            max-height: 160px;
            padding: 2px;
            border: 1px solid black;
            margin-right: 0px;
            float: left;
            font-family: "Times New Roman", Times, serif;
        }
		.data-div {
            width: 100%;
            font-size: 11px;
			line-height:1;
            
        }
    </style>
</head>

<body>
    <div class="full-header-box">
        <div class="header" style="position:relative;">
            <div class="logo-container" style="position:absolute; top:5px; left:5px;">
                <img src="{{ public_path('images/logo.png') }}">
            </div>

            <div class="header-text" style="text-align: center;">
                <p style="line-height:1;text-align:center;">
                    <span style="color:black;font-family:Arial, sans-serif;font-size:14px;">
                        <span>
                            <strong>WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL EDUCATION AND SKILL
                                DEVELOPMENT</strong>
                        </span>
                    </span>
                </p>
                <div class="right">
                    <div class="image-sign">
                        <div class="rectangle-image">
                        </div>
                    </div>
                </div>
                <p style="line-height: 1.4; margin: 0 130px; text-align: center;">
                    <span style="font-size: 12px;font-weight: bold;">
                        "Karigari Bhavan", 4th Floor, Plot No. B/7, Action Area-III, Newtown, Rajarhat, Kolkata-700160
                    </span>
                </p>
            </div>



        </div>
    </div>
	<div class="main-section">
		<h2 style="text-align: center;"><strong>Centre Name:</strong> 
{{ $data[0]['i_name'] }}, <strong>Examination:</strong>{{ $data[0]['part_sem'] }} December- {{ $data[0]['exam_year'] }}</h2>
	</div>
    
        <div class="data-div" style="text-align: center;"> <br>
            @foreach ($data as $student)
                <div class="box">
					<h5>Room:{{ $student['room_code'] }}</h5>
                     <h5>{{ $student['i_name'] }},</h5>
                     <b>PHARM, {{ $student['part_sem'] }}, December-{{ $student['exam_year'] }}</b>
                      <h4>{{ $student['s_candidate_name'] }} ({{ $student['s_inst_code'] }})</h4>
                       <h3>{{ $student['roll'] }}{{ $student['no'] }}</h3>
                </div>

                @if ($loop->iteration % 3 === 0)
                    <div style="clear: both;"></div>
                @endif

                @if ($loop->iteration % 9 === 0 && !$loop->last)
                    <div class="page-break"></div>
                @endif
            @endforeach
			</div>
        
</body>

</html>
