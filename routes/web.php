<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttemptController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialProgressController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

/*
|--------------------------------------------------------------------------
| Enrolled-user routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'course.enrolled'])->group(function () {

    Route::resource('courses', CourseController::class)->parameters([
        'courses' => 'id',
    ]);

    Route::prefix('courses/{id}/participants')->name('courses.participants.')->group(function () {
        Route::get('/', [CourseController::class, 'indexParticipants'])->name('index');
        Route::post('/', [CourseController::class, 'addParticipant'])->name('store');
        Route::put('/{participantId}', [CourseController::class, 'updateParticipantRole'])->name('update');
        Route::delete('/{participantId}', [CourseController::class, 'removeParticipant'])->name('destroy');
    });

    Route::post('/materials/{material}/progress', [MaterialProgressController::class, 'update'])
        ->name('materials.progress.update');

});

/*
|--------------------------------------------------------------------------
| General authenticated routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/profile', [UserController::class, 'profileEdit'])->name('profile.edit');
    Route::put('/profile/{id}', [UserController::class, 'profileUpdate'])->name('profile.update');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');

    /*
    |----------------------------------------------------------------------
    | Sections
    |----------------------------------------------------------------------
    */

    Route::post('/sections/reorder', [SectionController::class, 'reorder'])->name('sections.reorder');
    Route::resource('sections', SectionController::class);

    /*
    |----------------------------------------------------------------------
    | Materials
    |----------------------------------------------------------------------
    */

    Route::get('/materials/create/{section}', [MaterialController::class, 'create'])->name('materials.create');
    Route::get('/media/{asset}/download', [MaterialController::class, 'download'])->name('media.download');
    Route::resource('materials', MaterialController::class);

    /*
    |----------------------------------------------------------------------
    | Question Banks & Questions
    |----------------------------------------------------------------------
    */

    Route::resource('questionbank', QuestionBankController::class)->names([
        'index' => 'questionbank.index',
        'show' => 'questionbank.show',
    ]);

    Route::post('/questionbank/{questionBank}/questions/reorder', [QuestionBankController::class, 'reorder'])
        ->name('questionbank.questions.reorder');

    Route::resource('questions', QuestionController::class);

    /*
    |----------------------------------------------------------------------
    | Quizzes
    |----------------------------------------------------------------------
    */

    Route::get('quizzes/create/{section}', [QuizController::class, 'create'])->name('quizzes.create');
    Route::get('quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::get('quizzes/{quiz}/attempts', [QuizController::class, 'attempts'])->name('quizzes.attempts.index');
    Route::post('quizzes/{quiz}/attempts', [AttemptController::class, 'store'])->name('quizzes.attempts.store');

    Route::resource('quizzes', QuizController::class)->except(['create', 'show']);

    Route::prefix('quizzes/{quiz}/questions')->name('quiz-questions.')->group(function () {
        Route::get('/', [QuizQuestionController::class, 'index'])->name('index');
        Route::get('/available', [QuizQuestionController::class, 'available'])->name('available');
        Route::post('/', [QuizQuestionController::class, 'store'])->name('store');
        Route::post('/random', [QuizQuestionController::class, 'storeRandom'])->name('random');
        Route::post('/new', [QuizQuestionController::class, 'storeNew'])->name('store-new');
        Route::post('/reorder', [QuizQuestionController::class, 'reorder'])->name('reorder');
        Route::patch('/{question}/point-override', [QuizQuestionController::class, 'updatePointOverride'])->name('point-override');
        Route::delete('/{question}', [QuizQuestionController::class, 'destroy'])->name('destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Quiz Attempts
    |----------------------------------------------------------------------
    */

    Route::prefix('attempts/{attempt}')->name('attempts.')->group(function () {
        Route::get('/take', [AttemptController::class, 'take'])->name('take');
        Route::post('/answer', [AttemptController::class, 'saveAnswer'])->name('answer');
        Route::post('/answer/file', [AttemptController::class, 'uploadAnswerFile'])->name('answer.file');
        Route::delete('/answer/file/{mediaAsset}', [AttemptController::class, 'deleteAnswerFile'])->name('answer.file.delete');
        Route::post('/submit', [AttemptController::class, 'submit'])->name('submit');
    });

    /*
    |----------------------------------------------------------------------
    | Assignments
    |----------------------------------------------------------------------
    */

    Route::get('sections/{section}/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('sections/{section}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::get('assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::put('assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');

    // Only routes NOT already defined manually above — no more name/URI
    // collisions with the routes just above this.
    Route::resource('assignments', AssignmentController::class)
        ->only(['index', 'destroy']);

    /*
    |----------------------------------------------------------------------
    | Submissions
    |----------------------------------------------------------------------
    */

    Route::prefix('assignments/{assignment}/submission')->name('submissions.')->group(function () {
        Route::post('/', [SubmissionController::class, 'store'])->name('store');
        Route::post('/file', [SubmissionController::class, 'uploadFile'])->name('file.store');
        Route::delete('/file/{mediaAsset}', [SubmissionController::class, 'deleteFile'])->name('file.delete');
    });

});