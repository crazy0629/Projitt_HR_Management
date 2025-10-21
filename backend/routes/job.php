<?php

use App\Http\Controllers\Job\JobApplicantCertificateController;
use App\Http\Controllers\Job\JobApplicantController;
use App\Http\Controllers\Job\JobApplicantEducationController;
use App\Http\Controllers\Job\JobApplicantExperienceController;
use App\Http\Controllers\Job\JobApplicationQuestionAnswerController;
use App\Http\Controllers\Job\JobController;
use App\Http\Controllers\Job\JobStageController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {

    // Add and Edit job detail
    Route::post('add', [JobController::class, 'add']);
    Route::post('edit', [JobController::class, 'edit']);

    // Update specific sections
    Route::post('edit-description', [JobController::class, 'editDescription']);
    Route::post('edit-media', [JobController::class, 'editMedia']);
    Route::post('edit-questions', [JobController::class, 'editQuestions']);

    // Fetch operations
    Route::get('single/{id}', [JobController::class, 'single']);
    Route::get('list-with-filters', [JobController::class, 'listAllWithFilters']);
    Route::get('intellisense-search', [JobController::class, 'intellisenseSearch']);

    Route::post('publish', [JobController::class, 'publishJob']);

    Route::post('change-status', [JobController::class, 'changeStatus']);
    Route::delete('delete', [JobController::class, 'delete']);

    Route::get('duplicate/{id}', [JobController::class, 'DuplicateJob']);

   
    Route::post('applicant-reject', [JobApplicantController::class, 'rejectJobApplicant']);

    Route::post('stage-add', [JobStageController::class, 'add']);
    Route::post('stage-edit', [JobStageController::class, 'edit']);
    Route::get('stage-single/{id}', [JobStageController::class, 'single']);
    Route::get('stage', [JobStageController::class, 'listByJob']);
    Route::delete('stage-delete', [JobStageController::class, 'delete']);
    Route::post('stage-change-order', [JobStageController::class, 'changeJobStageOrder']);
    
    
});

Route::middleware('applicant.onboarded')->group(function () {


    Route::post('edit-applicant-contact-info', [JobApplicantController::class, 'editJobApplicantContactInfo']);
    Route::post('edit-applicant-cv-cover', [JobApplicantController::class, 'editJobApplicantCvAndCover']);
    Route::post('edit-applicant-info', [JobApplicantController::class, 'editJobApplicantInfo']);

    Route::post('applicant-experience-add', [JobApplicantExperienceController::class, 'add']);
    Route::post('applicant-experience-edit', [JobApplicantExperienceController::class, 'edit']);
    Route::get('applicant-experience-single/{id}', [JobApplicantExperienceController::class, 'single']);
    Route::get('applicant-experience', [JobApplicantExperienceController::class, 'listByApplicant']);
    Route::delete('applicant-experience-delete', [JobApplicantExperienceController::class, 'delete']);

    Route::post('applicant-education-add', [JobApplicantEducationController::class, 'add']);
    Route::post('applicant-education-edit', [JobApplicantEducationController::class, 'edit']);
    Route::get('applicant-education-single/{id}', [JobApplicantEducationController::class, 'single']);
    Route::get('applicant-education', [JobApplicantEducationController::class, 'listByApplicant']);
    Route::delete('applicant-education-delete', [JobApplicantEducationController::class, 'delete']);

    Route::post('applicant-certificate-add', [JobApplicantCertificateController::class, 'add']);
    Route::post('applicant-certificate-edit', [JobApplicantCertificateController::class, 'edit']);
    Route::get('applicant-certificate-single/{id}', [JobApplicantCertificateController::class, 'single']);
    Route::get('applicant-certificate', [JobApplicantCertificateController::class, 'listByApplicant']);
    Route::delete('applicant-certificate-delete', [JobApplicantCertificateController::class, 'delete']);

    Route::get('applicant-questions-answers', [JobApplicationQuestionAnswerController::class, 'single']);
    Route::post('applicant-questions-answers/update', [JobApplicationQuestionAnswerController::class, 'submitApplicantAnswer']);
    Route::post('applicant-submit', [JobApplicantController::class, 'submitJobApplicantion']);
    Route::post('applicant-change-email', [JobApplicantController::class, 'changeEmail']);

});

Route::middleware('either.auth.or.onboarded')->group(function () {

    Route::get('applicant-single', [JobApplicantController::class, 'single']);
    Route::get('get-applicant-jobs', [JobApplicantController::class, 'getApplicantJobs']);
    Route::get('get-job-applicant', [JobApplicantController::class, 'getJobsApplicant']);

    Route::post('update-current-stage', [JobApplicantController::class, 'UpdateCurrentStage']);
    Route::get('intellisense-search', [JobController::class, 'intellisenseSearch']);

});
