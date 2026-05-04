<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExaminationController;
use App\Http\Controllers\EvaluatorController;
use App\Http\Controllers\AnswersheetController;
use App\Http\Controllers\MarksEntryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/reset-password', [AuthController::class, 'reset_password']);

Route::post('/authenticate', [AuthController::class, 'authenticate']);
Route::post('/validate-security-code', [AuthController::class, 'validateSecurityCode']);
Route::get('/check-status/{user_id}', [StudentController::class, 'checkRedirect']);
Route::post('/print-reg-certificate', [StudentController::class, 'printRegistrationCertificate']);
Route::post('/registration-report-download', [StudentController::class, 'regReportDownload']);
Route::get('/download-reg-report-zip', [StudentController::class, 'downloadRegReportZip']);
Route::get('/download-reg-zip', [StudentController::class, 'downloadRegZip']);
Route::prefix('student')->middleware('authenticate')->group(function () {
   // Route::post('/student-info-update', [StudentController::class, 'studentInfoUpdate']);
    Route::get('/download-form/{from_num}', [StudentController::class, 'downloadForm'])->withoutMiddleware('authenticate');
    Route::get('/student-details/{from_num}', [StudentController::class, 'studentdetails']);

    Route::get('/session-list', [StudentController::class, 'sessionList']);
    Route::post('/institute-add', [MasterController::class, 'instituteAdd']);
    Route::get('/institute-list/{i_code?}', [StudentController::class, 'instituteList']);
    Route::post('/branch-add', [MasterController::class, 'branchAdd']);

    Route::post('/eligible-for-registration-list', [StudentController::class, 'studentRegEligibleList']);
    //Route::post('/eligible-for-registration', [StudentController::class, 'studentRegEligible']);
    Route::post('/registration-list', [StudentController::class, 'registrationList']);
    Route::post('/generate-reg-numbers', [StudentController::class, 'generateRegNo']);
    Route::post('/cancel-reg-numbers', [StudentController::class, 'registrationCancel']);
    Route::post('/print-registration-list', [StudentController::class, 'printRegistrationList']);
    Route::post('/registration-report-list', [StudentController::class, 'regReportList']);

    Route::post('/get-data-for-syllabus', [StudentController::class, 'getDataforsyllabus']);
    Route::get('/syllabus-id-list', [StudentController::class, 'syllabus_idList']);
    Route::post('/syllabus-tag', [StudentController::class, 'syllabusTagSubmit']);
    Route::post('/subject-list', [StudentController::class, 'subjectList']);
    Route::post('/submit-elective-subject', [StudentController::class, 'submitElectivePaper']);
});
Route::prefix('enrollment')->middleware('authenticate')->group(function () {
    Route::post('/re-admission-list', [EnrollmentController::class, 're_admission_list']);
    Route::post('/re-admission-submit', [EnrollmentController::class, 're_admission_submit']);
    Route::post('/list', [EnrollmentController::class, 'list']);

    Route::post('/get-enrollment-fees-data', [PaymentController::class, 'getEnrollmentPaymentdata']);
    Route::post('/enroll-payment', [PaymentController::class, 'payment'])->withoutMiddleware('authenticate');
    Route::post('/payment-success', [PaymentController::class, 'enrollmentPaymentSuccess']);
    Route::post('/payment-faill', [PaymentController::class, 'enrollmentPaymentFail']);

    Route::post('/enroll-payment-offline', [PaymentController::class, 'paymentOffline']);
    Route::get('/enrollment-receipt', [EnrollmentController::class, 'enrollmentReceipt'])->withoutMiddleware('authenticate');
    Route::post('/submit', [EnrollmentController::class, 'enrollmentsubmit']);
    Route::get('/enrollment-download', [EnrollmentController::class, 'enrollmentDownload'])->withoutMiddleware('authenticate');
    Route::post('/rollno-generate-list', [EnrollmentController::class, 'rollno_generate_list']);
    Route::post('/rollno-generate-submit', [EnrollmentController::class, 'rollno_generate_submit']);
    Route::post('/exam-center-list', [EnrollmentController::class, 'exam_center_list']);
    Route::post('/exam-center-submit', [EnrollmentController::class, 'exam_center_submit']);
});

Route::prefix('master')->middleware('authenticate')->group(function () {
    Route::get('/syllabus-subject-list/{part}', [AdminController::class, 'syllabusSubjectList']);
    Route::post('/state-list/{user_type?}', [AdminController::class, 'allStates']);
    Route::post('/district-list/{user_type?}', [AdminController::class, 'allDistricts']);
    Route::get('/subdivision-list/{dist_id?}/{user_type?}', [AdminController::class, 'allSubdivisions']);
    Route::post('/block-municipality-list/{user_type?}', [AdminController::class, 'allBlockMunicipality']);
    Route::get('/students-board-list', [AdminController::class, 'studentsBoardList']);

    //Holiday Master
    Route::post('/import-holidayList', [MasterController::class, 'importHolidayList']);
    Route::post('/insert-update-holiday', [MasterController::class, 'updateHolidayList']);
    Route::post('/get-holiday-list', [MasterController::class, 'getHolidayList']);

    Route::get('/away-center-list', [MasterController::class, 'allAwayCenter']);
    Route::post('/get-exam-schedule', [MasterController::class, 'getExamSchedule']);
    Route::post('/save-exam-schedule', [MasterController::class, 'saveExamSchedule']);
    Route::post('/subject-list-all', [MasterController::class, 'subjectList']);
    Route::post('/subject-list-theory', [MasterController::class, 'subjectListTheory']);

    Route::get('/schedule-list', [MasterController::class, 'scheduleList']);
    Route::post('/schedule-create', [MasterController::class, 'scheduleCreate']);
    Route::put('/schedule-update', [MasterController::class, 'scheduleUpdate']);
    Route::delete('/schedule-delete/{id}', [MasterController::class, 'scheduleDelete']);
    Route::post('/schedule-check', [MasterController::class, 'scheduleCheck']);

    Route::post('/cdc-submit', [MasterController::class, 'createCdc']);
    Route::get('/cdc-list', [MasterController::class, 'cdcList']);
    Route::post('/cdc-institute-tagging', [MasterController::class, 'cdcInstTagg']);
});

Route::prefix('attendance')->middleware('authenticate')->group(function () {
    Route::post('/room-allotment-list', [AttendanceController::class, 'roomAllotmentList']);
    Route::post('/room-allotment-submit', [AttendanceController::class, 'roomAllotmentSubmit']);
    Route::post('/college-wise-institute', [AttendanceController::class, 'collegeWiseInstitute']);
    Route::post('/institute-wise-center', [AttendanceController::class, 'instituteWiseCenter']);

    Route::post('/list-attendance', [AttendanceController::class, 'listAttendance']);
    Route::post('/update-attendance', [AttendanceController::class, 'updateAttendance']);
    Route::post('/final-submit-attendance', [AttendanceController::class, 'finalSubmitAttendance']);
    Route::post('/attendance-unlock', [AttendanceController::class, 'attendanceUnlock']);
    Route::post('/attendance-unlock-all', [AttendanceController::class, 'attendanceunlockAll']);

    Route::post('/center-list', [AttendanceController::class, 'listCentercode']);
});

Route::prefix('examinations')->middleware('authenticate')->group(function () {
    Route::get('/admit-card-download', [ExaminationController::class, 'downloadAdmitCard'])->withoutMiddleware('authenticate');
    Route::get('/admit-card-download-inbulk', [ExaminationController::class, 'downloadAdmitCardInbulk'])->withoutMiddleware('authenticate');
    Route::post('/admit-card-download-list', [ExaminationController::class, 'downloadAdmitCardList']);

    Route::post('/hall-sticker-institute-list', [ExaminationController::class, 'getHsInstituteList']);
    Route::post('/admin-hall-sticker-away-center', [ExaminationController::class, 'getAdminHsAwayList']);
    Route::post('/hall-sticker-list', [ExaminationController::class, 'hallStickerList']);
    Route::get('/hall-sticker-download', [ExaminationController::class, 'hallStickerDownload'])->withoutMiddleware('authenticate');
	
	//sachi 17112025
	Route::post('/descriptive-roll-institute-list', [ExaminationController::class, 'getHsInstituteList']);
    //sachi 17112025
	Route::post('/admin-descriptive-roll-away-center', [ExaminationController::class, 'getAdminHsAwayList']);
	Route::post('/descriptive-roll', [ExaminationController::class, 'descriptiveRoll']);
    Route::get('/descriptive-roll-download', [ExaminationController::class, 'descriptiveRollDownload'])->withoutMiddleware('authenticate');
    
    //sachi 17112025
	Route::post('/top-sheet-institute-list', [ExaminationController::class, 'getHsInstituteList']);
    //sachi 17112025
	Route::post('/admin-top-sheet-away-center', [ExaminationController::class, 'getAdminHsAwayList']);
    Route::post('/top-sheet-count', [ExaminationController::class, 'topSheetCount']);
    Route::get('/top-sheet-download', [ExaminationController::class, 'topSheetDownload'])->withoutMiddleware('authenticate');
    
    Route::get('/packing-slip', [ExaminationController::class, 'packingSlipDownload']);
    Route::get('/printing-instruction', [ExaminationController::class, 'printingInstruction']);
    Route::get('/decoding-list', [ExaminationController::class, 'decodingList']);

    Route::get('/center-wise-students/{part_sem}/{exam_year}/{center_code}', [ExaminationController::class, 'getCenterWiseStudents'])->withoutMiddleware('authenticate');
});

Route::prefix('answersheet')->middleware('authenticate')->group(function () {
    Route::get('/subject-list', [AnswersheetController::class, 'subjectList']);
    Route::post('/submit-mask-setup', [AnswersheetController::class, 'updateMaskSetup']);
    Route::post('/get-mask-list', [AnswersheetController::class, 'getMaskList']);
    Route::post('/institute-list', [AnswersheetController::class, 'getInstituteList']);
    Route::post('/list-for-serial', [AnswersheetController::class, 'listAnswersheetSerialEntry']);

    # Route::post('/final-submit-attendance', [AttendanceController::class, 'finalSubmitAttendance']);
    Route::post('/final-submit-masking', [AnswersheetController::class, 'finalSubmitMasking']);
});

Route::prefix('evaluator')->middleware('authenticate')->group(function () {
    Route::get('/subject-list', [EvaluatorController::class, 'subjectList']);
    Route::get('/evaluator-list', [EvaluatorController::class, 'evaluatorList']);
    Route::get('/evaluator-detail', [EvaluatorController::class, 'evaluatorDetail']);
    Route::post('/evaluator-submit', [EvaluatorController::class, 'evaluatorSubmit']);

    Route::post('/evaluator-allocation-submit', [EvaluatorController::class, 'allocationSubmit']);
    Route::get('evaluator-download-pdf', [EvaluatorController::class, 'downloadPdf']);
    Route::post('evaluator-send-mail', [EvaluatorController::class, 'sendMail']);
    Route::post('/evaluator-allocation-list', [EvaluatorController::class, 'allocationList']);
    Route::get('/evaluator-allocation-detail', [EvaluatorController::class, 'allocationDetail']);
    Route::get('/allocation-subject-list', [EvaluatorController::class, 'alocationSubjectList']);
    Route::post('/evaluator-roll-list', [EvaluatorController::class, 'evaluatorRollList']);
    Route::get('/evaluator-allocation-inst-list', [EvaluatorController::class, 'evaluatorAllocationInstList']);
    Route::get('/evaluator-allocation-cdc-list', [EvaluatorController::class, 'evaluatorAllocationCDCList']);
});

Route::prefix('admin')->middleware('authenticate')->group(function () {
    Route::post('/pone-student-list', [AdminController::class, 'studentListPone']);
});

Route::prefix('marks-entry')->middleware('authenticate')->group(function () {
    Route::post('/institute-list', [MarksEntryController::class, 'instituteList']);
    Route::post('/marks-entry-subject-list', [MarksEntryController::class, 'marksEntrysubjectList']);
    Route::post('/list', [MarksEntryController::class, 'list']);
    Route::post('/submit', [MarksEntryController::class, 'submit']);

    Route::post('/hoe-list', [MarksEntryController::class, 'hoeList']);
    Route::post('/hoe-submit', [MarksEntryController::class, 'hoeSubmit']);
});
//->middleware('authenticate')
Route::prefix('reports')->group(function () {
    Route::post('/registered-student-report-list', [ReportController::class, 'registeredStudentReportList']);
    Route::get('/result-department-wise-report-list', [ReportController::class, 'resultDepartmentWiseReportList']);
    Route::get('/result-subject-wise-report-list', [ReportController::class, 'resultSubjectWiseReportList']);
    Route::get('/student-result-report', [ReportController::class, 'studentResultReport']);
});

Route::prefix('review')->middleware('authenticate')->group(function () {

    Route::get('/list', [ReviewController::class, 'getReviewList']);
    Route::post('/student-review-subjects', [ReviewController::class, 'getStudentReviewSubject']);
    Route::post('/student-review-apply', [ReviewController::class, 'applyForReview']);
    Route::post('/review-payment', [PaymentController::class, 'reviewPayment']);
    Route::get('/review-receipt', [ReviewController::class, 'reviewReceipt'])->withoutMiddleware('authenticate');
    Route::get('/review-list', [ReviewController::class, 'getReviewList']);

    Route::post('/institute-list', [ReviewController::class, 'marksEntryVerifyInstituteList']);
    Route::post('/subject-list', [ReviewController::class, 'marksEntryVerifySubjectList']);

    Route::post('/marks-entry/list', [ReviewController::class, 'marksEntryList']);

    Route::post('/marks-entry/submit', [ReviewController::class, 'marksEntrySubmit']);
    Route::post('/marks-verify/hoe-list', [ReviewController::class, 'MarksVerifyhoeList']);

});