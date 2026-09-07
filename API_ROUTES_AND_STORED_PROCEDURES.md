# Pharmacy Services API Routes & Stored Procedures Mapping

This document provides a comprehensive analysis of all API routes defined in [`routes/api.php`](file:///d:/git/pharmacy_services/services/routes/api.php), and whether they execute **Stored Procedures / PostgreSQL Database Functions** (`fn_*`, `sp_*`) or utilize Laravel's **Eloquent ORM / Query Builder (`DB::table`)** architecture.

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
| 19 | `fn_admin_getexaminationcenterbyinstcode` |
| 20 | `fn_admin_getinstitutewisesummary` |
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

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `GET` | `/api/user` | Eloquent ORM | None | Sanctum Auth (`$request->user()`) |
| `POST` | `/api/reset-password` | Eloquent ORM | None | **Models:** `SuperUser` |
| `POST` | `/api/authenticate` | Stored Procedure + Query Builder / ORM | `fn_generateotp_student` | **Models:** `SuperUser`, `Otp`, `EvaluatorDetails`, `Token`, `EvaluatorAllocation`, `Registerstudent`<br>**Tables:** `institute_master` |
| `POST` | `/api/validate-security-code` | Eloquent ORM | None | **Models:** `Otp`, `Registerstudent`, `Token`, `EvaluatorDetails`, `SuperUser`, `EvaluatorAllocation` |
| `GET` | `/api/check-status/{user_id}` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/print-reg-certificate` | Stored Procedure + Query Builder / ORM | `fn_generate_registration_certificates` | **Tables:** `registration_certificate_issue` |
| `GET` | `/api/print-reg-certificate-single/{reg_num?}/{sess_yr?}` | Stored Procedure + Query Builder / ORM | `fn_generate_registration_certificates` | **Tables:** `registration_certificate_issue` |
| `GET,POST` | `/api/print-result-certificate-single` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/registration-report-download` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final` |
| `GET` | `/api/download-reg-report-zip` | Eloquent ORM | None | **Models:** `Zip` |
| `GET` | `/api/download-reg-zip` | Eloquent ORM | None | **Models:** `Zip` |
| `GET,POST` | `/api/result-download-inst` | Stored Procedure / PostgreSQL Function | `fn_getresulatdownload_inst` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/student`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/student/student-info-update` | Eloquent ORM + Query Builder | None | **Models:** `Token`, `User`, `Registerstudent`<br>**Tables:** `pharmacy_auth_roles_permissions`, `pharmacy_auth_urls`, `pharmacy_student_extraInfo` |
| `GET` | `/api/student/download-form/{from_num}` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final` |
| `GET` | `/api/student/student-details/{from_num}` | Eloquent ORM + Query Builder | None | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_fees`, `pharmacy_student_extraInfo`, `pharmacy_schedule_master`, `pharmacy_appl_review_apply` |
| `GET` | `/api/student/session-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/student/institute-add` | Eloquent ORM + Query Builder | None | **Models:** `Token`, `User`<br>**Tables:** `pharmacy_auth_roles_permissions`, `pharmacy_auth_urls`, `institute_master` |
| `GET` | `/api/student/institute-list/{i_code?}` | Eloquent ORM | None | **Models:** `Institute` |
| `POST` | `/api/student/branch-add` | Eloquent ORM + Query Builder | None | **Models:** `Token`, `User`<br>**Tables:** `pharmacy_auth_roles_permissions`, `pharmacy_auth_urls`, `pharmacy_branch_master` |
| `POST` | `/api/student/eligible-for-registration-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/student/eligible-for-registration` | Eloquent ORM + Query Builder | None | **Models:** `Token`, `User`, `Registerstudent`<br>**Tables:** `pharmacy_auth_roles_permissions`, `pharmacy_auth_urls` |
| `POST` | `/api/student/registration-list` | Query Builder (`DB::table`) | None | **Tables:** `tbl_student_master as sm` |
| `POST` | `/api/student/generate-reg-numbers` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/student/cancel-reg-numbers` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/student/send-registration-cancellation-mail` | Stored Procedure + Query Builder / ORM | `fn_getregistredstudentdetailslistbystudentid` | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_roll_no` |
| `POST` | `/api/student/print-registration-list` | Query Builder (`DB::table`) | None | **Tables:** `tbl_student_master as sm` |
| `POST` | `/api/student/registration-report-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final` |
| `POST` | `/api/student/get-data-for-syllabus` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/student/syllabus-id-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/student/syllabus-tag` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/student/subject-list` | Eloquent ORM | None | **Models:** `Registerstudent`, `Subject`, `Elective` |
| `POST` | `/api/student/submit-elective-subject` | Eloquent ORM | None | **Models:** `Registerstudent`, `Elective` |
| `POST` | `/api/student/check-student-eligible-in-exam` | Stored Procedure / PostgreSQL Function | `fn_check_studenteligible_inexam_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/student/check-exam-eligibility` | Stored Procedure / PostgreSQL Function | `fn_check_studenteligible_inexam_studentid` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/enrollment`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/enrollment/re-admission-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_result`, `pharmacy_register_student_final` |
| `POST` | `/api/enrollment/re-admission-submit` | Eloquent ORM + Query Builder | None | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_register_student_final`, `pharmacy_re_admission` |
| `GET` | `/api/enrollment/ra-institute-list` | Stored Procedure / PostgreSQL Function | `fn_get_ra_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/ra-institute-list` | Stored Procedure / PostgreSQL Function | `fn_get_ra_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/ra-student-list` | Stored Procedure / PostgreSQL Function | `fn_get_ra_student_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/get-enroll-student-details` | Stored Procedure / PostgreSQL Function | `fn_getenrollstudentdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/enroll-student-details` | Stored Procedure / PostgreSQL Function | `fn_getenrollstudentdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/update-student-enrollment-status` | Stored Procedure / PostgreSQL Function | `fn_updatestudentenrollmentstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/update-student-enrollment-status-by-admin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentenrollmentstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_result`, `pharmacy_register_student_final` |
| `POST` | `/api/enrollment/get-enrollment-fees-data` | Eloquent ORM + Query Builder | None | **Models:** `Enrollment`<br>**Tables:** `pharmacy_fees`, `pharmacy_schedule_master` |
| `POST` | `/api/enrollment/enroll-payment` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`<br>**Tables:** `pharmacy_fees` |
| `POST` | `/api/enrollment/payment-success` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`, `Payment`, `Registerstudent`, `Enrollment`<br>**Tables:** `pharmacy_appl_review_apply` |
| `POST` | `/api/enrollment/payment-faill` | Eloquent ORM | None | **Models:** `PaymentTransaction` |
| `POST` | `/api/enrollment/institute-payment` | Eloquent ORM | None | **Models:** `PaymentTransaction` |
| `POST` | `/api/enrollment/institute-payment-success` | Eloquent ORM | None | **Models:** `PaymentTransaction`, `Payment` |
| `POST` | `/api/enrollment/institute-payment-fail` | Eloquent ORM | None | **Models:** `PaymentTransaction` |
| `GET` | `/api/enrollment/institute-payment-receipt/{order_id}` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`<br>**Tables:** `institute_master` |
| `POST` | `/api/enrollment/enroll-payment-offline` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`, `Payment`, `Registerstudent`, `Enrollment`<br>**Tables:** `pharmacy_fees` |
| `GET` | `/api/enrollment/enrollment-receipt` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final as rsf` |
| `POST` | `/api/enrollment/submit` | Eloquent ORM + Query Builder | None | **Models:** `Enrollment`, `Registerstudent`<br>**Tables:** `pharmacy_subjects_master`, `pharmacy_result` |
| `GET` | `/api/enrollment/enrollment-download` | Eloquent ORM | None | **Models:** `PaymentTransaction` |
| `POST` | `/api/enrollment/rollno-generate-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_enrollment` |
| `POST` | `/api/enrollment/rollno-generate-submit` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_enrollment`, `pharmacy_roll_no`, `pharmacy_register_student_final`, `pharmacy_re_admission`, `pharmacy_result`, `pharmacy_subjects_master`, `exam_attendance_pone`, `pharmacy_exam_center` |
| `POST` | `/api/enrollment/exam-center-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_center` |
| `POST` | `/api/enrollment/exam-center-submit` | Eloquent ORM + Query Builder | None | **Models:** `Institute`<br>**Tables:** `pharmacy_exam_center` |
| `POST` | `/api/enrollment/generate-order-id` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/enrollment/generate-student-order-id` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/payment`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/payment/save-pharmacy-payment-response` | Stored Procedure / PostgreSQL Function | `fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/savePharmacyPaymentResponse` | Stored Procedure / PostgreSQL Function | `fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/pending-payment-detail` | Stored Procedure / PostgreSQL Function | `fn_getpendingpaymentdetails`<br>`fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/pending-payment-details` | Stored Procedure / PostgreSQL Function | `fn_getpendingpaymentdetails`<br>`fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/getPendingPaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_getpendingpaymentdetails`<br>`fn_savepharmacypaymentresponse` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/student-payment-type` | Stored Procedure / PostgreSQL Function | `fn_get_studentpayment_type_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/student-payment-type-studentid` | Stored Procedure / PostgreSQL Function | `fn_get_studentpayment_type_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/student-payment-response` | Stored Procedure / PostgreSQL Function | `fn_get_payment_response_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generateOrderId` | Stored Procedure / PostgreSQL Function | `fn_student_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generate-order-id` | Stored Procedure / PostgreSQL Function | `fn_student_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generateReviewOrderId` | Stored Procedure / PostgreSQL Function | `fn_student_review_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generate-review-order-id` | Stored Procedure / PostgreSQL Function | `fn_student_review_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generate-enrollment-order-id` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/generateEnrollmentOrderId` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/enrollment-student-generate-order-id` | Stored Procedure / PostgreSQL Function | `fn_enrollmentstudent_generateorderid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/saveSbiPaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_savesbipaymentdtls` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/save-sbi-payment-details` | Stored Procedure / PostgreSQL Function | `fn_savesbipaymentdtls` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/savePaymentDetails` | Stored Procedure / PostgreSQL Function | `fn_savepaymentdtls` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/save-payment-details` | Stored Procedure / PostgreSQL Function | `fn_savepaymentdtls` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/updateSbiPaymentResponse` | Stored Procedure / PostgreSQL Function | `fn_updatesbipaymentresponse` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/payment/update-sbi-payment-response` | Stored Procedure / PostgreSQL Function | `fn_updatesbipaymentresponse` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/getPaymentDetailsByTransNo` | Stored Procedure / PostgreSQL Function | `fn_getpaymentdetailsbytransno` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/get-payment-details-by-trans-no` | Stored Procedure / PostgreSQL Function | `fn_getpaymentdetailsbytransno` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/details-by-transaction/{transactionNo}` | Stored Procedure / PostgreSQL Function | `fn_getpaymentdetailsbytransno` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/payment/sbiPayment` | Stored Procedure / PostgreSQL Function | `fn_getpaymentdetailsbytransno` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/document`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/document/upload` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/document/upload-general` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/document/upload-multiple` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `DELETE` | `/api/document/delete` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/document/info` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |


### 📁 Module: `api/master`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `GET` | `/api/master/syllabus-subject-list/{part}` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/master/state-list/{user_type?}` | Eloquent ORM | None | **Models:** `State` |
| `POST` | `/api/master/district-list/{user_type?}` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/master/subdivision-list/{dist_id?}/{user_type?}` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/master/block-municipality-list/{user_type?}` | Eloquent ORM | None | **Models:** `BlockMunicipality` |
| `GET` | `/api/master/students-board-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/master/council-board-list` | Stored Procedure / PostgreSQL Function | `fn_get_council_board_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/master/import-holidayList` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/master/insert-update-holiday` | Eloquent ORM | None | **Models:** `Holiday` |
| `POST` | `/api/master/get-holiday-list` | Eloquent ORM | None | **Models:** `Holiday` |
| `GET` | `/api/master/away-center-list` | Eloquent ORM | None | **Models:** `CenterAllocation` |
| `POST` | `/api/master/get-exam-schedule` | Eloquent ORM | None | **Models:** `Subject`, `ExamSchedule` |
| `POST` | `/api/master/save-exam-schedule` | Eloquent ORM | None | **Models:** `ExamSchedule` |
| `POST` | `/api/master/subject-list-all` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/master/subject-list-theory` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `GET` | `/api/master/schedule-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/master/schedule-create` | Eloquent ORM | None | **Models:** `Schedule` |
| `PUT` | `/api/master/schedule-update` | Eloquent ORM | None | **Models:** `Schedule` |
| `DELETE` | `/api/master/schedule-delete/{id}` | Eloquent ORM | None | **Models:** `Schedule` |
| `POST` | `/api/master/schedule-check` | Eloquent ORM | None | **Models:** `Schedule` |
| `POST` | `/api/master/cdc-submit` | Eloquent ORM | None | **Models:** `CDC` |
| `GET` | `/api/master/cdc-list` | Eloquent ORM | None | **Models:** `CDC` |
| `POST` | `/api/master/cdc-institute-tagging` | Eloquent ORM | None | **Models:** `CDC`, `Institute`, `CDCInstituteTag` |


### 📁 Module: `api/attendance`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/attendance/room-allotment-list` | Eloquent ORM + Query Builder | None | **Models:** `Attendancepone`<br>**Tables:** `pharmacy_roll_no` |
| `POST` | `/api/attendance/room-allotment-submit` | Eloquent ORM + Query Builder | None | **Models:** `Attendancepone`, `Rollnomodel`<br>**Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/attendance/college-wise-institute` | Eloquent ORM | None | **Models:** `Examcenter` |
| `POST` | `/api/attendance/institute-wise-center` | Eloquent ORM | None | **Models:** `Examcenter` |
| `POST` | `/api/attendance/list-attendance` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/attendance/update-attendance` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/attendance/final-submit-attendance` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/attendance/attendance-unlock` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo`, `MarksEntryPone`, `MarksEntryPtwo` |
| `POST` | `/api/attendance/attendance-unlock-all` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/attendance/center-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_exam_center` |


### 📁 Module: `api/examinations`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `GET` | `/api/examinations/admit-card-download` | Query Builder (`DB::table`) | None | **Tables:** `exam_attendance_pone as a` |
| `GET` | `/api/examinations/admit-card-download-inbulk` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/admit-card-download-list` | Query Builder (`DB::table`) | None | **Tables:** `exam_attendance_pone as a` |
| `POST` | `/api/examinations/hall-sticker-institute-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/admin-hall-sticker-away-center` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_center` |
| `POST` | `/api/examinations/hall-sticker-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/hall-sticker-download` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/descriptive-roll-institute-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/admin-descriptive-roll-away-center` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_center` |
| `POST` | `/api/examinations/descriptive-roll` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/descriptive-roll-download` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final` |
| `POST` | `/api/examinations/top-sheet-institute-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/admin-top-sheet-away-center` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_center` |
| `POST` | `/api/examinations/top-sheet-count` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/top-sheet-download` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/packing-slip` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_schedule` |
| `GET` | `/api/examinations/printing-instruction` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/examinations/decoding-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_exam_schedule` |
| `GET` | `/api/examinations/center-wise-students/{part_sem}/{exam_year}/{center_code}` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/examinations/check-student-eligible-in-exam` | Stored Procedure / PostgreSQL Function | `fn_check_studenteligible_inexam_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/check-student-eligible` | Stored Procedure / PostgreSQL Function | `fn_check_studenteligible_inexam_studentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/ra-details-by-registration-number` | Stored Procedure / PostgreSQL Function | `fn_getradetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/ra-details-list-by-student-registration-number` | Stored Procedure / PostgreSQL Function | `fn_getradetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/update-ra-studentmarks` | Stored Procedure / PostgreSQL Function | `fn_update_ra_studentmarks`<br>`fn_save_ra_decission_student` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/update-ra-student-marks` | Stored Procedure / PostgreSQL Function | `fn_update_ra_studentmarks`<br>`fn_save_ra_decission_student` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/all-examination-institutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/examination-institutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/examination-institutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/save-examination-center` | Stored Procedure / PostgreSQL Function | `fn_save_examinationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/save-examinationcenter` | Stored Procedure / PostgreSQL Function | `fn_save_examinationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-examination-center` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-examinationcenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/examination-centers` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admin-get-examination-center` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-examination-center-by-instcode` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenterbyinstcode` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admin-get-examination-center-by-instcode` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenterbyinstcode` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/student-count-in-examination-center` | Stored Procedure / PostgreSQL Function | `fn_get_studentcountinexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/save-routine` | Stored Procedure / PostgreSQL Function | `fn_admin_saveroutine` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admin-save-routine` | Stored Procedure / PostgreSQL Function | `fn_admin_saveroutine` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/routine-list` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-routine-list` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-routinelist` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/routinelist` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-admit-card-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admit-card-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-admitcard-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admitcard-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-student-routine-info` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/student-routine-info` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admin-get-student-routine-info` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/student-routine` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-student-routine` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-admit-card-with-routine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admit-card-with-routine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-admitcard-with-routine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/admin-get-student-admit-card-routine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/get-admit-card-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/admit-card-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/get-admitcard-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/admitcard-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/examinations/get-admit-card-with-routine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-appearing-candidates-by-inst` | Stored Procedure / PostgreSQL Function | `fn_admin_getappearingcandidateddetailsbyinst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/update-exam-attendance-status-by-inst` | Stored Procedure / PostgreSQL Function | `fn_updateexamattendencestatusbyinst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/examinations/get-topsheet-list-by-inst` | Stored Procedure / PostgreSQL Function | `fn_admin_gettopsheetlistbyinst` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/answersheet`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `GET` | `/api/answersheet/subject-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/answersheet/submit-mask-setup` | Eloquent ORM | None | **Models:** `Answersheetmasking` |
| `POST` | `/api/answersheet/get-mask-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/answersheet/institute-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/answersheet/list-for-serial` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |
| `POST` | `/api/answersheet/final-submit-masking` | Eloquent ORM | None | **Models:** `Attendancepone`, `Attendanceptwo` |


### 📁 Module: `api/evaluator`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `GET` | `/api/evaluator/marks-entry-dashboard` | Eloquent ORM | None | **Models:** `EvaluatorAllocation` |
| `GET` | `/api/evaluator/marks-entry-courses` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/evaluator/subject-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `GET` | `/api/evaluator/exam-year-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/evaluator/examiner-subject-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations as pea` |
| `POST` | `/api/evaluator/examiner-institute-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations as pea` |
| `GET` | `/api/evaluator/evaluator-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator`, `pharmacy_subjects_master`, `institute_master` |
| `GET` | `/api/evaluator/evaluator-detail` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator as e` |
| `GET` | `/api/evaluator/evaluator-profile-info` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator as pe`, `pharmacy_evaluator_allocations as pea` |
| `POST` | `/api/evaluator/evaluator-submit` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator` |
| `POST` | `/api/evaluator/evaluator-allocation-submit` | Eloquent ORM | None | **Models:** `EvaluatorAllocation` |
| `GET` | `/api/evaluator/evaluator-download-pdf` | Eloquent ORM | None | **Models:** `EvaluatorDetails` |
| `POST` | `/api/evaluator/evaluator-send-mail` | Eloquent ORM | None | **Models:** `EvaluatorDetails` |
| `POST` | `/api/evaluator/evaluator-allocation-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations`, `pharmacy_evaluator` |
| `GET` | `/api/evaluator/evaluator-allocation-detail` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations`, `pharmacy_evaluator` |
| `GET` | `/api/evaluator/allocation-subject-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_evaluator_allocations` |
| `POST` | `/api/evaluator/evaluator-roll-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator` |
| `GET` | `/api/evaluator/evaluator-allocation-inst-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_evaluator_allocations`, `pharmacy_CDC_ins_tagging` |
| `GET` | `/api/evaluator/evaluator-allocation-cdc-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_cdc_master` |
| `POST` | `/api/evaluator/inst-allocation-summary` | Stored Procedure / PostgreSQL Function | `fn_admin_getevaluatorinstallocationsummary_v1` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/admin`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/admin/pone-student-list` | Eloquent ORM | None | **Models:** `Registerstudent` |
| `POST` | `/api/admin/institutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallinstitutes_v1` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-institute` | Stored Procedure / PostgreSQL Function | `fn_save_instritute` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-instritute` | Stored Procedure / PostgreSQL Function | `fn_save_instritute` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/admin/institute-list` | Stored Procedure / PostgreSQL Function | `fn_get_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/institute-list` | Stored Procedure / PostgreSQL Function | `fn_get_institute_list` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/admin/ra-institute-list` | Stored Procedure / PostgreSQL Function | `fn_get_ra_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/ra-institute-list` | Stored Procedure / PostgreSQL Function | `fn_get_ra_institute_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/ra-student-list` | Stored Procedure / PostgreSQL Function | `fn_get_ra_student_list` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-enroll-student-details` | Stored Procedure / PostgreSQL Function | `fn_getenrollstudentdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/enroll-student-details` | Stored Procedure / PostgreSQL Function | `fn_getenrollstudentdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/update-student-enrollment-status` | Stored Procedure / PostgreSQL Function | `fn_updatestudentenrollmentstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/update-student-enrollment-status-by-admin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentenrollmentstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/ra-details-by-registration-number` | Stored Procedure / PostgreSQL Function | `fn_getradetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/update-ra-studentmarks` | Stored Procedure / PostgreSQL Function | `fn_update_ra_studentmarks`<br>`fn_save_ra_decission_student` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/update-ra-student-marks` | Stored Procedure / PostgreSQL Function | `fn_update_ra_studentmarks`<br>`fn_save_ra_decission_student` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/all-examination-institutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/examination-institutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/admin/examination-institutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-get-all-examination-institutes` | Stored Procedure / PostgreSQL Function | `fn_admin_getallexaminationinstitutes` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-examination-center` | Stored Procedure / PostgreSQL Function | `fn_save_examinationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-examinationcenter` | Stored Procedure / PostgreSQL Function | `fn_save_examinationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-examination-center` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-examinationcenter` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/examination-centers` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-get-examination-center` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-examination-center-by-instcode` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenterbyinstcode` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-get-examination-center-by-instcode` | Stored Procedure / PostgreSQL Function | `fn_admin_getexaminationcenterbyinstcode` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/student-count-in-examination-center` | Stored Procedure / PostgreSQL Function | `fn_get_studentcountinexaminationcenter` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-routine` | Stored Procedure / PostgreSQL Function | `fn_admin_saveroutine` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-save-routine` | Stored Procedure / PostgreSQL Function | `fn_admin_saveroutine` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/routine-list` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-routine-list` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-routinelist` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/routinelist` | Stored Procedure / PostgreSQL Function | `fn_get_routinelist` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-admit-card-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admit-card-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-admitcard-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admitcard-details` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-student-routine-info` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/student-routine-info` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-get-student-routine-info` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/student-routine` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-student-routine` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-admit-card-with-routine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admit-card-with-routine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-admitcard-with-routine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/admin-get-student-admit-card-routine` | Stored Procedure / PostgreSQL Function | `fn_getadmitcard_details`<br>`fn_admin_getstudentroutineinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/semesters` | Stored Procedure / PostgreSQL Function | `fn_admin_getallsemesters` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/subject-categories` | Stored Procedure / PostgreSQL Function | `fn_admin_getallsubjectcategory` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/departments` | Stored Procedure / PostgreSQL Function | `fn_admin_getdepartmentsbyinst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/subjects` | Stored Procedure / PostgreSQL Function | `fn_admin_getdeptallsubjects_v1` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/subject-details` | Stored Procedure / PostgreSQL Function | `fn_admin_getsubjects_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-routine-subject-details` | Stored Procedure / PostgreSQL Function | `fn_admin_getroutinesubjects_details` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-bank-info` | Stored Procedure / PostgreSQL Function | `fn_admin_savebankinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-bank-info` | Stored Procedure / PostgreSQL Function | `fn_admin_getbankinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-bank-info-by-ifsc` | Stored Procedure / PostgreSQL Function | `fn_admin_getbankinfobyifsc` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/dashboard` | Stored Procedure / PostgreSQL Function | `fn_admin_getdashboard` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-entered-student-marks-info` | Stored Procedure / PostgreSQL Function | `fn_admin_getenteredstudentmarksinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-teacher` | Stored Procedure / PostgreSQL Function | `fn_admin_saveteacherinfo`<br>`fn_admin_saveteacherassignsubject_v1` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-assigned-teachers` | Stored Procedure + Query Builder / ORM | `fn_admin_getassignedteacherinfo` | **Models:** `AdminTeacherController` |
| `POST` | `/api/admin/get-marks-entered-teachers-info` | Stored Procedure + Query Builder / ORM | `fn_admin_getmarksenteredteachersinfo` | **Models:** `AdminTeacherController` |
| `POST` | `/api/admin/delete-teacher` | Stored Procedure / PostgreSQL Function | `fn_admin_deleteteacherinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-evaluator-subject-allocation-summary` | Stored Procedure / PostgreSQL Function | `fn_admin_getevaluatorsubjectallocationsummary` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-review-evaluator-subject-allocation-summary` | Stored Procedure / PostgreSQL Function | `fn_admin_getreview_evaluatorsubjectallocationsummary` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-review-evaluator-inst-allocation-summary` | Stored Procedure / PostgreSQL Function | `fn_admin_getreviewevaluatorinstallocationsummary` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/get-review-student-marks-info` | Stored Procedure / PostgreSQL Function | `fn_admin_getreviewstudentmarksinfo` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-review-teacher-assign-subject` | Stored Procedure / PostgreSQL Function | `fn_admin_savereviewteacherassignsubject` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-review-subject-marks` | Stored Procedure / PostgreSQL Function | `fn_save_review_subject_marks` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/save-student-marks` | Stored Procedure / PostgreSQL Function | `fn_admin_savestudentmarks_v3` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/institute-wise-summary` | Stored Procedure / PostgreSQL Function | `fn_admin_getinstitutewisesummary` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin/examiner-wise-summary` | Stored Procedure / PostgreSQL Function | `fn_admin_examinerwisesummary` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/admin/district-list` | Stored Procedure / PostgreSQL Function | `fn_getdistrictlistbyadmin` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/marks-entry`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/marks-entry/institute-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_exam_center` |
| `POST` | `/api/marks-entry/marks-entry-subject-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/marks-entry/list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/marks-entry/submit` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/marks-entry/unlock-all` | Not Found | None | N/A |
| `POST` | `/api/marks-entry/download-marks-sheet` | Not Found | None | N/A |
| `POST` | `/api/marks-entry/written-marksfolio-pdf` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `pharmacy_exam_marks_pone as pem`, `pharmacy_evaluator_allocations as pea` |
| `POST` | `/api/marks-entry/hoe-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/marks-entry/hoe-submit` | Not Found | None | N/A |


### 📁 Module: `api/reports`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/reports/registered-student-report-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_register_student_final as s` |
| `GET` | `/api/reports/student-details-by-institute-admin` | Stored Procedure / PostgreSQL Function | `fn_getstudentdetailslistbyinstrituteadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/student-details-by-institute-admin` | Stored Procedure / PostgreSQL Function | `fn_getstudentdetailslistbyinstrituteadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/update-student-phone-or-aadhaar-details-by-inst-admin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentphoneoraadhaardetailsbyinstadmin` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/registered-student-details-by-institute-admin` | Stored Procedure / PostgreSQL Function | `fn_getregistredstudentdetailslistbyinstrituteadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/registered-student-details-by-institute-admin` | Stored Procedure / PostgreSQL Function | `fn_getregistredstudentdetailslistbyinstrituteadmin` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/registered-student-details-by-student-id` | Stored Procedure / PostgreSQL Function | `fn_getregistredstudentdetailslistbystudentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/registered-student-details-by-student-id` | Stored Procedure / PostgreSQL Function | `fn_getregistredstudentdetailslistbystudentid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/update-student-details-by-admin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentdetailsbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/update-student-details-by-admin-v1` | Stored Procedure / PostgreSQL Function | `fn_updatestudentdetailsbyadmin_v1` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/update-student-registration-status-by-admin` | Stored Procedure / PostgreSQL Function | `fn_updatestudentregistrationstatusbyadmin` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/save-confirmation-status` | Stored Procedure / PostgreSQL Function | `fn_save_confirmation_status` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/result-department-wise-report-list` | Not Found | None | N/A |
| `GET` | `/api/reports/result-subject-wise-report-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `2024_pharmacy_result_pone` |
| `GET` | `/api/reports/student-result-report` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `GET` | `/api/reports/result-status-count-inst` | Stored Procedure / PostgreSQL Function | `fn_getresulatstatuscount_inst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/result-status-count-inst` | Stored Procedure / PostgreSQL Function | `fn_getresulatstatuscount_inst` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/result-status-details-inst` | Stored Procedure / PostgreSQL Function | `fn_getresulatstatusdetails_inst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/result-status-details-inst` | Stored Procedure / PostgreSQL Function | `fn_getresulatstatusdetails_inst` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/student-marks-details` | Stored Procedure / PostgreSQL Function | `fn_getstudentmarksdetails` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/student-marks-details` | Stored Procedure / PostgreSQL Function | `fn_getstudentmarksdetails` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/review-details-by-registration-number` | Stored Procedure / PostgreSQL Function | `fn_getreviewdetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/review-details-by-registration-number` | Stored Procedure / PostgreSQL Function | `fn_getreviewdetailslistbystudentregistartionnumber` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/student-review-details-by-council` | Stored Procedure / PostgreSQL Function | `fn_getstudentreviewdetailslistbycouncil` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/student-review-details-by-council` | Stored Procedure / PostgreSQL Function | `fn_getstudentreviewdetailslistbycouncil` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/reports/student-registration-download` | Stored Procedure / PostgreSQL Function | `fn_downlaodstudentregitration_registrationid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/reports/student-registration-download` | Stored Procedure / PostgreSQL Function | `fn_downlaodstudentregitration_registrationid` | None (Uses Stored Procedure / Function) |
| `GET,POST` | `/api/reports/result-download-inst` | Stored Procedure / PostgreSQL Function | `fn_getresulatdownload_inst` | None (Uses Stored Procedure / Function) |
| `GET,POST` | `/api/reports/result-certificate-download-inst` | Stored Procedure / PostgreSQL Function | `fn_getresulatdownload_inst` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/review`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `GET` | `/api/review/list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_appl_review_apply`, `pharmacy_subjects_master` |
| `POST` | `/api/review/student-review-subjects` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_appl_review_apply`, `pharmacy_schedule_master`, `pharmacy_subjects_master` |
| `POST` | `/api/review/teacher-list-of-review-subject` | Stored Procedure / PostgreSQL Function | `fn_get_teacherlistofreviewsubject` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/review/teacher-list-of-review-subject` | Stored Procedure / PostgreSQL Function | `fn_get_teacherlistofreviewsubject` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/review/teacher-info-by-institute` | Stored Procedure / PostgreSQL Function | `fn_get_teacherinfobyinst` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/teacher-info-by-institute` | Stored Procedure / PostgreSQL Function | `fn_get_teacherinfobyinst` | None (Uses Stored Procedure / Function) |
| `GET` | `/api/review/student-details-by-teacher` | Stored Procedure / PostgreSQL Function | `fn_get_reviewstudentdetailsbyteacherid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/student-details-by-teacher` | Stored Procedure / PostgreSQL Function | `fn_get_reviewstudentdetailsbyteacherid` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/save-pharmacy-review-subject` | Stored Procedure / PostgreSQL Function | `fn_save_pharmacy_review_subject` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/savePharmacyReviewSubject` | Stored Procedure / PostgreSQL Function | `fn_save_pharmacy_review_subject` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/review/student-review-apply` | Eloquent ORM + Query Builder | None | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_appl_review_apply` |
| `POST` | `/api/review/review-payment` | Eloquent ORM + Query Builder | None | **Models:** `PaymentTransaction`<br>**Tables:** `pharmacy_fees` |
| `GET` | `/api/review/review-receipt` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_appl_review_apply`, `pharmacy_subjects_master` |
| `GET` | `/api/review/review-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_appl_review_apply`, `pharmacy_subjects_master` |
| `POST` | `/api/review/institute-list` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/review/subject-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master` |
| `POST` | `/api/review/marks-entry/list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `$table_name as ea` |
| `POST` | `/api/review/marks-entry/submit` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/review/marks-verify/hoe-list` | Query Builder (`DB::table`) | None | **Tables:** `pharmacy_subjects_master`, `$table_name as ea` |


### 📁 Module: `api/admin-details`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `GET` | `/api/admin-details/by-username` | Stored Procedure / PostgreSQL Function | `fn_getadmindetailsbyusername` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin-details/by-username` | Stored Procedure / PostgreSQL Function | `fn_getadmindetailsbyusername` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/admin-details/raw` | Stored Procedure / PostgreSQL Function | `fn_getadmindetailsbyusername` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/student-details`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `GET` | `/api/student-details/by-username` | Stored Procedure / PostgreSQL Function | `fn_getstudentdetailsbyusername` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/student-details/by-username` | Stored Procedure / PostgreSQL Function | `fn_getstudentdetailsbyusername` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/generate-otp`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/generate-otp/send` | Stored Procedure / PostgreSQL Function | `fn_generateotp`<br>`fn_getadmindetailsbyusername` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/generate-otp/update-otp-used` | Stored Procedure / PostgreSQL Function | `fn_updateuserotpbycontactno` | None (Uses Stored Procedure / Function) |
| `POST` | `/api/generate-otp/verify` | Stored Procedure + Query Builder / ORM | `fn_generateotp`<br>`fn_getadmindetailsbyusername`<br>`fn_getlatestotpbyusername`<br>`fn_updateuserotpbycontactno` | **Models:** `Token` |


### 📁 Module: `api/admin/schedule`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/admin/schedule/check` | Stored Procedure / PostgreSQL Function | `fn_admin_check_schedule` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/marks`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/marks/student-marks-info-v1` | Stored Procedure / PostgreSQL Function | `fn_admin_getstudentmarksinfo_v2` | None (Uses Stored Procedure / Function) |


### 📁 Module: `api/sms`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/sms/broadcast` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |


### 📁 Module: `api/mail`

| HTTP Method | Route Endpoint | Architecture / Mechanism | Stored Procedure(s) Called | ORM / Query Builder Structure |
|:---|:---|:---|:---|:---|
| `POST` | `/api/mail/broadcast` | Non-DB / External Service / Utility | None | None (File generation, broadcast, or static config) |
| `POST` | `/api/mail/send-registration-cancellation-mail` | Stored Procedure + Query Builder / ORM | `fn_getregistredstudentdetailslistbystudentid` | **Models:** `Registerstudent`<br>**Tables:** `pharmacy_roll_no` |


