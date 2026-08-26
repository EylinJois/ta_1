<?php

namespace App\Http\Controllers;

use App\Models\CertificationType;
use App\Models\Course;
use App\Models\User;
use App\Models\UserCertification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\MediaAsset;
use App\Models\UserCertificationFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificationController extends Controller
{
    public function index()
    {
        $certifications = UserCertification::with(['certificationType', 'files.mediaAsset'])
            ->where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('certificates.index', compact('certifications'));
    }

    public function show(UserCertification $userCertification)
    {
        $user = auth()->user();

        // Owner can always view their own; otherwise must be an admin, or a
        // teacher of a course the certificate holder is enrolled in.
        $isOwner = $userCertification->user_id === $user->id;
        $isAdminAnywhere = $user->isAdmin();

        abort_unless($isOwner || $isAdminAnywhere || $this->teacherCanView($user, $userCertification), 403);

        $userCertification->load(['certificationType', 'files.mediaAsset', 'user', 'verifier']);

        return view('certificates.show', compact('userCertification'));
    }

    private function teacherCanView($user, UserCertification $cert): bool
    {
        return DB::table('course_user_role')
            ->join('course_user_role as teacher_role', 'teacher_role.course_id', '=', 'course_user_role.course_id')
            ->where('course_user_role.user_id', $cert->user_id)
            ->where('course_user_role.role', 'Student')
            ->where('teacher_role.user_id', $user->id)
            ->where('teacher_role.role', 'Teacher')
            ->exists();
    }

    /**
     * Admin-only: create a certificate for a student using the fixed
     * certificate design/template.
     */
    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $students = User::whereHas('roles', fn($q) => $q->where('role', 'student'))->get();
        $certificationTypes = CertificationType::all();

        return view('certificates.create', compact('students', 'certificationTypes'));
    }



    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'certification_type_id' => 'required|exists:certification_types,id',
            'certificate_num' => 'nullable|string|max:255',
            'score' => 'nullable|numeric|min:0',
            'expired_at' => 'nullable|date|after:today',
        ]);

        $userCertification = DB::transaction(function () use ($validated) {

            $userCertification = UserCertification::create(array_merge($validated, [
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => auth()->id(),
            ]));

            $userCertification->load(['user', 'certificationType']);

            // Generate the certificate PDF from the same design used on-screen,
            // then file it automatically as a supporting document.
            $pdf = Pdf::loadView('certificates.pdf', compact('userCertification'))
                ->setPaper('a4', 'landscape');

            $filename = 'certificate-' . Str::slug($userCertification->user->name) . '-' . $userCertification->id . '.pdf';
            $path = 'certificates/' . $filename;

            Storage::disk('public')->put($path, $pdf->output());

            $mediaAsset = MediaAsset::create([
                'file_path' => $path,
                'original_name' => $filename,
                'uploaded_by' => auth()->id(),
            ]);

            UserCertificationFile::create([
                'user_certification_id' => $userCertification->id,
                'media_asset_id' => $mediaAsset->id,
            ]);

            return $userCertification;
        });

        return redirect()
            ->route('certificates.create')
            ->with('success', 'Certificate issued and PDF generated.');
    }


    public function courseIndex(Course $course)
    {
        $user = auth()->user();
        abort_unless(
            $user->isAdmin($course->id) || $user->isTeacher($course->id),
            403
        );

        $studentIds = $course->students()->pluck('users.id'); // was: $course->participants()->wherePivot(...)->pluck('users.id')

        $certifications = UserCertification::with(['certificationType', 'user'])
            ->whereIn('user_id', $studentIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id');

        return view('certificates.index-course', compact('course', 'certifications'));
    }
}
