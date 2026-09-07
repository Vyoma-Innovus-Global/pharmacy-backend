# Pharmacy Services API Routes & Stored Procedures Mapping

This document provides a comprehensive analysis of all API routes defined in [`routes/api.php`](file:///d:/git/pharmacy_services/services/routes/api.php), their corresponding controller methods, and whether they execute **Stored Procedures / PostgreSQL Database Functions** (`fn_*`, `sp_*`) or utilize Laravel's **Eloquent ORM / Query Builder (`DB::table`)** architecture.

## 📊 Architecture & Usage Summary

| Metric | Count |
|:---|:---|
| **Total API Route Endpoints** | **354** |
| **Routes Using Stored Procedures / Functions Only** | **192** |
| **Routes Using Hybrid (Stored Procedure + ORM / Query Builder)** | **8** |
| **Routes Using Eloquent ORM / Query Builder (`DB::table`) Only** | **114** |
| **Routes for Non-DB / External Service / Utility (e.g. Mail/SMS/PDF/Zip)** | **40** |
| **Unique Stored Procedures / Database Functions Identified** | **89** |

### 📋 List of Unique Stored Procedures / PostgreSQL Functions (89 total)

| # | Stored Procedure / Function Name |
|---|----------------------------------|
| 1 | `fn_admin_check_schedule` |
| 2 | `fn_admin_deleteteacherinfo` |
| 3 | `fn_admin_examinerwisesummary` |
| 4 | `fn_admin_getallexaminationinstitutes` |
| 5 | `fn_admin_getallinstitutes_v1` |
| 6 | `fn_admin_getallsemesters` |
| 7 | `fn_admin_getallsubjectcategory` |
| 8 | `fn_admin_getappearingcandidateddetailsbyinst` |
| 9 | `fn_admin_getassignedteacherinfo` |
| 10 | `fn_admin_getbankinfo` |
| 11 | `fn_admin_getbankinfobyifsc` |
| 12 | `fn_admin_getdashboard` |
| 13 | `fn_admin_getdepartmentsbyinst` |
| 14 | `fn_admin_getdeptallsubjects_v1` |
| 15 | `fn_admin_getenteredstudentmarksinfo` |
| 16 | `fn_admin_getevaluatorinstallocationsummary_v1` |
| 17 | `fn_admin_getevaluatorsubjectallocationsummary` |
| 18 | `fn_admin_getexaminationcenter` |
| 19 | `fn_admin_getinstitutewisesummary` |
| 20 | `fn_admin_getmarksenteredteachersinfo` |
| 21 | `fn_admin_getreview_evaluatorsubjectallocationsummary` |
| 22 | `fn_admin_getreviewevaluatorinstallocationsummary` |
| 23 | `fn_admin_getreviewstudentmarksinfo` |
| 24 | `fn_admin_getroutinesubjects_details` |
| 25 | `fn_admin_getstudentmarksinfo_v2` |
| 26 | `fn_admin_getstudentroutineinfo` |
| 27 | `fn_admin_getsubjects_details` |
| 28 | `fn_admin_gettopsheetlistbyinst` |
| 29 | `fn_admin_savebankinfo` |
| 30 | `fn_admin_savereviewteacherassignsubject` |
| 31 | `fn_admin_saveroutine` |
| 32 | `fn_admin_savestudentmarks_v3` |
| 33 | `fn_admin_saveteacherassignsubject_v1` |
| 34 | `fn_admin_saveteacherinfo` |
| 35 | `fn_check_studenteligible_inexam_studentid` |
| 36 | `fn_downlaodstudentregitration_registrationid` |
| 37 | `fn_enrollmentstudent_generateorderid` |
| 38 | `fn_generate_registration_certificates` |
| 39 | `fn_generateotp` |
| 40 | `fn_generateotp_student` |
| 41 | `fn_get_council_board_list` |
| 42 | `fn_get_institute_list` |
| 43 | `fn_get_payment_response_studentid` |
| 44 | `fn_get_ra_institute_list` |
| 45 | `fn_get_ra_student_list` |
| 46 | `fn_get_reviewstudentdetailsbyteacherid` |
| 47 | `fn_get_routinelist` |
| 48 | `fn_get_studentcountinexaminationcenter` |
| 49 | `fn_get_studentpayment_type_studentid` |
| 50 | `fn_get_teacherinfobyinst` |
| 51 | `fn_get_teacherlistofreviewsubject` |
| 52 | `fn_getadmindetailsbyusername` |
| 53 | `fn_getadmitcard_details` |
| 54 | `fn_getdistrictlistbyadmin` |
| 55 | `fn_getenrollstudentdetails` |
| 56 | `fn_getlatestotpbyusername` |
| 57 | `fn_getpaymentdetailsbytransno` |
| 58 | `fn_getpendingpaymentdetails` |
| 59 | `fn_getradetailslistbystudentregistartionnumber` |
| 60 | `fn_getregistredstudentdetailslistbyinstrituteadmin` |
| 61 | `fn_getregistredstudentdetailslistbystudentid` |
| 62 | `fn_getresulatdownload_inst` |
| 63 | `fn_getresulatstatuscount_inst` |
| 64 | `fn_getresulatstatusdetails_inst` |
| 65 | `fn_getreviewdetailslistbystudentregistartionnumber` |
| 66 | `fn_getstudentdetailsbyusername` |
| 67 | `fn_getstudentdetailslistbyinstrituteadmin` |
| 68 | `fn_getstudentmarksdetails` |
| 69 | `fn_getstudentreviewdetailslistbycouncil` |
| 70 | `fn_save_confirmation_status` |
| 71 | `fn_save_examinationcenter` |
| 72 | `fn_save_instritute` |
| 73 | `fn_save_pharmacy_review_subject` |
| 74 | `fn_save_ra_decission_student` |
| 75 | `fn_save_review_subject_marks` |
| 76 | `fn_savepaymentdtls` |
| 77 | `fn_savepharmacypaymentresponse` |
| 78 | `fn_savesbipaymentdtls` |
| 79 | `fn_student_generateorderid` |
| 80 | `fn_student_review_generateorderid` |
| 81 | `fn_update_ra_studentmarks` |
| 82 | `fn_updateexamattendencestatusbyinst` |
| 83 | `fn_updatesbipaymentresponse` |
| 84 | `fn_updatestudentdetailsbyadmin` |
| 85 | `fn_updatestudentdetailsbyadmin_v1` |
| 86 | `fn_updatestudentenrollmentstatusbyadmin` |
| 87 | `fn_updatestudentphoneoraadhaardetailsbyinstadmin` |
| 88 | `fn_updatestudentregistrationstatusbyadmin` |
| 89 | `fn_updateuserotpbycontactno` |

---

## 🗺️ Route-by-Route Mapping by Module

### 📁 / (Root API Routes)

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `GET` | `/api/user` | `Closure` | Eloquent ORM | None | Sanctum Auth (`$request->user()`) |
| `POST` | `/api/reset-password` | `AuthController@reset_password` | Eloquent ORM | None | **Models:** `SuperUser` |
| `POST` | `/api/authenticate` | `AuthController@authenticate` | Stored Procedure + Query Builder / ORM | `fn_generateotp_student` | **Models:** `SuperUser`, `Otp`, `EvaluatorDetails`, `Token`, `EvaluatorAllocation`, `Registerstudent`<br>**Tables:** `institute_master` |
| `POST` | `/api/validate-security-code` | `AuthController@validateSecurityCode` | Eloquent ORM | None | **Models:** `Otp`, `Registerstudent`, `Token`, `EvaluatorDetails`, `SuperUser`, `EvaluatorAllocation` |
| `GET` | `/api/check-status/{user_id}` | `StudentController@checkRedirect` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/print-reg-certificate` | `StudentController@printRegistrationCertificate` | Stored Procedure + Query Builder / ORM | `fn_generate_registration_certificates` | **Tables:** `registration_certificate_issue` |
| `GET` | `/api/print-reg-certificate-single/{reg_num?}/{sess_yr?}` | `StudentController@printRegistrationCertificateSingle` | Stored Procedure + Query Builder / ORM | `fn_generate_registration_certificates` | **Tables:** `registration_certificate_issue` |
| `GET,POST` | `/api/print-result-certificate-single` | `StudentController@printResultCertificateSingle` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/registration-report-download` | `StudentController@regReportDownload` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final` |
| `GET` | `/api/download-reg-report-zip` | `StudentController@downloadRegReportZip` | Eloquent ORM | None | **Models:** `Zip` |
| `GET` | `/api/download-reg-zip` | `StudentController@downloadRegZip` | Eloquent ORM | None | **Models:** `Zip` |
| `GET,POST` | `/api/result-download-inst` | `ReportController@resultCertificateDownloadInst` | Stored Procedure / PostgreSQL Function | `fn_getresulatdownload_inst` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/student`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/student/student-info-update` | `StudentController@studentInfoUpdate` | Eloquent ORM + Query Builder | None | **Models:** `Token`, `User`, `Registerstudent`<br>**Tables:** `pharmacy_auth_roles_permissions`, `pharmacy_auth_urls`, `pharmacy_student_extraInfo` |
| `GET` | `/api/student/download-form/{from_num}` | `StudentController@downloadForm` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final` |
| `GET` | `/api/student/student-details/{from_num}` | `StudentController@studentdetails` | Eloquent ORM + Query Builder | None | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_fees`, `pharmacy_student_extraInfo`, `pharmacy_schedule_master`, `pharmacy_appl_review_apply` |
| `GET` | `/api/student/session-list` | `StudentController@sessionList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/student/institute-add` | `MasterController@instituteAdd` | Eloquent ORM + Query Builder | None | **Models:** `Token`, `User`<br>**Tables:** `pharmacy_auth_roles_permissions`, `pharmacy_auth_urls`, `institute_master` |
| `GET` | `/api/student/institute-list/{i_code?}` | `StudentController@instituteList` | Eloquent ORM | None | **Models:** `Institute` |
| `POST` | `/api/student/branch-add` | `MasterController@branchAdd` | Eloquent ORM + Query Builder | None | **Models:** `Token`, `User`<br>**Tables:** `pharmacy_auth_roles_permissions`, `pharmacy_auth_urls`, `pharmacy_branch_master` |
| `POST` | `/api/student/eligible-for-registration-list` | `StudentController@studentRegEligibleList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/student/eligible-for-registration` | `StudentController@studentRegEligible` | Eloquent ORM + Query Builder | None | **Models:** `Token`, `User`, `Registerstudent`<br>**Tables:** `pharmacy_auth_roles_permissions`, `pharmacy_auth_urls` |
| `POST` | `/api/student/registration-list` | `StudentController@registrationList` | Query Builder (`DB::table`) | None | **Tables:** `tbl_student_master as sm` |
| `POST` | `/api/student/generate-reg-numbers` | `StudentController@generateRegNo` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/student/cancel-reg-numbers` | `StudentController@registrationCancel` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/student/send-registration-cancellation-mail` | `EmailBroadcastController@sendRegistrationCancellationMail` | Stored Procedure + Query Builder / ORM | `fn_getregistredstudentdetailslistbystudentid` | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_roll_no` |
| `POST` | `/api/student/print-registration-list` | `StudentController@printRegistrationList` | Query Builder (`DB::table`) | None | **Tables:** `tbl_student_master as sm` |
| `POST` | `/api/student/registration-report-list` | `StudentController@regReportList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final` |
| `POST` | `/api/student/get-data-for-syllabus` | `StudentController@getDataforsyllabus` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/student/syllabus-id-list` | `StudentController@syllabus_idList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/student/syllabus-tag` | `StudentController@syllabusTagSubmit` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/student/subject-list` | `StudentController@subjectList` | Eloquent ORM | None | **Models:** `Registerstudent`, `Subject`, `Elective` |
| `POST` | `/api/student/submit-elective-subject` | `StudentController@submitElectivePaper` | Eloquent ORM | None | **Models:** `Registerstudent`, `Elective` |
| `POST` | `/api/student/check-student-eligible-in-exam` | `ExaminationController@checkStudentEligibleInExam` | Stored Procedure / PostgreSQL Function | `fn_check_studenteligible_inexam_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/student/check-exam-eligibility` | `ExaminationController@checkStudentEligibleInExam` | Stored Procedure / PostgreSQL Function | `fn_check_studenteligible_inexam_studentid` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/enrollment`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/enrollment/re-admission-list` | `EnrollmentController@re_admission_list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_result`, `pharmacy_register_student_final` |
| `POST` | `/api/enrollment/re-admission-submit` | `EnrollmentController@re_admission_submit` | Eloquent ORM + Query Builder | None | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_register_student_final`, `pharmacy_re_admission` |
| `GET` | `/api/enrollment/ra-institute-list` | `AdminInstituteController@getRaInstituteList` | Stored Procedure / PostgreSQL Function | `fn_get_ra_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/ra-institute-list` | `AdminInstituteController@getRaInstituteList` | Stored Procedure / PostgreSQL Function | `fn_get_ra_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/ra-student-list` | `EnrollmentController@getRaStudentList` | Stored Procedure / PostgreSQL Function | `fn_get_ra_student_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/get-enroll-student-details` | `EnrollmentController@getEnrollStudentDetails` | Stored Procedure / PostgreSQL Function | `fn_getenrollstudentdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/enroll-student-details` | `EnrollmentController@getEnrollStudentDetails` | Stored Procedure / PostgreSQL Function | `fn_getenrollstudentdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/update-student-enrollment-status` | `EnrollmentController@updateStudentEnrollmentStatusByAdmin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentenrollmentstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/update-student-enrollment-status-by-admin` | `EnrollmentController@updateStudentEnrollmentStatusByAdmin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentenrollmentstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/list` | `EnrollmentController@list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_result`, `pharmacy_register_student_final` |
| `POST` | `/api/enrollment/get-enrollment-fees-data` | `PaymentController@getEnrollmentPaymentdata` | Eloquent ORM + Query Builder | None | **Models:** `Enrollment`<br>**Tables:** `pharmacy_fees`, `pharmacy_schedule_master` |
| `POST` | `/api/enrollment/enroll-payment` | `PaymentController@payment` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`<br>**Tables:** `pharmacy_fees` |
| `POST` | `/api/enrollment/payment-success` | `PaymentController@enrollmentPaymentSuccess` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`, `Payment`, `Registerstudent`, `Enrollment`<br>**Tables:** `pharmacy_appl_review_apply` |
| `POST` | `/api/enrollment/payment-faill` | `PaymentController@enrollmentPaymentFail` | Eloquent ORM | None | **Models:** `PaymentTransaction` |
| `POST` | `/api/enrollment/institute-payment` | `PaymentController@institutePayment` | Eloquent ORM | None | **Models:** `PaymentTransaction` |
| `POST` | `/api/enrollment/institute-payment-success` | `PaymentController@institutePaymentSuccess` | Eloquent ORM | None | **Models:** `PaymentTransaction`, `Payment` |
| `POST` | `/api/enrollment/institute-payment-fail` | `PaymentController@institutePaymentFail` | Eloquent ORM | None | **Models:** `PaymentTransaction` |
| `GET` | `/api/enrollment/institute-payment-receipt/{order_id}` | `PaymentController@getInstitutePaymentReceiptData` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`<br>**Tables:** `institute_master` |
| `POST` | `/api/enrollment/enroll-payment-offline` | `PaymentController@paymentOffline` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`, `Payment`, `Registerstudent`, `Enrollment`<br>**Tables:** `pharmacy_fees` |
| `GET` | `/api/enrollment/enrollment-receipt` | `EnrollmentController@enrollmentReceipt` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final as rsf` |
| `POST` | `/api/enrollment/submit` | `EnrollmentController@enrollmentsubmit` | Eloquent ORM + Query Builder | None | **Models:** `Enrollment`, `Registerstudent`<br>**Tables:** `pharmacy_subjects_master`, `pharmacy_result` |
| `GET` | `/api/enrollment/enrollment-download` | `EnrollmentController@enrollmentDownload` | Eloquent ORM | None | **Models:** `PaymentTransaction` |
| `POST` | `/api/enrollment/rollno-generate-list` | `EnrollmentController@rollno_generate_list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_enrollment` |
| `POST` | `/api/enrollment/rollno-generate-submit` | `EnrollmentController@rollno_generate_submit` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_enrollment`, `pharmacy_roll_no`, `pharmacy_register_student_final`, `pharmacy_re_admission`, `pharmacy_result`, `pharmacy_subjects_master`, `exam_attendance_pone`, `pharmacy_exam_center` |
| `POST` | `/api/enrollment/exam-center-list` | `EnrollmentController@exam_center_list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_center` |
| `POST` | `/api/enrollment/exam-center-submit` | `EnrollmentController@exam_center_submit` | Eloquent ORM + Query Builder | None | **Models:** `Institute`<br>**Tables:** `pharmacy_exam_center` |
| `POST` | `/api/enrollment/generate-order-id` | `PaymentController@generateEnrollmentStudentOrderId` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/generate-student-order-id` | `PaymentController@generateEnrollmentStudentOrderId` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/payment`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/payment/save-pharmacy-payment-response` | `PaymentController@savePharmacyPaymentResponse` | Stored Procedure / PostgreSQL Function | `fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/savePharmacyPaymentResponse` | `PaymentController@savePharmacyPaymentResponse` | Stored Procedure / PostgreSQL Function | `fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/pending-payment-detail` | `PaymentController@getPendingPaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_getpendingpaymentdetails`<br>`fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/pending-payment-details` | `PaymentController@getPendingPaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_getpendingpaymentdetails`<br>`fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/getPendingPaymentDetails` | `PaymentController@getPendingPaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_getpendingpaymentdetails`<br>`fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/student-payment-type` | `PaymentController@getStudentPaymentTypeByStudentId` | Stored Procedure / PostgreSQL Function | `fn_get_studentpayment_type_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/student-payment-type-studentid` | `PaymentController@getStudentPaymentTypeByStudentId` | Stored Procedure / PostgreSQL Function | `fn_get_studentpayment_type_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/student-payment-response` | `PaymentController@getPaymentResponseByStudentId` | Stored Procedure / PostgreSQL Function | `fn_get_payment_response_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generateOrderId` | `PaymentController@generateStudentOrderId` | Stored Procedure / PostgreSQL Function | `fn_student_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generate-order-id` | `PaymentController@generateStudentOrderId` | Stored Procedure / PostgreSQL Function | `fn_student_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generateReviewOrderId` | `PaymentController@generateStudentReviewOrderId` | Stored Procedure / PostgreSQL Function | `fn_student_review_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generate-review-order-id` | `PaymentController@generateStudentReviewOrderId` | Stored Procedure / PostgreSQL Function | `fn_student_review_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generate-enrollment-order-id` | `PaymentController@generateEnrollmentStudentOrderId` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generateEnrollmentOrderId` | `PaymentController@generateEnrollmentStudentOrderId` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/enrollment-student-generate-order-id` | `PaymentController@generateEnrollmentStudentOrderId` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/saveSbiPaymentDetails` | `PaymentController@saveSbiPaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_savesbipaymentdtls` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/save-sbi-payment-details` | `PaymentController@saveSbiPaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_savesbipaymentdtls` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/savePaymentDetails` | `PaymentController@savePaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_savepaymentdtls` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/save-payment-details` | `PaymentController@savePaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_savepaymentdtls` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/updateSbiPaymentResponse` | `PaymentController@updateSbiPaymentResponse` | Stored Procedure / PostgreSQL Function | `fn_updatesbipaymentresponse` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/update-sbi-payment-response` | `PaymentController@updateSbiPaymentResponse` | Stored Procedure / PostgreSQL Function | `fn_updatesbipaymentresponse` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/getPaymentDetailsByTransNo` | `PaymentController@getPaymentDetailsByTransNo` | Stored Procedure / PostgreSQL Function | `fn_getpaymentdetailsbytransno` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/get-payment-details-by-trans-no` | `PaymentController@getPaymentDetailsByTransNo` | Stored Procedure / PostgreSQL Function | `fn_getpaymentdetailsbytransno` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/details-by-transaction/{transactionNo}` | `PaymentController@getPaymentDetailsByTransNo` | Stored Procedure / PostgreSQL Function | `fn_getpaymentdetailsbytransno` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/sbiPayment` | `PaymentController@getPaymentDetailsByTransNo` | Stored Procedure / PostgreSQL Function | `fn_getpaymentdetailsbytransno` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/document`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/document/upload` | `DocumentUploadController@upload` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/document/upload-general` | `DocumentUploadController@uploadGeneral` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/document/upload-multiple` | `DocumentUploadController@uploadMultiple` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `DELETE` | `/api/document/delete` | `DocumentUploadController@delete` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/document/info` | `DocumentUploadController@getInfo` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |


### 📁 Module: `api/master`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `GET` | `/api/master/syllabus-subject-list/{part}` | `AdminController@syllabusSubjectList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/master/state-list/{user_type?}` | `AdminController@allStates` | Eloquent ORM | None | **Models:** `State` |
| `POST` | `/api/master/district-list/{user_type?}` | `AdminController@allDistricts` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/master/subdivision-list/{dist_id?}/{user_type?}` | `AdminController@allSubdivisions` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/master/block-municipality-list/{user_type?}` | `AdminController@allBlockMunicipality` | Eloquent ORM | None | **Models:** `BlockMunicipality` |
| `GET` | `/api/master/students-board-list` | `AdminController@studentsBoardList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/master/council-board-list` | `MasterController@councilBoardList` | Stored Procedure / PostgreSQL Function | `fn_get_council_board_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/master/import-holidayList` | `MasterController@importHolidayList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/master/insert-update-holiday` | `MasterController@updateHolidayList` | Eloquent ORM | None | **Models:** `Holiday` |
| `POST` | `/api/master/get-holiday-list` | `MasterController@getHolidayList` | Eloquent ORM | None | **Models:** `Holiday` |
| `GET` | `/api/master/away-center-list` | `MasterController@allAwayCenter` | Eloquent ORM | None | **Models:** `CenterAllocation` |
| `POST` | `/api/master/get-exam-schedule` | `MasterController@getExamSchedule` | Eloquent ORM | None | **Models:** `Subject`, `ExamSchedule` |
| `POST` | `/api/master/save-exam-schedule` | `MasterController@saveExamSchedule` | Eloquent ORM | None | **Models:** `ExamSchedule` |
| `POST` | `/api/master/subject-list-all` | `MasterController@subjectList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/master/subject-list-theory` | `MasterController@subjectListTheory` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `GET` | `/api/master/schedule-list` | `MasterController@scheduleList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/master/schedule-create` | `MasterController@scheduleCreate` | Eloquent ORM | None | **Models:** `Schedule` |
| `PUT` | `/api/master/schedule-update` | `MasterController@scheduleUpdate` | Eloquent ORM | None | **Models:** `Schedule` |
| `DELETE` | `/api/master/schedule-delete/{id}` | `MasterController@scheduleDelete` | Eloquent ORM | None | **Models:** `Schedule` |
| `POST` | `/api/master/schedule-check` | `MasterController@scheduleCheck` | Eloquent ORM | None | **Models:** `Schedule` |
| `POST` | `/api/master/cdc-submit` | `MasterController@createCdc` | Eloquent ORM | None | **Models:** `CDC` |
| `GET` | `/api/master/cdc-list` | `MasterController@cdcList` | Eloquent ORM | None | **Models:** `CDC` |
| `POST` | `/api/master/cdc-institute-tagging` | `MasterController@cdcInstTagg` | Eloquent ORM | None | **Models:** `CDC`, `Institute`, `CDCInstituteTag` |


### 📁 Module: `api/attendance`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/attendance/room-allotment-list` | `AttendanceController@roomAllotmentList` | Eloquent ORM + Query Builder | None | **Models:** `Attendancepone`<br>**Tables:** `pharmacy_roll_no` |
| `POST` | `/api/attendance/room-allotment-submit` | `AttendanceController@roomAllotmentSubmit` | Eloquent ORM + Query Builder | None | **Models:** `Attendancepone`, `Rollnomodel`<br>**Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/attendance/college-wise-institute` | `AttendanceController@collegeWiseInstitute` | Eloquent ORM | None | **Models:** `Examcenter` |
| `POST` | `/api/attendance/institute-wise-center` | `AttendanceController@instituteWiseCenter` | Eloquent ORM | None | **Models:** `Examcenter` |
| `POST` | `/api/attendance/list-attendance` | `AttendanceController@listAttendance` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/attendance/update-attendance` | `AttendanceController@updateAttendance` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/attendance/final-submit-attendance` | `AttendanceController@finalSubmitAttendance` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/attendance/attendance-unlock` | `AttendanceController@attendanceUnlock` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo`, `MarksEntryPone`, `MarksEntryPtwo` |
| `POST` | `/api/attendance/attendance-unlock-all` | `AttendanceController@attendanceunlockAll` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/attendance/center-list` | `AttendanceController@listCentercode` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_exam_center` |


### 📁 Module: `api/examinations`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `GET` | `/api/examinations/admit-card-download` | `ExaminationController@downloadAdmitCard` | Query Builder (`DB::table`) | None | **Tables:** `exam_attendance_pone as a` |
| `GET` | `/api/examinations/admit-card-download-inbulk` | `ExaminationController@downloadAdmitCardInbulk` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/admit-card-download-list` | `ExaminationController@downloadAdmitCardList` | Query Builder (`DB::table`) | None | **Tables:** `exam_attendance_pone as a` |
| `POST` | `/api/examinations/hall-sticker-institute-list` | `ExaminationController@getHsInstituteList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/admin-hall-sticker-away-center` | `ExaminationController@getAdminHsAwayList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_center` |
| `POST` | `/api/examinations/hall-sticker-list` | `ExaminationController@hallStickerList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/hall-sticker-download` | `ExaminationController@hallStickerDownload` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/descriptive-roll-institute-list` | `ExaminationController@getHsInstituteList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/admin-descriptive-roll-away-center` | `ExaminationController@getAdminHsAwayList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_center` |
| `POST` | `/api/examinations/descriptive-roll` | `ExaminationController@descriptiveRoll` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/descriptive-roll-download` | `ExaminationController@descriptiveRollDownload` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final` |
| `POST` | `/api/examinations/top-sheet-institute-list` | `ExaminationController@getHsInstituteList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/admin-top-sheet-away-center` | `ExaminationController@getAdminHsAwayList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_center` |
| `POST` | `/api/examinations/top-sheet-count` | `ExaminationController@topSheetCount` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/top-sheet-download` | `ExaminationController@topSheetDownload` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/packing-slip` | `ExaminationController@packingSlipDownload` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_schedule` |
| `GET` | `/api/examinations/printing-instruction` | `ExaminationController@printingInstruction` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/decoding-list` | `ExaminationController@decodingList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_schedule` |
| `GET` | `/api/examinations/center-wise-students/{part_sem}/{exam_year}/{center_code}` | `ExaminationController@getCenterWiseStudents` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/check-student-eligible-in-exam` | `ExaminationController@checkStudentEligibleInExam` | Stored Procedure / PostgreSQL Function | `fn_check_studenteligible_inexam_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/check-student-eligible` | `ExaminationController@checkStudentEligibleInExam` | Stored Procedure / PostgreSQL Function | `fn_check_studenteligible_inexam_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/ra-details-by-registration-number` | `ExaminationController@getRaDetailsListByStudentRegistrationNumber` | Stored Procedure / PostgreSQL Function | `fn_getradetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/ra-details-list-by-student-registration-number` | `ExaminationController@getRaDetailsListByStudentRegistrationNumber` | Stored Procedure / PostgreSQL Function | `fn_getradetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/update-ra-studentmarks` | `ExaminationController@updateRaStudentMarks` | Stored Procedure / PostgreSQL Function | `fn_update_ra_studentmarks`<br>`fn_save_ra_decission_student` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/update-ra-student-marks` | `ExaminationController@updateRaStudentMarks` | Stored Procedure / PostgreSQL Function | `fn_update_ra_studentmarks`<br>`fn_save_ra_decission_student` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/all-examination-institutes` | `ExaminationController@getAllExaminationInstitutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/examination-institutes` | `ExaminationController@getAllExaminationInstitutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/examination-institutes` | `ExaminationController@getAllExaminationInstitutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/save-examination-center` | `ExaminationController@saveExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_save_examinationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/save-examinationcenter` | `ExaminationController@saveExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_save_examinationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-examination-center` | `ExaminationController@getExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-examinationcenter` | `ExaminationController@getExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/examination-centers` | `ExaminationController@getExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admin-get-examination-center` | `ExaminationController@getExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/student-count-in-examination-center` | `ExaminationController@getStudentCountInExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_get_studentcountinexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/save-routine` | `ExaminationController@saveRoutine` | Stored Procedure / PostgreSQL Function | `fn_admin_saveroutine` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admin-save-routine` | `ExaminationController@saveRoutine` | Stored Procedure / PostgreSQL Function | `fn_admin_saveroutine` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/routine-list` | `ExaminationController@getRoutineList` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-routine-list` | `ExaminationController@getRoutineList` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-routinelist` | `ExaminationController@getRoutineList` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/routinelist` | `ExaminationController@getRoutineList` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-admit-card-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admit-card-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-admitcard-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admitcard-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-student-routine-info` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/student-routine-info` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admin-get-student-routine-info` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/student-routine` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-student-routine` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-admit-card-with-routine` | `ExaminationController@getAdmitCardWithRoutine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admit-card-with-routine` | `ExaminationController@getAdmitCardWithRoutine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-admitcard-with-routine` | `ExaminationController@getAdmitCardWithRoutine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admin-get-student-admit-card-routine` | `ExaminationController@getAdmitCardWithRoutine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/get-admit-card-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/admit-card-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/get-admitcard-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/admitcard-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/get-admit-card-with-routine` | `ExaminationController@getAdmitCardWithRoutine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-appearing-candidates-by-inst` | `ExaminationController@getAppearingCandidatesByInst` | Stored Procedure / PostgreSQL Function | `fn_admin_getappearingcandidateddetailsbyinst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/update-exam-attendance-status-by-inst` | `ExaminationController@updateExamAttendanceStatusByInst` | Stored Procedure / PostgreSQL Function | `fn_updateexamattendencestatusbyinst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-topsheet-list-by-inst` | `ExaminationController@getTopSheetListByInst` | Stored Procedure / PostgreSQL Function | `fn_admin_gettopsheetlistbyinst` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/answersheet`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `GET` | `/api/answersheet/subject-list` | `AnswersheetController@subjectList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/answersheet/submit-mask-setup` | `AnswersheetController@updateMaskSetup` | Eloquent ORM | None | **Models:** `Answersheetmasking` |
| `POST` | `/api/answersheet/get-mask-list` | `AnswersheetController@getMaskList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/answersheet/institute-list` | `AnswersheetController@getInstituteList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/answersheet/list-for-serial` | `AnswersheetController@listAnswersheetSerialEntry` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/answersheet/final-submit-masking` | `AnswersheetController@finalSubmitMasking` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |


### 📁 Module: `api/evaluator`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `GET` | `/api/evaluator/marks-entry-dashboard` | `EvaluatorDashboardController@dashboard` | Eloquent ORM | None | **Models:** `EvaluatorAllocation` |
| `GET` | `/api/evaluator/marks-entry-courses` | `EvaluatorDashboardController@courseList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/evaluator/subject-list` | `EvaluatorController@subjectList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `GET` | `/api/evaluator/exam-year-list` | `EvaluatorController@examYearList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/evaluator/examiner-subject-list` | `EvaluatorController@evaluatorSubjectList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations as pea` |
| `POST` | `/api/evaluator/examiner-institute-list` | `EvaluatorController@evaluatorInstituteList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations as pea` |
| `GET` | `/api/evaluator/evaluator-list` | `EvaluatorController@evaluatorList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator`, `pharmacy_subjects_master`, `institute_master` |
| `GET` | `/api/evaluator/evaluator-detail` | `EvaluatorController@evaluatorDetail` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator as e` |
| `GET` | `/api/evaluator/evaluator-profile-info` | `EvaluatorController@evaluatorProfileInfo` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator as pe`, `pharmacy_evaluator_allocations as pea` |
| `POST` | `/api/evaluator/evaluator-submit` | `EvaluatorController@evaluatorSubmit` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator` |
| `POST` | `/api/evaluator/evaluator-allocation-submit` | `EvaluatorController@allocationSubmit` | Eloquent ORM | None | **Models:** `EvaluatorAllocation` |
| `GET` | `/api/evaluator/evaluator-download-pdf` | `EvaluatorController@downloadPdf` | Eloquent ORM | None | **Models:** `EvaluatorDetails` |
| `POST` | `/api/evaluator/evaluator-send-mail` | `EvaluatorController@sendMail` | Eloquent ORM | None | **Models:** `EvaluatorDetails` |
| `POST` | `/api/evaluator/evaluator-allocation-list` | `EvaluatorController@allocationList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations`, `pharmacy_evaluator` |
| `GET` | `/api/evaluator/evaluator-allocation-detail` | `EvaluatorController@allocationDetail` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations`, `pharmacy_evaluator` |
| `GET` | `/api/evaluator/allocation-subject-list` | `EvaluatorController@alocationSubjectList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_evaluator_allocations` |
| `POST` | `/api/evaluator/evaluator-roll-list` | `EvaluatorController@evaluatorRollList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator` |
| `GET` | `/api/evaluator/evaluator-allocation-inst-list` | `EvaluatorController@evaluatorAllocationInstList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations`, `pharmacy_CDC_ins_tagging` |
| `GET` | `/api/evaluator/evaluator-allocation-cdc-list` | `EvaluatorController@evaluatorAllocationCDCList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_cdc_master` |
| `POST` | `/api/evaluator/inst-allocation-summary` | `EvaluatorInstAllocationController@getSummary` | Stored Procedure / PostgreSQL Function | `fn_admin_getevaluatorinstallocationsummary_v1` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/admin`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/admin/pone-student-list` | `AdminController@studentListPone` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/admin/institutes` | `AdminInstituteController@getAllInstitutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallinstitutes_v1` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-institute` | `AdminInstituteController@saveInstitute` | Stored Procedure / PostgreSQL Function | `fn_save_instritute` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-instritute` | `AdminInstituteController@saveInstitute` | Stored Procedure / PostgreSQL Function | `fn_save_instritute` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/admin/institute-list` | `AdminInstituteController@getInstituteList` | Stored Procedure / PostgreSQL Function | `fn_get_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/institute-list` | `AdminInstituteController@getInstituteList` | Stored Procedure / PostgreSQL Function | `fn_get_institute_list` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/admin/ra-institute-list` | `AdminInstituteController@getRaInstituteList` | Stored Procedure / PostgreSQL Function | `fn_get_ra_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/ra-institute-list` | `AdminInstituteController@getRaInstituteList` | Stored Procedure / PostgreSQL Function | `fn_get_ra_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/ra-student-list` | `EnrollmentController@getRaStudentList` | Stored Procedure / PostgreSQL Function | `fn_get_ra_student_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-enroll-student-details` | `EnrollmentController@getEnrollStudentDetails` | Stored Procedure / PostgreSQL Function | `fn_getenrollstudentdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/enroll-student-details` | `EnrollmentController@getEnrollStudentDetails` | Stored Procedure / PostgreSQL Function | `fn_getenrollstudentdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/update-student-enrollment-status` | `EnrollmentController@updateStudentEnrollmentStatusByAdmin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentenrollmentstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/update-student-enrollment-status-by-admin` | `EnrollmentController@updateStudentEnrollmentStatusByAdmin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentenrollmentstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/ra-details-by-registration-number` | `ExaminationController@getRaDetailsListByStudentRegistrationNumber` | Stored Procedure / PostgreSQL Function | `fn_getradetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/update-ra-studentmarks` | `ExaminationController@updateRaStudentMarks` | Stored Procedure / PostgreSQL Function | `fn_update_ra_studentmarks`<br>`fn_save_ra_decission_student` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/update-ra-student-marks` | `ExaminationController@updateRaStudentMarks` | Stored Procedure / PostgreSQL Function | `fn_update_ra_studentmarks`<br>`fn_save_ra_decission_student` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/all-examination-institutes` | `AdminInstituteController@getAllExaminationInstitutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/examination-institutes` | `AdminInstituteController@getAllExaminationInstitutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/admin/examination-institutes` | `AdminInstituteController@getAllExaminationInstitutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-get-all-examination-institutes` | `AdminInstituteController@getAllExaminationInstitutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-examination-center` | `ExaminationController@saveExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_save_examinationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-examinationcenter` | `ExaminationController@saveExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_save_examinationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-examination-center` | `ExaminationController@getExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-examinationcenter` | `ExaminationController@getExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/examination-centers` | `ExaminationController@getExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-get-examination-center` | `ExaminationController@getExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/student-count-in-examination-center` | `ExaminationController@getStudentCountInExaminationCenter` | Stored Procedure / PostgreSQL Function | `fn_get_studentcountinexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-routine` | `ExaminationController@saveRoutine` | Stored Procedure / PostgreSQL Function | `fn_admin_saveroutine` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-save-routine` | `ExaminationController@saveRoutine` | Stored Procedure / PostgreSQL Function | `fn_admin_saveroutine` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/routine-list` | `ExaminationController@getRoutineList` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-routine-list` | `ExaminationController@getRoutineList` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-routinelist` | `ExaminationController@getRoutineList` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/routinelist` | `ExaminationController@getRoutineList` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-admit-card-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admit-card-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-admitcard-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admitcard-details` | `ExaminationController@getAdmitCardDetails` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-student-routine-info` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/student-routine-info` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-get-student-routine-info` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/student-routine` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-student-routine` | `ExaminationController@getStudentRoutineInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-admit-card-with-routine` | `ExaminationController@getAdmitCardWithRoutine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admit-card-with-routine` | `ExaminationController@getAdmitCardWithRoutine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-admitcard-with-routine` | `ExaminationController@getAdmitCardWithRoutine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-get-student-admit-card-routine` | `ExaminationController@getAdmitCardWithRoutine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/semesters` | `AdminSemesterController@getAllSemesters` | Stored Procedure / PostgreSQL Function | `fn_admin_getallsemesters` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/subject-categories` | `AdminSubjectCategoryController@getAllSubjectCategories` | Stored Procedure / PostgreSQL Function | `fn_admin_getallsubjectcategory` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/departments` | `AdminDepartmentController@getDepartmentsByInst` | Stored Procedure / PostgreSQL Function | `fn_admin_getdepartmentsbyinst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/subjects` | `AdminSubjectController@getDeptAllSubjects` | Stored Procedure / PostgreSQL Function | `fn_admin_getdeptallsubjects_v1` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/subject-details` | `AdminSubjectController@getSubjectDetails` | Stored Procedure / PostgreSQL Function | `fn_admin_getsubjects_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-routine-subject-details` | `AdminSubjectController@getRoutineSubjectDetails` | Stored Procedure / PostgreSQL Function | `fn_admin_getroutinesubjects_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-bank-info` | `AdminController@saveBankInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_savebankinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-bank-info` | `AdminController@getBankInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getbankinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-bank-info-by-ifsc` | `AdminController@getBankInfoByIfsc` | Stored Procedure / PostgreSQL Function | `fn_admin_getbankinfobyifsc` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/dashboard` | `AdminController@getDashboard` | Stored Procedure / PostgreSQL Function | `fn_admin_getdashboard` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-entered-student-marks-info` | `AdminController@getEnteredStudentMarksInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getenteredstudentmarksinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-teacher` | `AdminTeacherController@saveTeacherWithSubjects` | Stored Procedure / PostgreSQL Function | `fn_admin_saveteacherinfo`<br>`fn_admin_saveteacherassignsubject_v1` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-assigned-teachers` | `AdminTeacherController@getAssignedTeacherInfo` | Stored Procedure + Query Builder / ORM | `fn_admin_getassignedteacherinfo` | **Models:** `AdminTeacherController` |
| `POST` | `/api/admin/get-marks-entered-teachers-info` | `AdminTeacherController@getMarksEnteredTeachersInfo` | Stored Procedure + Query Builder / ORM | `fn_admin_getmarksenteredteachersinfo` | **Models:** `AdminTeacherController` |
| `POST` | `/api/admin/delete-teacher` | `AdminTeacherController@deleteTeacherInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_deleteteacherinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-evaluator-subject-allocation-summary` | `AdminTeacherController@getEvaluatorSubjectAllocationSummary` | Stored Procedure / PostgreSQL Function | `fn_admin_getevaluatorsubjectallocationsummary` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-review-evaluator-subject-allocation-summary` | `AdminTeacherController@getReviewEvaluatorSubjectAllocationSummary` | Stored Procedure / PostgreSQL Function | `fn_admin_getreview_evaluatorsubjectallocationsummary` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-review-evaluator-inst-allocation-summary` | `AdminTeacherController@getReviewEvaluatorInstAllocationSummary` | Stored Procedure / PostgreSQL Function | `fn_admin_getreviewevaluatorinstallocationsummary` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-review-student-marks-info` | `StudentMarksController@getReviewStudentMarksInfo` | Stored Procedure / PostgreSQL Function | `fn_admin_getreviewstudentmarksinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-review-teacher-assign-subject` | `AdminTeacherController@saveReviewTeacherAssignSubject` | Stored Procedure / PostgreSQL Function | `fn_admin_savereviewteacherassignsubject` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-review-subject-marks` | `AdminStudentMarksController@saveReviewSubjectMarks` | Stored Procedure / PostgreSQL Function | `fn_save_review_subject_marks` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-student-marks` | `AdminStudentMarksController@saveStudentMarks` | Stored Procedure / PostgreSQL Function | `fn_admin_savestudentmarks_v3` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/institute-wise-summary` | `AdminInstituteWiseSummaryController@getInstituteWiseSummary` | Stored Procedure / PostgreSQL Function | `fn_admin_getinstitutewisesummary` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/examiner-wise-summary` | `AdminInstituteWiseSummaryController@getExaminerWiseSummary` | Stored Procedure / PostgreSQL Function | `fn_admin_examinerwisesummary` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/admin/district-list` | `AdminDistrictController@getDistrictListByAdmin` | Stored Procedure / PostgreSQL Function | `fn_getdistrictlistbyadmin` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/marks-entry`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/marks-entry/institute-list` | `MarksEntryController@instituteList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_exam_center` |
| `POST` | `/api/marks-entry/marks-entry-subject-list` | `MarksEntryController@marksEntrysubjectList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/marks-entry/list` | `MarksEntryController@list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/marks-entry/submit` | `MarksEntryController@submit` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/marks-entry/unlock-all` | `MarksEntryController@unlockAllMarks` | Not Found | None | N/A |
| `POST` | `/api/marks-entry/download-marks-sheet` | `MarksEntryController@downloadMarksSheet` | Not Found | None | N/A |
| `POST` | `/api/marks-entry/written-marksfolio-pdf` | `MarksEntryController@downloadWrittenFolio` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_exam_marks_pone as pem`, `pharmacy_evaluator_allocations as pea` |
| `POST` | `/api/marks-entry/hoe-list` | `MarksEntryController@hoeList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/marks-entry/hoe-submit` | `MarksEntryController@hoeSubmit` | Not Found | None | N/A |


### 📁 Module: `api/reports`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/reports/registered-student-report-list` | `ReportController@registeredStudentReportList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final as s` |
| `GET` | `/api/reports/student-details-by-institute-admin` | `ReportController@studentDetailsListByInstituteAdmin` | Stored Procedure / PostgreSQL Function | `fn_getstudentdetailslistbyinstrituteadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/student-details-by-institute-admin` | `ReportController@studentDetailsListByInstituteAdmin` | Stored Procedure / PostgreSQL Function | `fn_getstudentdetailslistbyinstrituteadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/update-student-phone-or-aadhaar-details-by-inst-admin` | `ReportController@updateStudentPhoneOrAadhaarDetailsByInstAdmin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentphoneoraadhaardetailsbyinstadmin` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/registered-student-details-by-institute-admin` | `ReportController@registeredStudentDetailsListByInstituteAdmin` | Stored Procedure / PostgreSQL Function | `fn_getregistredstudentdetailslistbyinstrituteadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/registered-student-details-by-institute-admin` | `ReportController@registeredStudentDetailsListByInstituteAdmin` | Stored Procedure / PostgreSQL Function | `fn_getregistredstudentdetailslistbyinstrituteadmin` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/registered-student-details-by-student-id` | `ReportController@registeredStudentDetailsListByStudentId` | Stored Procedure / PostgreSQL Function | `fn_getregistredstudentdetailslistbystudentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/registered-student-details-by-student-id` | `ReportController@registeredStudentDetailsListByStudentId` | Stored Procedure / PostgreSQL Function | `fn_getregistredstudentdetailslistbystudentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/update-student-details-by-admin` | `ReportController@updateStudentDetailsByAdmin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentdetailsbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/update-student-details-by-admin-v1` | `ReportController@updateStudentDetailsByAdminV1` | Stored Procedure / PostgreSQL Function | `fn_updatestudentdetailsbyadmin_v1` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/update-student-registration-status-by-admin` | `ReportController@updateStudentRegistrationStatusByAdmin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentregistrationstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/save-confirmation-status` | `ReportController@saveConfirmationStatus` | Stored Procedure / PostgreSQL Function | `fn_save_confirmation_status` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/result-department-wise-report-list` | `ReportController@resultDepartmentWiseReportList` | Not Found | None | N/A |
| `GET` | `/api/reports/result-subject-wise-report-list` | `ReportController@resultSubjectWiseReportList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `2024_pharmacy_result_pone` |
| `GET` | `/api/reports/student-result-report` | `ReportController@studentResultReport` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/reports/result-status-count-inst` | `ReportController@resultStatusCountInst` | Stored Procedure / PostgreSQL Function | `fn_getresulatstatuscount_inst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/result-status-count-inst` | `ReportController@resultStatusCountInst` | Stored Procedure / PostgreSQL Function | `fn_getresulatstatuscount_inst` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/result-status-details-inst` | `ReportController@resultStatusDetailsInst` | Stored Procedure / PostgreSQL Function | `fn_getresulatstatusdetails_inst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/result-status-details-inst` | `ReportController@resultStatusDetailsInst` | Stored Procedure / PostgreSQL Function | `fn_getresulatstatusdetails_inst` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/student-marks-details` | `ReportController@studentMarksDetails` | Stored Procedure / PostgreSQL Function | `fn_getstudentmarksdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/student-marks-details` | `ReportController@studentMarksDetails` | Stored Procedure / PostgreSQL Function | `fn_getstudentmarksdetails` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/review-details-by-registration-number` | `ReportController@reviewDetailsListByStudentRegistrationNumber` | Stored Procedure / PostgreSQL Function | `fn_getreviewdetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/review-details-by-registration-number` | `ReportController@reviewDetailsListByStudentRegistrationNumber` | Stored Procedure / PostgreSQL Function | `fn_getreviewdetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/student-review-details-by-council` | `ReportController@studentReviewDetailsListByCouncil` | Stored Procedure / PostgreSQL Function | `fn_getstudentreviewdetailslistbycouncil` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/student-review-details-by-council` | `ReportController@studentReviewDetailsListByCouncil` | Stored Procedure / PostgreSQL Function | `fn_getstudentreviewdetailslistbycouncil` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/student-registration-download` | `ReportController@studentRegistrationDownload` | Stored Procedure / PostgreSQL Function | `fn_downlaodstudentregitration_registrationid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/student-registration-download` | `ReportController@studentRegistrationDownload` | Stored Procedure / PostgreSQL Function | `fn_downlaodstudentregitration_registrationid` | None (Uses Stored Procedure / Function) |
| `GET,POST` | `/api/reports/result-download-inst` | `ReportController@resultCertificateDownloadInst` | Stored Procedure / PostgreSQL Function | `fn_getresulatdownload_inst` | None (Uses Stored Procedure / Function) |
| `GET,POST` | `/api/reports/result-certificate-download-inst` | `ReportController@resultCertificateDownloadInst` | Stored Procedure / PostgreSQL Function | `fn_getresulatdownload_inst` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/review`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `GET` | `/api/review/list` | `ReviewController@getReviewList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_appl_review_apply`, `pharmacy_subjects_master` |
| `POST` | `/api/review/student-review-subjects` | `ReviewController@getStudentReviewSubject` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_appl_review_apply`, `pharmacy_schedule_master`, `pharmacy_subjects_master` |
| `POST` | `/api/review/teacher-list-of-review-subject` | `ReviewController@getTeacherListOfReviewSubject` | Stored Procedure / PostgreSQL Function | `fn_get_teacherlistofreviewsubject` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/review/teacher-list-of-review-subject` | `ReviewController@getTeacherListOfReviewSubject` | Stored Procedure / PostgreSQL Function | `fn_get_teacherlistofreviewsubject` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/review/teacher-info-by-institute` | `ReviewController@getTeacherInfoByInstitute` | Stored Procedure / PostgreSQL Function | `fn_get_teacherinfobyinst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/teacher-info-by-institute` | `ReviewController@getTeacherInfoByInstitute` | Stored Procedure / PostgreSQL Function | `fn_get_teacherinfobyinst` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/review/student-details-by-teacher` | `ReviewController@getReviewStudentDetailsByTeacherId` | Stored Procedure / PostgreSQL Function | `fn_get_reviewstudentdetailsbyteacherid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/student-details-by-teacher` | `ReviewController@getReviewStudentDetailsByTeacherId` | Stored Procedure / PostgreSQL Function | `fn_get_reviewstudentdetailsbyteacherid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/save-pharmacy-review-subject` | `ReviewController@savePharmacyReviewSubject` | Stored Procedure / PostgreSQL Function | `fn_save_pharmacy_review_subject` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/savePharmacyReviewSubject` | `ReviewController@savePharmacyReviewSubject` | Stored Procedure / PostgreSQL Function | `fn_save_pharmacy_review_subject` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/student-review-apply` | `ReviewController@applyForReview` | Eloquent ORM + Query Builder | None | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_appl_review_apply` |
| `POST` | `/api/review/review-payment` | `PaymentController@reviewPayment` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`<br>**Tables:** `pharmacy_fees` |
| `GET` | `/api/review/review-receipt` | `ReviewController@reviewReceipt` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_appl_review_apply`, `pharmacy_subjects_master` |
| `GET` | `/api/review/review-list` | `ReviewController@getReviewList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_appl_review_apply`, `pharmacy_subjects_master` |
| `POST` | `/api/review/institute-list` | `ReviewController@marksEntryVerifyInstituteList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/review/subject-list` | `ReviewController@marksEntryVerifySubjectList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/review/marks-entry/list` | `ReviewController@marksEntryList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `$table_name as ea` |
| `POST` | `/api/review/marks-entry/submit` | `ReviewController@marksEntrySubmit` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/review/marks-verify/hoe-list` | `ReviewController@MarksVerifyhoeList` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `$table_name as ea` |


### 📁 Module: `api/admin-details`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `GET` | `/api/admin-details/by-username` | `AdminDetailsController@getByUsername` | Stored Procedure / PostgreSQL Function | `fn_getadmindetailsbyusername` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin-details/by-username` | `AdminDetailsController@getByUsername` | Stored Procedure / PostgreSQL Function | `fn_getadmindetailsbyusername` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin-details/raw` | `AdminDetailsController@getRaw` | Stored Procedure / PostgreSQL Function | `fn_getadmindetailsbyusername` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/student-details`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `GET` | `/api/student-details/by-username` | `StudentController@getByUsername` | Stored Procedure / PostgreSQL Function | `fn_getstudentdetailsbyusername` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/student-details/by-username` | `StudentController@getByUsername` | Stored Procedure / PostgreSQL Function | `fn_getstudentdetailsbyusername` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/generate-otp`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/generate-otp/send` | `GenerateOtpController@generate` | Stored Procedure / PostgreSQL Function | `fn_generateotp`<br>`fn_getadmindetailsbyusername` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/generate-otp/update-otp-used` | `GenerateOtpController@updateOtpUsed` | Stored Procedure / PostgreSQL Function | `fn_updateuserotpbycontactno` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/generate-otp/verify` | `GenerateOtpController@verifyOtp` | Stored Procedure + Query Builder / ORM | `fn_generateotp`<br>`fn_getadmindetailsbyusername`<br>`fn_getlatestotpbyusername`<br>`fn_updateuserotpbycontactno` | **Models:** `Token` |


### 📁 Module: `api/admin/schedule`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/admin/schedule/check` | `AdminScheduleController@checkSchedule` | Stored Procedure / PostgreSQL Function | `fn_admin_check_schedule` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/marks`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/marks/student-marks-info-v1` | `StudentMarksController@getStudentMarksInfoV1` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentmarksinfo_v2` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/sms`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/sms/broadcast` | `SmsBroadcastController@sendBulkSms` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |


### 📁 Module: `api/mail`

| HTTP Method | Route Endpoint | Controller & Action | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|:---|
| `POST` | `/api/mail/broadcast` | `EmailBroadcastController@sendBulkEmail` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/mail/send-registration-cancellation-mail` | `EmailBroadcastController@sendRegistrationCancellationMail` | Stored Procedure + Query Builder / ORM | `fn_getregistredstudentdetailslistbystudentid` | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_roll_no` |


