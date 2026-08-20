<!doctype html>
<html lang="en-US">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>Cancellation of Registration</title>
</head>

<body style="font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333333; line-height: 1.6;">
    <div style="max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; border: 1px solid #e0e0e0; padding: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <p style="margin-top: 0; font-size: 15px;">Dear Student,</p>

        <p style="font-size: 15px;">This is to inform you that your registration has been cancelled by the Council on the basis of the appeal submitted by you regarding cancellation of your registration.</p>

        <p style="font-size: 15px;">The cancellation has been processed after due consideration of the appeal and the relevant records available with the Council.</p>

        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 18px; margin: 20px 0;">
            <p style="margin: 0 0 12px 0; font-weight: bold; font-size: 15px; color: #1e293b; border-bottom: 1px solid #cbd5e1; padding-bottom: 6px;">Student Details:</p>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr>
                    <td style="padding: 5px 0; width: 160px; font-weight: bold; color: #475569;">Name:</td>
                    <td style="padding: 5px 0; color: #1e293b;">{{ $studentName ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: bold; color: #475569;">Registration No.:</td>
                    <td style="padding: 5px 0; color: #1e293b;">{{ $registrationNumber ?? 'N/A' }}</td>
                </tr>
                <!-- <tr>
                    <td style="padding: 5px 0; font-weight: bold; color: #475569;">Roll No.:</td>
                    <td style="padding: 5px 0; color: #1e293b;">{{ $rollNumber ?? 'N/A' }}</td>
                </tr> -->
                <tr>
                    <td style="padding: 5px 0; font-weight: bold; color: #475569;">Institute:</td>
                    <td style="padding: 5px 0; color: #1e293b;">{{ $instituteName ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: bold; color: #475569;">Course:</td>
                    <td style="padding: 5px 0; color: #1e293b;">{{ $courseName ?? 'Diploma in Pharmacy' }}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: bold; color: #475569;">Academic Session:</td>
                    <td style="padding: 5px 0; color: #1e293b;">{{ $session ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <p style="font-size: 15px;">You are requested to retain this communication for future reference.</p>

        <p style="font-size: 15px;">For any further clarification regarding the cancellation of registration, you may contact the concerned section of the Council.</p>

        <div style="margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 14px; color: #475569;">
            <p style="margin: 0;">Regards,</p>
            <p style="margin: 2px 0 0 0; font-weight: bold; color: #1e293b;">Examination Section</p>
            <p style="margin: 2px 0 0 0; color: #64748b;">West Bengal State Council of Technical & Vocational Education and Skill Development.</p>
        </div>
    </div>
</body>

</html>
