<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialProgressController;
use App\Http\Controllers\QuestionBankController;
use App\Models\QuestionBank;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizQuestionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware(['auth', 'course.enrolled'])->group(function () {
    Route::resource('courses', CourseController::class)
        ->parameters([
            'courses' => 'id',
        ]);
    Route::get(
        '/courses/{id}/participants',
        [CourseController::class, 'indexParticipants']
    )->name('courses.participants.index');

    Route::post(
        '/courses/{id}/participants',
        [CourseController::class, 'addParticipant']
    )->name('courses.participants.store');

    Route::put(
        '/courses/{id}/participants/{participantId}',
        [CourseController::class, 'updateParticipantRole']
    )->name('courses.participants.update');

    Route::delete(
        '/courses/{id}/participants/{participantId}',
        [CourseController::class, 'removeParticipant']
    )->name('courses.participants.destroy');

    Route::post('/materials/{material}/progress', [MaterialProgressController::class, 'update'])
        ->name('materials.progress.update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [UserController::class, 'profileEdit'])->name('profile.edit');
    Route::put('/profile/{id}', [UserController::class, 'profileUpdate'])->name('profile.update');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');

    Route::post(
        '/sections/reorder',
        [SectionController::class, 'reorder']
    )
        ->name('sections.reorder');
    Route::resource('sections', SectionController::class);

    Route::resource('materials', MaterialController::class);
    Route::get('/materials/create/{section}/', [MaterialController::class, 'create'])
        ->name('materials.create');
    Route::get('/media/{asset}/download', [MaterialController::class, 'download'])
        ->name('media.download');
    Route::resource('questionbank', QuestionBankController::class)->names([
        'index' => 'questionbank.index',
        'show' => 'questionbank.show',
    ]);
    Route::post(
        '/questionbank/{questionBank}/questions/reorder',
        [QuestionBankController::class, 'reorder']
    )->name('questionbank.questions.reorder');
    Route::resource('questions', QuestionController::class);

    Route::prefix('quizzes/{quiz}/questions')->name('quiz-questions.')->group(function () {
        Route::get('/', [QuizQuestionController::class, 'index'])->name('index');
        Route::get('/available', [QuizQuestionController::class, 'available'])->name('available');
        Route::post('/', [QuizQuestionController::class, 'store'])->name('store');
        Route::post('/random', [QuizQuestionController::class, 'storeRandom'])->name('random');
        Route::delete('/{question}', [QuizQuestionController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [QuizQuestionController::class, 'reorder'])->name('reorder');
        Route::patch('/{question}/point-override', [QuizQuestionController::class, 'updatePointOverride'])->name('point-override');
    });
});
