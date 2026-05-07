<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descriptive Roll</title>
    <style>
        .box{
            width:450px;
            padding:3px;
            border:1px solid black;
            margin-right:5px;
            float:left;
            font-family: "Times New Roman", Times, serif;
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
             <table width="100%" cellspacing="0" cellpadding="5">
                <tr>
                    <th>Subject: </th>
                    <td>{{$data[0]['ea_subject_code']}}</td>
                </tr>
                <tr>
                    <th>Centre :</th>
                    <td>{{$data[0]['center']}}, Room: {{$data[0]['room_code']}}, Institute code: [ {{$data[0]['institute']}} ]</td>
                </tr>
                <!-- <tr>
                    <th>Centre :</th>
                    <td></td>
                </tr> -->
            </table> <br>
             <table border="1" width="100%" cellspacing="0" cellpadding="5">
                <tr>
                    <th style="width:5%;">Photo</th>
                    <th style="width:25%;">Registration No.</th>
                    <th style="width:18%;">Name</th>
                    <th style="width:18%;">Roll No.</th>
                    <th style="width:34%;">Signature</th>
                    <!-- <th>Answer Script(s) No.</th> -->
                </tr>
                @foreach($data as $student)
                <tr>
                    <td>
                    @if(!empty($student['photo']))
                            <img src="{{ public_path('storage/' . $student['photo']) }}"style="width: 40px; height: 30px; object-fit: cover;">
                     @endif
                    </td>
                    <td style="font-size: 12px">{{$student['s_appl_reg_no']}} of {{$student['s_appl_reg_year']}}</td>
                    <td style="font-size: 12px">{{$student['name']}}</td>
                    <td style="font-size: 12px">{{$student['roll_no']}}</td>
                    <!-- <td></td> -->
                    <td></td>
                </tr>               
                @endforeach
             </table>
             <p><b>Present:_______________  &nbsp;&nbsp;&nbsp; Absent:________________</b></p>
             <p style="text-align:right; font-weight:bold;">Signature of the Invigilator(s)</p>
        </div>
</body>

</html>
