<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Details</title>
    <style>
        body {
            background-image: url("assets/logo_bg.png");
            background-position: center;
            background-repeat: no-repeat;
            background-size: 35%;
            font-family: Arial, sans-serif;
            margin: 0;
        }
        td {
            font-size: 9px;
        }

        .left {
            float: left;
            margin-right: 20px;
            margin-left: 15px;
        }

        .right {
            float: right;
        }

        .header {
            text-align: center;
          
        }

        .main-section {
            
            position: relative;
            text-align: left;
           font-style: italic;
            /* margin-top: 20px;  */
        }

        .logo-container img {
            width: 70px;
            height: auto;
            display: block;
            margin-left: 20px;
        }

       


        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px;
            text-align: center;
        }
        th {
            background: #f2f2f2;
        }
    </style>
</head>

<body>
    
    <div class="header" style="position:relative;border-bottom:2px solid #333">
        <div class="logo-container"style="position:absolute; margin-top:12px; margin-left:0px;">
            <img src="{{ public_path('images/logo.png') }}" alt="Left Logo">
        </div>
        <div style="text-align: left;margin:0px 0px 10px 120px;">
            <span style="font-weight: bold;font-family:Arial, sans-serif;font-size:14px;">
                West Bengal State Council of Technical & Vocational Education and Skill Development                   
            </span>
             
            <span style="font-size: 12px;">
                (A Statutory Body under Government of West Bengal Act XXVI of 2013)
            </span><br>
          
            <span style="font-size: 12px; font-weight: bold;">
                Technical Education Division
            </span><br>
        
            <!-- <span style="font-size: 12px;font-style: italic;">
                (Erstwhile West Bengal State Council of Technical Education)
            </span><br> -->
        
            <span style="font-size: 12px;">
                "Karigari Bhavan", 4th Floor, Plot No. B/7, Action Area-III, Newtown, Rajarhat, Kolkata-700160
            </span>
            
        </div>
    </div>
    <div style="margin-bottom:20px;border-bottom:2px solid #333">
        <h3 style="text-align:center;">REGISTRATION DETAILS [Academic Session:{{$sess_yr}}]</h3>

    </div>
    <div style="margin-bottom:20px;font-weight:bold">
        <span><b>Institute: </b>{{$institute['i_name']}} ({{$institute['i_code']}})</span><br><br>
        <span><b>Branch: </b>PHARMACY</span>
    </div>  

    <div class="main-section" style="font-weight:bold;"> 
            
         <div style="font-size:12px">
            
            <table>
                <thead>
                    <tr>
                        <th>Sl.</th>
                        <th>Registration Number</th>
                        <th>Student's Name</th>
                        <th>Father's Name</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $index => $student)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $student->s_appl_reg_no }}</td>
                        <td style="text-align:left">{{ $student->full_name }}</td>
                        <td  style="text-align:left">{{ $student->s_father_name }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4"><b>Total No. of Students in PHARMACY: {{ $students->count() }}<b></td>
                    </tr>
                </tfoot>
            </table>

        </div>
        <div>
          <h3 style="text-align:center;border-bottom: 2px solid #333;">Branch-wise Break-up of Registered Students [Academic Session: {{$sess_yr}}]</h3>

        </div>
        
        <div style="margin-bottom:20px;font-weight:bold">
            <span><b>Institute: </b>{{$institute['i_name']}}</span><br><br>
        </div>

        <div style="font-weight:bold;font-size:12px">
            
            <table>
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th>No. of Student</th>
                    </tr>
                </thead>
                <tbody>   
                    <tr>
                        <td>PHARMACY</td>
                        <td>{{ $students->count() }}</td>
                    </tr>
                    <tr>
                        <td>Total No. of Students for the Academic Session {{$sess_yr}}</td>
                        <td>{{ $students->count() }}</td>
                    </tr>
                </tbody>
            </table>

        </div>

</body>

</html>
