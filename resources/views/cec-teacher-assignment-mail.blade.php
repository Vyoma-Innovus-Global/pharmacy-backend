<!doctype html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Appointment as Examiner</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333333; line-height: 1.6;">
    <div style="max-width: 680px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; border: 1px solid #e0e0e0; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">

        <!-- Header -->
        <div style="border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #1e3a8a; font-size: 20px; text-transform: uppercase; letter-spacing: 0.5px;">
                Pharmacy Examination Authority
            </h2>
            <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">
                Examiner Appointment &amp; Answer Script Evaluation Allocation
            </p>
        </div>

        <!-- Greeting -->
        <p style="font-size: 15px; margin-top: 0;">
            Dear <strong>{{ $mailData['teacher_name'] ?? 'Examiner' }}</strong>,
        </p>

        <!-- Notification Message -->
        <p style="font-size: 14px; color: #334155;">
            You have been appointed as an Examiner for the evaluation of answer scripts for the Pharmacy Examination held in
            <strong>{{ $mailData['exam_year'] ?? '2026' }} ({{ $mailData['semester'] ?? 'Part-I' }})</strong>.
            The details of your assigned answer scripts are provided below:
        </p>

        <!-- Teacher Details Summary Box -->
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 14px 16px; margin: 18px 0; font-size: 13px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 4px 0; color: #64748b; width: 140px;"><strong>Examiner Name:</strong></td>
                    <td style="padding: 4px 0; color: #1e293b;">{{ $mailData['teacher_name'] ?? 'N/A' }}</td>
                </tr>
                @if(!empty($mailData['teacher_designation']))
                <tr>
                    <td style="padding: 4px 0; color: #64748b;"><strong>Designation:</strong></td>
                    <td style="padding: 4px 0; color: #1e293b;">{{ $mailData['teacher_designation'] }}</td>
                </tr>
                @endif
                @if(!empty($mailData['teacher_inst_name']) || !empty($mailData['teacher_inst_code']))
                <tr>
                    <td style="padding: 4px 0; color: #64748b;"><strong>Parent Institute:</strong></td>
                    <td style="padding: 4px 0; color: #1e293b;">
                        {{ $mailData['teacher_inst_name'] ?? '' }}
                        @if(!empty($mailData['teacher_inst_code']))
                            ({{ $mailData['teacher_inst_code'] }})
                        @endif
                    </td>
                </tr>
                @endif
                @if(!empty($mailData['teacher_phone']))
                <tr>
                    <td style="padding: 4px 0; color: #64748b;"><strong>Contact Number:</strong></td>
                    <td style="padding: 4px 0; color: #1e293b;">{{ $mailData['teacher_phone'] }}</td>
                </tr>
                @endif
                @if(!empty($mailData['memo_number']))
                <tr>
                    <td style="padding: 4px 0; color: #64748b;"><strong>Memo Number:</strong></td>
                    <td style="padding: 4px 0; color: #1e293b; font-weight: bold;">{{ $mailData['memo_number'] }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Assignments Table -->
        <h3 style="font-size: 15px; color: #1e293b; margin: 20px 0 10px 0;">Allocated Answer Scripts</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: left; color: #475569;">
                    <th style="padding: 10px; border: 1px solid #cbd5e1; width: 35px; text-align: center;">#</th>
                    <th style="padding: 10px; border: 1px solid #cbd5e1;">Allocated Institute</th>
                    <th style="padding: 10px; border: 1px solid #cbd5e1;">Subject</th>
                    <th style="padding: 10px; border: 1px solid #cbd5e1; text-align: center;">Total Scripts</th>
                    <th style="padding: 10px; border: 1px solid #cbd5e1;">Memo No.</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mailData['assignments'] as $index => $item)
                <tr style="background-color: {{ $index % 2 == 0 ? '#ffffff' : '#f8fafc' }}; color: #334155;">
                    <td style="padding: 9px 10px; border: 1px solid #e2e8f0; text-align: center;">{{ $index + 1 }}</td>
                    <td style="padding: 9px 10px; border: 1px solid #e2e8f0;">
                        <strong>{{ $item['instCode'] ?? '' }}</strong> - {{ $item['instName'] ?? 'N/A' }}
                    </td>
                    <td style="padding: 9px 10px; border: 1px solid #e2e8f0;">
                        <strong>{{ $item['subjectCode'] ?? '' }}</strong> - {{ $item['subjectName'] ?? 'N/A' }}
                    </td>
                    <td style="padding: 9px 10px; border: 1px solid #e2e8f0; text-align: center; font-weight: bold; color: #0f172a;">
                        {{ $item['totalAnsScript'] ?? '-' }}
                    </td>
                    <td style="padding: 9px 10px; border: 1px solid #e2e8f0; color: #64748b;">
                        {{ !empty($item['memoNumber']) ? $item['memoNumber'] : '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="padding: 12px; border: 1px solid #e2e8f0; text-align: center; color: #94a3b8;">
                        No answer scripts allocated.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Guidelines / Next Steps -->
        <div style="background-color: #eff6ff; border-left: 4px solid #2563eb; padding: 12px 16px; margin: 20px 0; font-size: 13px; color: #1e3a8a;">
            <strong>Important Instructions:</strong>
            <ul style="margin: 6px 0 0 0; padding-left: 20px; line-height: 1.5;">
                <li>Please log in to the examination portal to verify your assignment details.</li>
                <li>Ensure confidential evaluation and submit the evaluated marks within the stipulated timeline.</li>
                <li>Contact the Examination Cell immediately if there is any discrepancy in the allocated scripts.</li>
            </ul>
        </div>

        <p style="font-size: 14px; color: #334155; margin-top: 20px;">
            Thank you for your cooperation and timely support.
        </p>

        <!-- Footer -->
        <div style="margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 13px; color: #475569;">
            <p style="margin: 0;">Regards,</p>
            <p style="margin: 2px 0 0 0; font-weight: bold; color: #1e293b;">Examination Section</p>
            <p style="margin: 2px 0 0 0; color: #64748b; font-size: 12px;">Pharmacy Examination Authority</p>
            <p style="margin: 15px 0 0 0; font-size: 11px; color: #94a3b8; font-style: italic;">
                Note: This is an automated system email. Please do not reply directly to this email address.
            </p>
        </div>
    </div>
</body>

</html>
