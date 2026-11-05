/**
 * The ambulance's siren on the home page (components/site/ambulance): off until the visitor presses its button, then
 * a short whoop as the van drops over the beat in the line, made in the browser (no sound file to fetch). The choice is
 * remembered.
 */
export function startSiren() {
    const button = document.querySelector('[data-siren]');
    const van = document.querySelector('.ambulance');
    if (!van) return;
    const sound = Boolean(button && (window.AudioContext || window.webkitAudioContext));
    if (button && !sound) button.hidden = true;

    let audio = null;
    let on = false;

    const wail = () => {
        if (!on || !sound) return;
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
        button?.setAttribute('aria-pressed', String(value));
        try { localStorage.setItem('siren', value ? 'on' : 'off'); } catch {}
    };

    button?.addEventListener('click', () => {
        set(!on);
        wail();
    });

    // Each run: thirty seconds across, then a rest, every minute or every few (the administrator's choice); the whoop
    // as the van drops over the beat, halfway across (resources/css/app.css, amb-bump). The van is gone meanwhile.
    const screen = van.closest('.home-screen');
    const every = Number(document.querySelector('[data-ambulance-every]')?.dataset.ambulanceEvery || 60) * 1000;
    const RUN = 30000;
    const DROP = 0.516 * RUN;
    const drive = () => {
        if (document.hidden) return;
        screen.classList.remove('is-driving');
        void screen.offsetWidth;
        screen.classList.add('is-driving');
        setTimeout(wail, DROP);
        setTimeout(() => screen.classList.remove('is-driving'), RUN);
    };
    setTimeout(drive, 2500);
    setInterval(drive, Math.max(every, RUN + 5000));

    // Remembered, but a browser plays nothing before the visitor has pressed something on the page.
    try {
        if (localStorage.getItem('siren') === 'on') {
            set(true);
            document.addEventListener('pointerdown', () => audio?.resume?.(), { once: true });
        }
    } catch {}
}
