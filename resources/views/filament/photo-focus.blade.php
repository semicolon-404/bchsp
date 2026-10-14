{{--
    The part of a person's photograph that shows in the round picture (PersonResource::fields, "photo_focus").
    The photograph is larger than the circle; the reader of the site sees the part chosen here. It is chosen by
    dragging the picture inside the circle, as on a phone's profile picture; the arrow keys move it too.
    What is kept is where the middle of the circle falls on the photograph, as two figures from 0 to 100
    ("50 18": the middle across, near the top, where a face usually is).

    It works on a photograph that has been saved: one only just chosen is not on the server yet.
--}}
@php($photo = $getRecord()?->photoUrl())
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if ($photo)
        <div
            x-data="{
                state: $wire.$entangle(@js($getStatePath())),
                held: null,
                // A square photograph fills the circle whole: there is nothing of it outside to drag in.
                whole: false,
                // Where it stands, kept finer than what is saved, so that a slow drag is not lost to rounding.
                x: 50,
                y: 18,
                init() {
                    const [x, y] = String(this.state || '50 18').split(' ').map(Number);
                    this.x = Number.isFinite(x) ? x : 50;
                    this.y = Number.isFinite(y) ? y : 18;
                },
                move(dx, dy) {
                    this.x = Math.min(100, Math.max(0, this.x - dx));
                    this.y = Math.min(100, Math.max(0, this.y - dy));
                    this.state = Math.round(this.x) + ' ' + Math.round(this.y);
                },
                reset() {
                    this.x = 50;
                    this.y = 18;
                    this.state = '50 18';
                },
                // The picture follows the pointer exactly: how far it can slide each way is how much of it lies outside the circle.
                drag(event) {
                    if (! this.held) return;
                    const size = this.$refs.circle.clientWidth;
                    const picture = this.$refs.picture;
                    const scale = Math.max(size / picture.naturalWidth, size / picture.naturalHeight);
                    const [spareX, spareY] = [picture.naturalWidth * scale - size, picture.naturalHeight * scale - size];
                    this.move(spareX > 1 ? (event.clientX - this.held.x) / spareX * 100 : 0, spareY > 1 ? (event.clientY - this.held.y) / spareY * 100 : 0);
                    this.held = { x: event.clientX, y: event.clientY };
                },
            }"
            style="display: flex; flex-wrap: wrap; align-items: center; gap: 1.25rem;"
        >
            <div
                x-ref="circle" tabindex="0" role="img" aria-label="{{ __('The part of the photograph that is shown') }}"
                x-on:pointerdown.prevent="held = { x: $event.clientX, y: $event.clientY }; $el.setPointerCapture($event.pointerId)"
                x-on:pointermove="drag($event)" x-on:pointerup="held = null" x-on:pointercancel="held = null"
                x-on:keydown.left.prevent="move(2, 0)" x-on:keydown.right.prevent="move(-2, 0)"
                x-on:keydown.up.prevent="move(0, 2)" x-on:keydown.down.prevent="move(0, -2)"
                style="width: 11rem; height: 11rem; flex: none; overflow: hidden; border-radius: 9999px; border: 3px solid var(--primary-500); cursor: grab; touch-action: none; user-select: none;"
                x-bind:style="{ cursor: held ? 'grabbing' : 'grab' }"
            >
                <img x-ref="picture" src="{{ $photo }}" alt="" draggable="false" x-on:load="whole = Math.abs($el.naturalWidth - $el.naturalHeight) < 3" x-init="$el.complete && $el.dispatchEvent(new Event('load'))" style="width: 100%; height: 100%; object-fit: cover; pointer-events: none;" x-bind:style="{ objectPosition: x + '% ' + y + '%' }">
            </div>
            <div style="min-width: 12rem; flex: 1; font-size: 0.875rem; line-height: 1.6;">
                <p x-show="whole" x-cloak style="font-weight: 600;">{{ __('This photograph is square, so all of it already shows. To choose a part, upload the whole photograph, not one already cut square.') }}</p>
                <p x-show="! whole" style="font-weight: 600;">{{ __('Drag the picture until the face is in the circle.') }}</p>
                <p style="opacity: 0.75;">{{ __('This is what visitors see. The arrow keys move it a little at a time. Then press Save.') }}</p>
                <button type="button" x-on:click="reset()" style="margin-top: 0.5rem; text-decoration: underline; color: var(--primary-600);">{{ __('Back to where it was') }}</button>
            </div>
        </div>
    @else
        <p style="font-size: 0.875rem; opacity: 0.75;">{{ __('Save the photograph first; then open this person again to drag the face into the circle.') }}</p>
    @endif
</x-dynamic-component>
