<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Management Admission</title>
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
            font-size: 14px;
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
            border-style: double;
        }

        .main-section {
            height: 350px;
            position: relative;
            /* margin-top: 20px;  */
        }

        .logo-container img {
            width: 150px;
            height: auto;
            display: block;
            margin-left: 20px;
        }

        /* .test td{
             border: 1px solid black;
        } */
        .center-horizontally {
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }

        .center-both {
            display: flex;
            justify-content: center;
            /* Center horizontally */
            align-items: center;
            /* Center vertically */
            height: 100vh;
            /* Full height of the viewport */
        }

        .rectangle {
            width: 150px;
            height: 150px;

            background-color: white;
            border: 2px solid black;
            margin-left: 17px;
            margin-top: 20px;
        }

        .rectangle1 {
            width: 180px;
            height: 50px;
            position: center;
            background-color: white;
            border: 2px solid black;
            margin-right: 10px;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="header" style="position:relative;">
        <div class="logo-container"style="position:absolute; margin-top:10px; margin-left:5px;">
            <img src="{{ public_path('assets/logo.png') }}" alt="Left Logo">
        </div>
        <div class="header-text" style="text-align: center;">
            <p style="line-height:1;margin:10.13px 130.27px 0px 128.93px;text-align:center;">
                <span style="color:#2d0660;font-family:Cambria;font-size:14px;">
                    <span style="font-stretch:115%;">
                        <strong>WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL EDUCATION AND SKILL
                            DEVELOPMENT</strong>
                    </span>
                </span>
            </p>

            <p style="line-height:11.53px;margin:0px 130.13px 0px 128.93px;text-align:center;text-indent:0px;">
                <span style="font-size:10px;">
                    (A Statutory Body under Government of West Bengal Act XXVI of 2013)
                </span>
            </p>
            <p style="line-height:11.53px;margin:0px 130.13px 0px 128.93px;text-align:center;">
                <span style="font-size:10px;">
                    Department of Technical Education, Training & Skill Development, Govt. of West Bengal
                </span>
            </p>

            <p style="line-height:11.53px;margin:0px 130.13px 0px 128.93px;text-align:center;">
                <span style="font-size:10px;">
                    Karigari Bhavan, 4th Floor, Plot No. B/7, Action Area-III, Newtown, Rajarhat, Kolkata-700160
                </span>
            </p>
            <h4>Application Form For Pharmacy Admission {{ date('Y') }}</h4>
            <span style="font-size:14px;">
                (For {{ $registerstudent->i_name }})
            </span>
        </div>

        {{-- <div class="logo-container-right" style="position:absolute;top:0;margin-top:10px;margin-left:50px;">
            <img src="{{ public_path('assets/logo.png') }}" alt="Right Logo">
        </div> --}}

    </div>

    <div class="main-section" style="border:1px solid black;">
        <div style="margin-top:20px;">
            <div class="right">
                <label style="left:10px;">Download on: {{ date('d/m/Y') }}</label>
                <div class="rectangle">
                    @if (!empty($registerstudent->s_photo))
                        <img src="{{ public_path('storage/' . $registerstudent->s_photo) }}"
                            style="width: 150px; height: 150px; object-fit: cover;">
                    @endif
                </div>
                <div class="rectangle1">
                    @if (!empty($registerstudent->s_sign))
                        <img src="{{ public_path('storage/' . $registerstudent->s_sign) }}"
                            style="width: 180px; height: 50px; object-fit: contain;">
                    @endif
                </div>
            </div>
        </div>
        <div style="width:100%; position:absolute; margin-top: 30px;">
            <table style="width:70%;padding:3px;">
                <tbody>
                    <tr>
                        <td>Application Form No:</td>
                        <td>{{ isset($registerstudent->s_appl_form_num) ? $registerstudent->s_appl_form_num : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td>Name Of The Candidate:</td>
                        <td>{{ isset($registerstudent->s_candidate_name) ? $registerstudent->s_candidate_name : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td>Date Of Birth:</td>
                        <td>{{ isset($registerstudent->s_dob) ? date('d/m/Y', strtotime($registerstudent->s_dob)) : 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td>Father's Name:</td>
                        <td>{{ isset($registerstudent->s_father_name) ? $registerstudent->s_father_name : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Mother's Name:</td>
                        <td>{{ isset($registerstudent->s_mother_name) ? $registerstudent->s_mother_name : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Mobile No:</td>
                        <td>{{ isset($registerstudent->s_phone) ? $registerstudent->s_phone : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Caste:</td>
                        <td>{{ isset($registerstudent->s_caste) ? $registerstudent->s_caste : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td>{{ isset($registerstudent->s_email) ? $registerstudent->s_email : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Gender:</td>
                        <td>{{ isset($registerstudent->s_gender) ? $registerstudent->s_gender : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Inst. Name:</td>
                        <td>{{ isset($registerstudent->i_name) ? $registerstudent->i_name : 'N/A' }}</td>
                    </tr>


                </tbody>
            </table>
        </div>
    </div>
    <div style="width:100%; height:50px;  position:relative;">
        <div style="position:absolute; float:right; padding:5px;margin-top:80px;">
            <label>(full signature of the candidate) </label>
        </div>
        <div style="position:absolute; float:left; padding:5px;margin-top:80px;">
            <label>Date:</label>
        </div>
    </div>


</body>

</html>
