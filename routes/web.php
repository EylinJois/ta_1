<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttemptController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TTSController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\MaterialProgressController;
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

    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/profile', [UserController::class, 'profileEdit'])->name('profile.edit');
    Route::put('/profile/{id}', [UserController::class, 'profileUpdate'])->name('profile.update');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses/{id}/classes', [CourseController::class, 'storeClass'])
    ->name('courses.classes.store');
    Route::put('/courses/{id}/classes/{classId}', [CourseController::class, 'updateClass'])
    ->name('courses.classes.update');
    Route::delete('/courses/{id}/classes/{classId}', [CourseController::class, 'destroyClass'])
    ->name('courses.classes.destroy');

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
    Route::resource('materials', MaterialController::class)->except(['create']);
    Route::post('materials/vocab/pinyin-preview', [MaterialController::class, 'pinyinPreview'])->name('materials.vocab.pinyin-preview');

    /*
    |----------------------------------------------------------------------
    | Question Banks & Questions
    |----------------------------------------------------------------------
    */

    Route::resource('questionbank', QuestionBankController::class)->names([
        'index' => 'questionbank.index',
        'show' => 'questionbank.show',
        'store' => 'questionbank.store',
        'update' => 'questionbank.update',
        'destroy' => 'questionbank.destroy',
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
        Route::get('/grade', [AttemptController::class, 'grade'])->name('grade');
        Route::get('/review', [AttemptController::class, 'review'])->name('review');
        Route::post('/grade', [AttemptController::class, 'saveGrade'])->name('grade.save');
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


    Route::get('assignments/{assignment}/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('submissions/{submission}/edit', [SubmissionController::class, 'edit'])->name('submissions.edit');
    Route::put('submissions/{submission}', [SubmissionController::class, 'gradeUpdate'])->name('submissions.grade.update');
    Route::prefix('assignments/{assignment}/submission')->name('submissions.')->group(function () {
        Route::post('/', [SubmissionController::class, 'store'])->name('store');
        Route::post('/file', [SubmissionController::class, 'uploadFile'])->name('file.store');
        Route::delete('/file/{mediaAsset}', [SubmissionController::class, 'deleteFile'])->name('file.delete');
    });



    /*
    |----------------------------------------------------------------------
    | TTS
    |----------------------------------------------------------------------
    */
    Route::get('/tts', [TTSController::class, 'index'])->name('tts.index');
    Route::post('/tts/generate', [TTSController::class, 'generate'])->name('tts.generate');

    /*
    |----------------------------------------------------------------------
    | NOTES & FOLDERS
    |----------------------------------------------------------------------
    */
    Route::resource('notes', NoteController::class)->except(['update']);
    Route::put('/notes/{note}/update', [NoteController::class, 'update'])->name('notes.update');
    Route::patch('/notes/{note}/pin', [NoteController::class, 'pin'])->name('notes.pin');
    Route::get('/notes/{note}/edit-data', [NoteController::class, 'editData'])->name('notes.edit-data');

    /*
    |----------------------------------------------------------------------
    | CALENDAR
    |----------------------------------------------------------------------
    */
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('calendar/events', [CalendarController::class, 'store'])->name('calendar.events.store');
    Route::get('calendar/events/{activity}', [CalendarController::class, 'show'])->name('calendar.events.show');
    Route::put('calendar/events/{activity}', [CalendarController::class, 'update'])->name('calendar.events.update');
    Route::delete('calendar/events/{activity}', [CalendarController::class, 'destroy'])->name('calendar.events.destroy');
    Route::patch('calendar/events/{activity}/status', [CalendarController::class, 'updateStatus'])->name('calendar.events.status');
    Route::patch('/calendar/events/{activity}/cancel', [CalendarController::class, 'cancel'])
    ->name('calendar.events.cancel');

    /*
    |----------------------------------------------------------------------
    | REPORT
    |----------------------------------------------------------------------
    */
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::get('courses/{courseId}/progress/calculate/{studentId}', [ProgressController::class, 'calculate'])->name('progress.calculate');
    Route::get('courses/{courseId}/progress/{studentId}/{fromCourse?}', [ProgressController::class, 'courseIndex'])->name('progress.course');
    Route::post('courses/{course}/progress', [ProgressController::class, 'store'])->name('progress.store');
});
