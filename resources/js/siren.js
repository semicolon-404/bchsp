/**
 * The ambulance's siren on the home page (components/site/ambulance): off until the visitor presses its button, then
 * a short whoop as the van drops over the beat in the line, made in the browser (no sound file to fetch). The choice is
 * remembered.
 */
export function startSiren() {
    const button = document.querySelector('[data-siren]');
    const van = document.querySelector('.ambulance');
    if (!button || !van || !(window.AudioContext || window.webkitAudioContext)) {
        if (button) button.hidden = true;
        return;
    }

    let audio = null;
    let on = false;

    const wail = () => {
        if (!on) return;
        audio ??= new (window.AudioContext || window.webkitAudioContext)();
        const now = audio.currentTime;
        const tone = audio.createOscillator();
        const volume = audio.createGain();
        tone.type = 'triangle';
        // One rise and fall, under a second.
        tone.frequency.setValueAtTime(700, now);
        tone.frequency.linearRampToValueAtTime(1300, now + 0.35);
        tone.frequency.linearRampToValueAtTime(700, now + 0.8);
        volume.gain.setValueAtTime(0, now);
        volume.gain.linearRampToValueAtTime(0.08, now + 0.08);
        volume.gain.setValueAtTime(0.08, now + 0.65);
        volume.gain.linearRampToValueAtTime(0, now + 0.85);
        tone.connect(volume).connect(audio.destination);
        tone.start(now);
        tone.stop(now + 0.9);
    };

    const set = (value) => {
        on = value;
        button.setAttribute('aria-pressed', String(value));
        try { localStorage.setItem('siren', value ? 'on' : 'off'); } catch {}
    };

    button.addEventListener('click', () => {
        set(!on);
        wail();
    });

    // As the van drops over the beat: 34.4% into its thirty seconds (resources/css/app.css, amb-bump). One timer a
    // cycle, set from where the van is now, so it stays in step.
    const CYCLE = 30000;
    const DROP = 0.344 * CYCLE;
    const drive = van.getAnimations().find((a) => a.animationName === 'amb-drive');
    const next = () => {
        const at = Number(drive?.currentTime ?? 0) % CYCLE;
        setTimeout(() => { wail(); next(); }, ((DROP - at + CYCLE) % CYCLE) || CYCLE);
    };
    if (drive) next();

    // Remembered, but a browser plays nothing before the visitor has pressed something on the page.
    try {
        if (localStorage.getItem('siren') === 'on') {
            set(true);
            document.addEventListener('pointerdown', () => audio?.resume?.(), { once: true });
        }
    } catch {}
}
