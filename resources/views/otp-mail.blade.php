<!doctype html>
<html lang="en-US">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>OTP Verification</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:30px;">
    <div style="max-width:480px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; border:1px solid #e0e0e0;">
        <h2 style="color:#023d54; margin-bottom:6px;">OTP Verification</h2>
        <p style="color:#555;">Dear <strong>{{ $recipientName }}</strong>,</p>
        <p style="color:#555;">Your One Time Password (OTP) for the <strong>WBSCT&amp;VE&amp;SD Pharmacy Portal</strong> is:</p>

        <div style="text-align:center; margin:28px 0;">
            <span style="display:inline-block; font-size:36px; font-weight:bold; letter-spacing:12px; color:#023d54; background:#eef6fb; padding:16px 32px; border-radius:8px; border:2px dashed #023d54;">
                {{ $otp }}
            </span>
        </div>

        <p style="color:#555;">This OTP is valid for <strong>2 minutes</strong>. Do not share it with anyone.</p>
        <p style="color:#888; font-size:12px; margin-top:32px;">
            Regards,<br/>
            Examination Cell<br/>
            West Bengal State Council of Technical &amp; Vocational Education and Skill Development (WBSCT&amp;VE&amp;SD)
        </p>
    </div>
</body>
</html>
