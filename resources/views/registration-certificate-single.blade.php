<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Certificate:</title>

    <style>
        html, body {
            margin: 22;
            padding: 0;
            font-family: "Times New Roman", Times, serif;
        }
        .page-container {
            
            margin: 0; 
            font-size:14px;
            
        }
        .certificate{
            min-height:100vh;
        }
        td {
            font-size: 14px;
        }

        .left {
            float: left;
            margin-left: 15px;
        }

        .right {
            float: right;
        }

        .header {
            text-align: left;
        }

        .main-section {
            
           position: relative;
            text-align: left;
        }

        .logo-container img {
            width: 70px;
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

		.underline {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 200px; /* Adjust the underline length */
            height: 18px;
            vertical-align: bottom;
        }
        .underline large{
             display: inline-block;
            border-bottom: 1px solid #000;
            width: 500px; /* Adjust the underline length */
            height: 18px;
            vertical-align: bottom;

        }
        .page-break{
            page-break-after:always;
        }
    </style>
</head>

<body class="page-container">
        @php
        //dd($data);
            $student_name = $data['st_full_name'];
            $student_image = $data['st_profile_img'];
            $parent_name = $data['st_gur_name'];
            $reg_no = $data['st_reg_number'];
            $reg_year = $data['st_session'];
            $course = $data['st_course'];
            $iCode = $data['st_institute'];
            $regIssuedOn = $data['reg_issued_on'];
            $certificateType = $data['certificate_type'];
           
        @endphp
        <div class="certificate">
            <div class="header">
			<div class="header-text" style="text-align: left;">
                    <div class="left" style="margin-top:65px">
                        <p style="font-size: 10px;margin-left:15px">{{$iCode}} - PHARM</p>
                        @if($certificateType=="duplicate")
                            <h2 style="text-decoration: underline;margin-left:300px;">DUPLICATE</h2>
                        @endif
                    </div>
					<div class="right" style="margin-right:35px;margin-top:30px">
					<div class="image-sign">
						@if(!empty($data['st_profile_img']) && file_exists(public_path('storage/' . $data['st_profile_img'])))
							<img src="{{ public_path('storage/' . $data['st_profile_img']) }}" style="width: 97px; height: 131px; object-fit: cover;">
						@else
							<img src="" style="width: 97px; height: 131px; object-fit: cover;">
						@endif

					</div>
				</div>
			</div>
                
			 
                        
                    
                <div style="font-weight:bold;">
                    <div class="main-section" style="margin-top:145px"> 
                                <div style="margin-top: 55px;">
                                    <span class="label"></span>
                                    <span style="text-align:center;margin-left:228px;font-size:16px;text-transform: uppercase;">{{ $student_name }}</span>
                                </div>
                                <div style="margin-top: 20px;">
                                    <span class="label"></span>
                                    <span style="text-align:center;margin-left:183px;text-transform: uppercase;"> {{ $parent_name }} </span>

                                </div>
                                <div style="margin-top: 20px; text-align:justify;">
                        <span class="label" style="display:block; white-space:nowrap;">
                            
                        </span>
                    </div>

                    <div style="margin-top: 35px;">
                         
                        <span style="text-align:center;margin-left:270px;margin-top:10px">DIPLOMA COURSE IN PHARMACY</span>
                    </div>
                    <div style="margin-top: 15px;">
                         
                        <span style=" text-align:center;margin-left:377px;margin-top:-4px">{{ $reg_no }} </span>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        
                        <span style=" text-align:center;margin-left:215px;margin-top:-4px">{{ $reg_year }}</span>
                    </div>
                </div>
                    
            </div>
            <div>
                <span style="margin-top: 15px;float:right;margin-right:110px"><img src="{{ public_path('assets/pharmacy-certi-sign.jpeg')}}" style="width:98px; height: 33px;"></span>
            </div>
            <div style="margin-top: 70px;display:flex;font-weight:bold;">            
                <span style="margin-left:125px">
                    {{ $regIssuedOn 
                        ? \Carbon\Carbon::createFromFormat('n-j-Y', ltrim($regIssuedOn, '0'))->format('jS F, Y') 
                        : '-' 
                    }}
                </span>

                
            </div>
        </div> 
        
         
</body>

</html>