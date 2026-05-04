<html>

<head>
    <meta http-equiv="Content-Type" content="text/html;">
    <style>
        @page {
            margin: 120px 30px 100px 30px;
        }

        #header {
            position: fixed;
            left: 0px;
            top: -120px;
            right: 0px;
            height: 120px;
            margin-top: 20px;
            /* background-color: orange; */
        }

        #footer {
            position: fixed;
            left: 0px;
            bottom: -100px;
            right: 0px;
            height: 100px;
            /* background-color: rgb(221, 5, 237); */
        }

        #content {
            /* background-color: rgb(7, 120, 7); */
        }

        #footer .page:after {
            content: counter(page, upper-roman);
        }

        .float-left {
            float: left;
        }

        .float-right {
            float: right;
        }

        .heading-text {
            text-align: center;
            padding: 0;
            margin: 0;
        }

        .heading-text .h5 {
            font-size: 14px;
            font-weight: bold;
            padding: 0;
            margin: 0;
        }

        .heading-text p {
            margin-top: 3px;
            font-size: 13px;
        }

        .left-align {
            position: absolute;
            left: 0;
            border-top: 1px solid black;
        }

        .right-align {
            position: absolute;
            right: 0;
            border-top: 1px solid black;
        }

        .center-code {
            position: absolute;
            left: 0;
        }

        .center-name {
            position: absolute;
            right: 0;
        }

        #content p {
            font-size: 16px;
            padding: 0;
            margin: 0;
        }

        .content p span {
            font-weight: bold;
        }

        .bold-text {
            font-weight: bold;
        }

        .highlight-text {
            color: rgb(99, 5, 5);
            font-weight: 500;
            font-size: 14px;
        }

        img {
            position: absolute;
            right: 15;
            bottom: 70;
        }

        .free-text-footer {
            position: absolute;
            right: 40;
            bottom: 110;
        }
    </style>
</head>

<body>
    <div id="header">
        <div class="heading-text">
            <p class="h5">
                WEST BENGAL STATE COUNCIL OF TECHNICAL & VOCATIONAL EDUCATION AND SKILL
                DEVELOPMENT
            </p>
            <p style="font-size: 16px; padding: 0; margin:0;">
                (A Statutory Body under Government of West Bengal Act XXVI of 2013)
            </p>
            <p style="font-size: 16px; padding: 0; margin:0;">
                Department of Technical Education, Training Skill Development, Government of West Bengal
            </p>
            <p style="font-size: 16px; padding: 0; margin:0; margin-top: 5px;">
                Karigori Bhaban, 4<sup>th</sup> & 5<sup>th</sup> Floor, Plot No. B/7, Action Area-III, Newtown,
                Rajarhat, Kolkata -
                700160
            </p>
        </div>

        <hr>

        <div>
            <div style="float:left; margin-top: 0;">
                Memo No.: WBSCTVESD/SAO(TE)/CONF/(X+2)/Exam/{{ $session }}/{{ $memo_id }}
            </div>

            <div style="float: right; margin-top: 0;">
                Date: {{ date('d-M-y') }}
            </div>
        </div>
    </div>

    <div id="footer">
        <table width="100%" style="border: none;">
            <tr>
                <td style="text-align: left; width: 40%;">
                    <hr style="width: 80%; text-align: left; margin-left: 0; border: 1px solid black;">
                    Counter Signature of the Coordinator of <br> Centralized Evaluation Camp/Nodal Officer
                </td>

                <td style="text-align: right; width: 40%;">
                    <span class="free-text-footer">Yours faithfully</span>
                    <img src="{{ public_path('sao-sign.jpg') }}" alt="signature" width="180px">
                    <hr style="width: 60%; text-align: right; margin-right: 0; border: 1px solid black;">
                    (Sandip Kundu) <br>
                    SAO (TE) <br>
                    WBSCT&VE&SD
                </td>
            </tr>
        </table>
    </div>

    <div id="content">
        <div style="margin-top: 30px;" class="bold-text">
            <p>From: The SAO (TE)</p>
            <p>West Bengal State Council of Technical & Vocational Education and Skill Development</p>
        </div>

        <div style="margin-top: 20px;">
            <p>To,</p>
            <p>SHRI/SMT. {{ $name }} [EXID - {{ $exid }}]</p>
            <p>{{ $designation }}</p>
            <p style="font-size: 14px;">
                @if (is_array($institute))
                    @foreach ($institute as $value)
                        {{ $value['label'] }} <br />
                    @endforeach
                @else
                    {{ $institute }}
                @endif
            </p>
            <p>Mobile No. {{ $phone }}</p>
            <p>Email : {{ $email }}</p>
        </div>

        <p style="margin-top: 20px;" class="bold-text">
            Sub: Appointment of {{ $evaluator_type }} for evaluation of answer scripts of Class XII of (X+2)
            Level Higher Secondary [Vocational] Examination held in {{ $exam_year }}
        </p>

        <div style="margin-top: 20px;">
            <p>Dear Sir / Madam,</p>

            <p>
                The undersigned is glad to inform you that you have been appointed as an <span
                    class="highlight-text">{{ $evaluator_type }}</span>
                for
                evaluating the
                answer script of <span class="highlight-text">{{ $paper_name }}</span> of
                Class XII at the centralized evaluation
                camp organized by <span class="highlight-text">{{ $nodal_name }}</span>
            </p>

            <p style="margin-top: 10px;">
                While examining the answer scripts, you should follow the "General Guidline" along with the instruction
                if any, issued by Centralized Evaluation Camp Coordinator.
            </p>

            <p style="margin-top: 10px;">
                In this connection, the undersigned is also state that the sealed packet marked R.A. (Reported Against)
                will not be opened. Besides, if you become convinced while checking answer scripts that answer to a
                question
                or part thereof has been copied from any sources, a report indicating the Roll Number, Question Number
                and
                suspected sources of coping should be sent to the SAO(TE), WEST BENGAL STATE COUNCIL OF TECHNICAL &
                VOCATIONAL EDUCATION AND SKILL DEVELOPMENT in a sealed cover through the Centralized Evaluation in a
                Camp Coordinator Nodal Officer. However, such answer scripts may be assessed by you as usual, and the
                concerned candidate may be marked as R.A. on the online marks entry portal.
                <a href="{{ $url }}">{{ $url }}</a>
            </p>

            <p style="margin: 5px 0;">
                In this
                connection, for
                online entry / Verification of theory
                marks, the
                <br>
                USER ID: [<span class="highlight-text">{{ $username }}</span>]
                <br> PASSWORD: [<span class="highlight-text">{{ $password }}</span>].
            </p>

            <p>
                All relevant guidelines will be sent to the Centralized Evaluation Camp.
            </p>

            <p style="margin-top:
                10px;">
                As the publication of the result in time depends on your active cooperation, you are therefore requested
                to
                cooperate and follow the time schedule and other guidelines as prescribed by the coordinator of the
                concerned centralized evaluation camp.
            </p>

            <p style="margin-top: 10px;">
                Hope you would render necessary cooperation in this above matter.
            </p>
        </div>
    </div>
</body>

</html>
