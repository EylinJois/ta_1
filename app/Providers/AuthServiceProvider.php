<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Attempt;
use App\Models\Course;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\QuestionBank;
use App\Models\Section;
use App\Models\Submission;
use App\Policies\AssignmentPolicy;
use App\Policies\AttemptPolicy;
use App\Policies\CoursePolicy;
use App\Policies\MaterialPolicy;
use App\Policies\QuizPolicy;
use App\Policies\QuestionBankPolicy;
use App\Policies\SectionPolicy;
use App\Policies\SubmissionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Section::class => SectionPolicy::class,
        Course::class => CoursePolicy::class,
        Material::class => MaterialPolicy::class,
        Quiz::class => QuizPolicy::class,
        QuestionBank::class => QuestionBankPolicy::class,
        Attempt::class => AttemptPolicy::class,
        Assignment::class => AssignmentPolicy::class,
        Submission::class => SubmissionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
