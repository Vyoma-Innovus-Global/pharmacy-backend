# Production Deployment Files - Ready to Upload

## 📁 Folder Structure Created

```
services/production/
├── controllers/           (15 files)
├── routes/                (3 files)
└── mail_and_views/        (2 files)
```

## 📋 Files Organized

### 1️⃣ controllers/ (15 files)
Upload these to: `/var/www/pharmacy_backend/app/Http/Controllers/`

- AdminDepartmentController.php
- AdminDesignationController.php
- AdminDetailsController.php
- AdminInstituteController.php
- AdminScheduleController.php
- AdminSemesterController.php
- AdminStudentMarksController.php
- AdminSubjectCategoryController.php
- AdminSubjectController.php
- AdminTeacherController.php ⭐
- EvaluatorDashboardController.php
- EvaluatorInstAllocationController.php
- GenerateOtpController.php
- PaymentController.php ⭐
- StudentMarksController.php

### 2️⃣ routes/ (3 files)
Upload these to: `/var/www/pharmacy_backend/routes/`

- api.php ⭐ (Contains all new API endpoints)
- web.php
- update.php

### 3️⃣ mail_and_views/ (2 files)

**OtpMail.php** → Upload to: `/var/www/pharmacy_backend/app/Mail/`
**otp-mail.blade.php** → Upload to: `/var/www/pharmacy_backend/resources/views/`

## 🚀 Quick Upload Commands

### Using SCP:
```bash
cd /Users/indranilsarmacharya/Documents/pharmacy_final/services/production

# Upload controllers
scp controllers/*.php user@production:/var/www/pharmacy_backend/app/Http/Controllers/

# Upload routes
scp routes/*.php user@production:/var/www/pharmacy_backend/routes/

# Upload mail class
scp mail_and_views/OtpMail.php user@production:/var/www/pharmacy_backend/app/Mail/

# Upload view template
scp mail_and_views/otp-mail.blade.php user@production:/var/www/pharmacy_backend/resources/views/
```

### Using SFTP:
```bash
sftp user@production
cd /var/www/pharmacy_backend/app/Http/Controllers
put controllers/*.php

cd /var/www/pharmacy_backend/routes
put routes/*.php

cd /var/www/pharmacy_backend/app/Mail
put mail_and_views/OtpMail.php

cd /var/www/pharmacy_backend/resources/views
put mail_and_views/otp-mail.blade.php
```

### Using rsync:
```bash
cd /Users/indranilsarmacharya/Documents/pharmacy_final/services/production

rsync -avz controllers/*.php user@production:/var/www/pharmacy_backend/app/Http/Controllers/
rsync -avz routes/*.php user@production:/var/www/pharmacy_backend/routes/
rsync -avz mail_and_views/OtpMail.php user@production:/var/www/pharmacy_backend/app/Mail/
rsync -avz mail_and_views/otp-mail.blade.php user@production:/var/www/pharmacy_backend/resources/views/
```

## ✅ Total: 20 Files Ready for Production

⭐ = Critical files (Teacher save, Payment gateway, API routes)
