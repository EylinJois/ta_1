<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\MaterialController;

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

Route::middleware(['auth'])->group(function () {
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [UserController::class, 'profileEdit'])->name('profile.edit');
    Route::put('/profile/{id}', [UserController::class, 'profileUpdate'])->name('profile.update');

    Route::resource('courses', CourseController::class);
    
    Route::post('/sections/reorder',
    [SectionController::class, 'reorder'])
    ->name('sections.reorder');
    Route::resource('sections', SectionController::class);
    Route::resource('materials', MaterialController::class);
    

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

    


});
