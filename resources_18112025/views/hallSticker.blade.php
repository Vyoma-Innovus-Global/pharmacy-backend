<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Sticker Card</title>
    <style>
        .box{
            width:310px;
            max-height:180px;
            padding:5px;
            border:1px solid black;
            margin-right:0px;
            float:left;
            font-family: "Times New Roman", Times, serif;
        }
        .page-break {
                page-break-after: always;
                clear: both;
            }
    </style>
</head>

<body>
    
    <div class="header" style="position:relative;">
       
        <div class="header-text" style="text-align: left;margin-right: 40px;">
            <p style="line-height:1;margin:10.13px 130.27px 0px 128.93px;text-align:center;">
                <span style="color:black;font-family:Arial, sans-serif;font-size:14px;">
                    <span style="font-stretch:115%;">
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
            

          
            <p style="line-height: 1.4; margin: 0 130px 4px 130px; text-align: left;">
                <span style="font-size: 13px; font-weight: bold;">
                    (Formerly WEST BENGAL STATE COUNCIL OF TECHNICAL EDUCATION)
                </span>
            </p>
        
            <p style="line-height: 1.4; margin: 0 130px; text-align: left;">
                <span style="font-size: 12px;font-weight: bold;">
                    "Karigari Bhavan", 4th Floor, Plot No. B/7, Action Area-III, Newtown, Rajarhat, Kolkata-700160
                </span>
            </p>

            
        </div>
        
    </div>  

    <div class="main-section"> 
        <div> <br>
             @foreach($data as $student)
                <div class="box">
                    <b>PHARM {{$student['part_sem']}} {{$student['exam_year']}}</b>
                    <h3>{{$student['roll_no']}}</h3>
                    <h4>{{$student['s_candidate_name']}}</h4>
                    <h5>{{$student['i_name']}}, Room:{{$student['room_code']}}</h5>
                </div>

                @if($loop->iteration % 3 === 0)
                    <div style="clear: both;"></div>
                @endif

                @if($loop->iteration % 9 === 0)
                    <div class="page-break"></div>
                @endif
            @endforeach
        </div>
</body>

</html>
