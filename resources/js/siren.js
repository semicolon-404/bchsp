/**
 * The ambulance's siren on the home page (components/site/ambulance): off until the visitor presses its button, then
 * a short wail each time the van comes by, made in the browser (no sound file to fetch). The choice is remembered.
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
        // Two rises and falls, as a Thai ambulance's siren, in two and a half seconds.
        tone.frequency.setValueAtTime(650, now);
        tone.frequency.linearRampToValueAtTime(1250, now + 0.6);
        tone.frequency.linearRampToValueAtTime(650, now + 1.2);
        tone.frequency.linearRampToValueAtTime(1250, now + 1.8);
        tone.frequency.linearRampToValueAtTime(650, now + 2.4);
        volume.gain.setValueAtTime(0, now);
        volume.gain.linearRampToValueAtTime(0.08, now + 0.15);
        volume.gain.setValueAtTime(0.08, now + 2.1);
        volume.gain.linearRampToValueAtTime(0, now + 2.5);
        tone.connect(volume).connect(audio.destination);
        tone.start(now);
        tone.stop(now + 2.5);
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

    // Each time the van starts across again.
    van.addEventListener('animationiteration', (event) => {
        if (event.animationName === 'amb-drive') wail();
    });

    // Remembered, but a browser plays nothing before the visitor has pressed something on the page.
    try {
        if (localStorage.getItem('siren') === 'on') {
            set(true);
            document.addEventListener('pointerdown', () => audio?.resume?.(), { once: true });
        }
    } catch {}
}
