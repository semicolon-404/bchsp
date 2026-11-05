<?php

namespace App\Http\Controllers;

use App\Models\Festival;
use App\Models\Krathong;
use App\Support\RudeWords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * /loykrathong: the river at night, every krathong floated on it this festival, and a form for a visitor to float
 * their own under a name of their choosing (no account). It goes on the river at once; rude words and links are
 * turned away (App\Support\RudeWords), a few to a visitor (the route's throttle and their session), and an
 * administrator can take one off afterwards (Festivals > Float a krathong).
 */
class KrathongController extends Controller
{
    /** How many one visitor can float in a festival. */
    private const PER_VISITOR = 3;

    public function index(Request $request): View
    {
        $festival = $this->festival();

        return view('loykrathong', [
            'festival' => $festival,
            'krathongs' => $festival->krathongs()->where('is_hidden', false)->with('department')->limit(40)->get(),
            'floated' => $festival->krathongs()->where('is_hidden', false)->count(),
            'mine' => $request->session()->get('krathong.floated'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $festival = $this->festival();

        // A field no person sees or fills: whatever fills it is not a person, and nothing is kept.
        if (filled($request->input('website'))) {
            return redirect()->route('loykrathong');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'wish' => ['required', 'string', 'max:140'],
            'style' => ['required', Rule::in(array_keys(Krathong::STYLES))],
        ]);

        if (RudeWords::in($data['name'].' '.$data['wish'])) {
            throw ValidationException::withMessages(['wish' => __('Please float your krathong with kind words.')]);
        }

        $count = (int) $request->session()->get('krathong.count.'.$festival->id, 0);

        if ($count >= self::PER_VISITOR) {
            throw ValidationException::withMessages(['wish' => __('You have floated :count krathongs this festival. Thank you!', ['count' => self::PER_VISITOR])]);
        }

        $krathong = $festival->krathongs()->create([
            'name' => trim(strip_tags($data['name'])),
            'wish' => trim(strip_tags($data['wish'])),
            'style' => $data['style'],
        ]);

        $request->session()->put('krathong.count.'.$festival->id, $count + 1);

        return redirect()->route('loykrathong')->with('krathong.floated', $krathong->id);
    }

    /** The Loy Krathong festival on today; none, and there is no river. */
    private function festival(): Festival
    {
        $festival = Festival::today();
        abort_unless($festival?->theme === 'loykrathong', 404);

        return $festival;
    }
}
