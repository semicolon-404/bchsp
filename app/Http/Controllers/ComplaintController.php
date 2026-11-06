<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\WorkGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Complaints, suggestions and praise: the form a visitor sends one by, the page that gives them the tracking
 * number and code, and the page where they read how it stands (App\Models\Complaint).
 *
 * The one part of the site where a visitor writes about themselves, so: the form asks leave to keep what is
 * written, a robot that fills the field no person sees is turned away, the number of matters sent and of codes
 * tried from one address is limited, and a wrong code is answered the same as a number that does not exist.
 */
class ComplaintController extends Controller
{
    /** Wrong codes allowed for one tracking number before it must wait. */
    private const TRIES = 5;

    private const WAIT_MINUTES = 15;

    /** /complaint/send: the form. */
    public function create(): View
    {
        return view('complaint.send', ['groups' => WorkGroup::shown()->get()]);
    }

    /** The form, sent. */
    public function store(Request $request): RedirectResponse
    {
        // A field no person sees or fills: whatever fills it is not a person. It is told the same as anyone, and nothing is kept.
        if (filled($request->input('website'))) {
            return redirect()->route('complaint');
        }

        $named = $request->input('who') !== 'anonymous';

        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(Complaint::KINDS))],
            'title' => ['required', 'string', 'max:255'],
            'detail' => ['required', 'string', 'max:5000'],
            'happened_on' => ['nullable', 'date', 'before_or_equal:today'],
            'place' => ['nullable', 'string', 'max:255'],
            'work_group_id' => ['nullable', Rule::exists('work_groups', 'id')->where('is_active', true)],
            'who' => ['required', Rule::in(['named', 'anonymous'])],
            'name' => [Rule::requiredIf($named), 'nullable', 'string', 'max:150'],
            // To be called back, one way to reach them is enough.
            'phone' => [Rule::requiredIf($named && blank($request->input('email'))), 'nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'line_id' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'files' => ['nullable', 'array', 'max:'.Complaint::MAX_FILES],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.Complaint::MAX_FILE_KB],
            'consent' => ['accepted'],
        ], [], [
            'kind' => __('Kind of matter'), 'title' => __('Subject'), 'detail' => __('What happened'), 'happened_on' => __('Day it happened'),
            'place' => __('Where it happened'), 'name' => __('Full name'), 'phone' => __('Phone number'), 'email' => __('Email'),
            'files' => __('Proof'), 'files.*' => __('Proof'), 'consent' => __('Consent'),
        ]);

        $pin = Complaint::newPin();

        $complaint = Complaint::create([
            'code' => Complaint::newCode(),
            'pin_hash' => Hash::make($pin),
            'kind' => $data['kind'],
            'title' => $data['title'],
            'detail' => $data['detail'],
            'happened_on' => $data['happened_on'] ?? null,
            'place' => $data['place'] ?? null,
            'work_group_id' => $data['work_group_id'] ?? null,
            'is_anonymous' => ! $named,
            // Unnamed means unnamed: whatever was typed before the choice was changed is not kept.
            'name' => $named ? ($data['name'] ?? null) : null,
            'phone' => $named ? ($data['phone'] ?? null) : null,
            'email' => $named ? ($data['email'] ?? null) : null,
            'line_id' => $named ? ($data['line_id'] ?? null) : null,
            'address' => $named ? ($data['address'] ?? null) : null,
        ]);

        // Off the public disk, under names nobody can guess; the name each came under is kept beside it.
        $files = [];

        foreach ($request->file('files', []) as $file) {
            $files[] = ['name' => mb_substr($file->getClientOriginalName(), 0, 150), 'path' => $file->store('complaints/'.$complaint->id, Complaint::DISK)];
        }

        if ($files) {
            $complaint->update(['attachments' => $files]);
        }

        // Shown once, on the next page, and kept nowhere it could be read again.
        return redirect()->route('complaint.sent')->with('complaint', ['code' => $complaint->code, 'pin' => $pin]);
    }

    /** /complaint/sent: the tracking number and the code, once. */
    public function sent(Request $request): View|RedirectResponse
    {
        $given = $request->session()->get('complaint');

        return is_array($given) ? view('complaint.sent', $given) : redirect()->route('complaint');
    }

    /** /complaint/track: the two boxes; and, looked up, how the matter stands. */
    public function track(): View
    {
        return view('complaint.track', ['complaint' => null]);
    }

    public function lookup(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
            'pin' => ['required', 'digits:6'],
        ], [], ['code' => __('Tracking number'), 'pin' => __('Code')]);

        $code = Complaint::tidyCode($data['code']);
        $key = 'complaint-track:'.sha1($code);

        if (RateLimiter::tooManyAttempts($key, self::TRIES)) {
            return back()->withInput($request->only('code'))->withErrors(['code' => __('Too many wrong tries for this tracking number. Please wait :minutes minutes and try again.', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)])]);
        }

        $complaint = Complaint::where('code', $code)->first();

        // A number that does not exist and a wrong code are answered alike: neither says which numbers are real.
        if (! $complaint || ! $complaint->pinIs($data['pin'])) {
            RateLimiter::hit($key, self::WAIT_MINUTES * 60);

            return back()->withInput($request->only('code'))->withErrors(['code' => __('The tracking number or the code is not right.')]);
        }

        RateLimiter::clear($key);

        return view('complaint.track', ['complaint' => $complaint->load('workGroup')]);
    }

    /** A file attached to a matter, in the panel, for an administrator or the department it is about; nobody else. */
    public function file(Complaint $complaint, int $index): StreamedResponse
    {
        abort_unless($complaint->isVisibleTo(auth()->user()), 403);

        $file = $complaint->files()[$index] ?? null;
        abort_unless($file && Storage::disk(Complaint::DISK)->exists($file['path']), 404);

        return Storage::disk(Complaint::DISK)->download($file['path'], $file['name']);
    }
}
