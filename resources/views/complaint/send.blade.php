{{--
    /complaint/send: the form a complaint, a suggestion or a word of praise is sent by (ComplaintController::store).
    Four parts, numbered as the reader goes down: what kind it is, what happened, proof if there is any, and who
    is sending it, named or not. It works without scripts: the choices are plain radio buttons drawn as cards,
    and the fields for a name step aside by a style when "unnamed" is chosen.

    What is typed here is kept by the hospital to deal with the matter and for nothing else; the reader is told
    so, and must agree, before sending.
--}}
@php
    use App\Models\Complaint;
    use App\Support\SiteSettings;

    $emergency = SiteSettings::get('contact.emergency_phone');
    $icons = ['service' => 'face-frown', 'conduct' => 'user-minus', 'corruption' => 'shield-exclamation', 'suggestion' => 'light-bulb', 'praise' => 'hand-thumb-up', 'other' => 'ellipsis-horizontal-circle'];
    $privacy = App\Models\SitePage::published()->where('slug', 'privacy')->first();
@endphp
<x-layouts.site :title="__('Send a matter')" :description="__('Send a complaint, a suggestion or praise to :name.', ['name' => SiteSettings::name()])">
    <x-site.page-header :eyebrow="__('Complaints and suggestions')" :title="__('Send a matter')" icon="pencil-square" width="max-w-3xl"
                        :lead="__('About 3 to 5 minutes. No account needed. What you write is kept in confidence.')">
        <p class="text-sm"><a href="{{ route('complaint') }}" class="font-semibold text-brand underline">&larr; {{ __('Complaints and suggestions') }}</a></p>
    </x-site.page-header>

    <div class="wrap max-w-3xl py-10 sm:py-14">
        @if ($emergency)
            <p class="mb-8 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg border border-danger px-4 py-3">
                <x-heroicon-m-exclamation-triangle class="size-5 shrink-0 text-danger" aria-hidden="true" />
                <span class="min-w-0 flex-1">{{ __('For an emergency or sudden illness, do not use a form: call.') }}</span>
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $emergency) }}" class="btn btn-danger"><x-heroicon-s-phone class="size-4" aria-hidden="true" />{{ $emergency }}</a>
            </p>
        @endif

        @if ($errors->any())
            {{-- What is wrong, said together at the top and again beside each field. --}}
            <div class="mb-8 rounded-lg border border-danger px-4 py-3 text-danger" role="alert" tabindex="-1" id="form-errors">
                <p class="font-semibold">{{ __('Please look at these before sending:') }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('complaint.store') }}" enctype="multipart/form-data" class="form space-y-10" novalidate>
            @csrf
            {{-- Not for people: a robot that fills every field fills this one, and is turned away. --}}
            <div class="sr-only" aria-hidden="true">
                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>
            </div>

            <fieldset>
                <legend class="form-part"><span aria-hidden="true">1</span>{{ __('What kind of matter is it?') }} <b class="text-danger">*</b></legend>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach (Complaint::KINDS as $key => $label)
                        <label class="choice">
                            <input type="radio" name="kind" value="{{ $key }}" @checked(old('kind') === $key) required>
                            <span class="door-mark" aria-hidden="true"><x-dynamic-component :component="'heroicon-o-'.$icons[$key]" class="size-5" /></span>
                            <span class="font-medium">{{ __($label) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('kind')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>

            <fieldset>
                <legend class="form-part"><span aria-hidden="true">2</span>{{ __('What happened') }}</legend>
                <div class="mt-4 space-y-5">
                    <div>
                        <label for="c-title" class="field-label">{{ __('Subject') }} <b class="text-danger">*</b></label>
                        <input id="c-title" name="title" type="text" maxlength="255" value="{{ old('title') }}" required class="field" placeholder="{{ __('In a few words, such as: waited over two hours for medicine') }}" @error('title') aria-invalid="true" @enderror>
                        @error('title')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="c-detail" class="field-label">{{ __('What happened') }} <b class="text-danger">*</b></label>
                        <textarea id="c-detail" name="detail" rows="7" maxlength="5000" required class="field" placeholder="{{ __('Tell it in order: who did what, where and when.') }}" @error('detail') aria-invalid="true" @enderror>{{ old('detail') }}</textarea>
                        <p class="field-hint">{{ __('Up to 5,000 characters.') }}</p>
                        @error('detail')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="c-day" class="field-label">{{ __('Day it happened') }}</label>
                            <input id="c-day" name="happened_on" type="date" max="{{ today()->toDateString() }}" value="{{ old('happened_on') }}" class="field">
                            <p class="field-hint">{{ __('If you know it.') }}</p>
                            @error('happened_on')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="c-place" class="field-label">{{ __('Where it happened') }}</label>
                            <input id="c-place" name="place" type="text" maxlength="255" value="{{ old('place') }}" class="field" placeholder="{{ __('Such as: the pharmacy counter') }}">
                            @error('place')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    @if ($groups->isNotEmpty())
                        <div>
                            <label for="c-group" class="field-label">{{ __('Group of work it is about') }}</label>
                            <select id="c-group" name="work_group_id" class="field">
                                <option value="">{{ __('Not known / not said') }}</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" @selected((string) old('work_group_id') === (string) $group->id)>{{ $group->localName() }}</option>
                                @endforeach
                            </select>
                            @error('work_group_id')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>
            </fieldset>

            <fieldset>
                <legend class="form-part"><span aria-hidden="true">3</span>{{ __('Proof, if there is any') }}</legend>
                <div class="mt-4">
                    <label for="c-files" class="field-label">{{ __('Pictures or documents') }}</label>
                    <input id="c-files" name="files[]" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" class="field field-file">
                    <p class="field-hint">{{ __('JPG, PNG, WEBP or PDF, up to :size MB each, :count files at most.', ['size' => Complaint::MAX_FILE_KB / 1024, 'count' => Complaint::MAX_FILES]) }}</p>
                    @error('files')<p class="field-error">{{ $message }}</p>@enderror
                    @error('files.*')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </fieldset>

            <fieldset class="who">
                <legend class="form-part"><span aria-hidden="true">4</span>{{ __('Who is sending it') }}</legend>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <label class="choice choice-tall">
                        <input type="radio" name="who" value="named" @checked(old('who', 'named') === 'named')>
                        <span class="door-mark" aria-hidden="true"><x-heroicon-o-user class="size-5" /></span>
                        <span><span class="block font-medium">{{ __('Give my name') }}</span><span class="block text-sm text-muted">{{ __('Staff can call back to ask for more.') }}</span></span>
                    </label>
                    <label class="choice choice-tall">
                        <input type="radio" name="who" value="anonymous" @checked(old('who') === 'anonymous')>
                        <span class="door-mark" aria-hidden="true"><x-heroicon-o-eye-slash class="size-5" /></span>
                        <span><span class="block font-medium">{{ __('Stay unnamed') }}</span><span class="block text-sm text-muted">{{ __('You follow it with the tracking number and code.') }}</span></span>
                    </label>
                </div>

                {{-- Steps aside when "unnamed" is chosen (app.css, ".who"); whatever was typed in it is then not kept. --}}
                <div class="who-named mt-5 space-y-5">
                    <div>
                        <label for="c-name" class="field-label">{{ __('Full name') }} <b class="text-danger">*</b></label>
                        <input id="c-name" name="name" type="text" maxlength="150" autocomplete="name" value="{{ old('name') }}" class="field" @error('name') aria-invalid="true" @enderror>
                        @error('name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="c-phone" class="field-label">{{ __('Phone number') }}</label>
                            <input id="c-phone" name="phone" type="tel" inputmode="tel" maxlength="30" autocomplete="tel" value="{{ old('phone') }}" class="field" placeholder="08x-xxx-xxxx" @error('phone') aria-invalid="true" @enderror>
                            <p class="field-hint">{{ __('A phone number or an email: one at least.') }}</p>
                            @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="c-email" class="field-label">{{ __('Email') }}</label>
                            <input id="c-email" name="email" type="email" inputmode="email" maxlength="150" autocomplete="email" value="{{ old('email') }}" class="field" placeholder="name@example.com" @error('email') aria-invalid="true" @enderror>
                            @error('email')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="c-line" class="field-label">LINE ID</label>
                            <input id="c-line" name="line_id" type="text" maxlength="100" value="{{ old('line_id') }}" class="field">
                        </div>
                        <div>
                            <label for="c-address" class="field-label">{{ __('Address to write back to') }}</label>
                            <input id="c-address" name="address" type="text" maxlength="500" autocomplete="street-address" value="{{ old('address') }}" class="field">
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="card">
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="consent" value="1" @checked(old('consent')) required class="mt-1 size-5 shrink-0 accent-[var(--color-brand)]">
                    <span>
                        {{ __('I confirm that what I have written is true, and agree that the hospital keeps and uses it, with anything about me, only to look into and deal with this matter.') }}
                        @if ($privacy)
                            <a href="{{ route('pages.show', $privacy) }}" target="_blank" rel="noopener" class="font-semibold text-brand underline">{{ $privacy->localTitle() }}</a>
                        @endif
                        <b class="text-danger">*</b>
                    </span>
                </label>
                @error('consent')<p class="field-error">{{ $message }}</p>@enderror

                <div class="mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-line pt-5">
                    <p class="text-sm text-muted">{{ __('Fields marked * must be filled in.') }}</p>
                    <button type="submit" class="btn btn-brand min-h-12 px-8 text-base"><x-heroicon-m-paper-airplane class="size-5" aria-hidden="true" />{{ __('Send the matter') }}</button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.site>
