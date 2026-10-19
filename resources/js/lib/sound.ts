import { ref } from 'vue';

/*
 * Sound effects for the book, played through Web Audio so they overlap,
 * start instantly and can be pitched a touch differently every time (two
 * page turns in a row should never sound identical).
 *
 * Nothing is fetched until the first sound is asked for, which always
 * happens inside a click — the moment browsers allow audio to start.
 */

const SOUNDS = {
    flip: ['/sounds/page-flip.mp3', '/sounds/page-flip-2.mp3'],
    coin: ['/sounds/coin.mp3'],
    sparkle: ['/sounds/sparkle.mp3'],
    pop: ['/sounds/pop.mp3'],
    meow: ['/sounds/meow.mp3'],
    woof: ['/sounds/woof.mp3'],
    dragon: ['/sounds/dragon.mp3'],
    bookOpen: ['/sounds/book-open.mp3'],
    whoosh: ['/sounds/whoosh.mp3'],
    unlock: ['/sounds/unlock.mp3'],
} as const;

export type SoundName = keyof typeof SOUNDS;

const DEFAULT_VOLUME: Record<SoundName, number> = {
    flip: 0.55,
    coin: 0.45,
    sparkle: 0.3,
    pop: 0.22,
    meow: 0.5,
    woof: 0.4,
    dragon: 0.45,
    bookOpen: 0.5,
    whoosh: 0.25,
    unlock: 0.45,
};

const STORAGE_KEY = 'bajka:sound';

function readEnabled() {
    try {
        return localStorage.getItem(STORAGE_KEY) !== 'off';
    } catch {
        return true;
    }
}

export const soundEnabled = ref(typeof window === 'undefined' ? false : readEnabled());

let ctx: AudioContext | null = null;
let master: GainNode | null = null;
const buffers = new Map<string, Promise<AudioBuffer | null>>();

function context() {
    if (!ctx) {
        const Ctor = window.AudioContext ?? (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
        if (!Ctor) return null;
        ctx = new Ctor();
        master = ctx.createGain();
        master.gain.value = soundEnabled.value ? 1 : 0;
        master.connect(ctx.destination);
    }
    if (ctx.state === 'suspended') void ctx.resume();
    return ctx;
}

function load(url: string) {
    let pending = buffers.get(url);
    if (!pending) {
        pending = fetch(url)
            .then((r) => r.arrayBuffer())
            .then((data) => context()!.decodeAudioData(data))
            .catch(() => null);
        buffers.set(url, pending);
    }
    return pending;
}

export function preload(...names: SoundName[]) {
    if (!context()) return;
    for (const name of names) SOUNDS[name].forEach((url) => void load(url));
}

interface PlayOptions {
    volume?: number;
    /** Playback rate; 1 is natural pitch. */
    rate?: number;
    /** Random pitch spread, as a fraction (0.06 = ±6%). */
    vary?: number;
}

export async function play(name: SoundName, options: PlayOptions = {}) {
    if (!soundEnabled.value || typeof window === 'undefined') return;
    const audio = context();
    if (!audio || !master) return;

    const variants = SOUNDS[name];
    const buffer = await load(variants[Math.floor(Math.random() * variants.length)]);
    if (!buffer) return;

    const source = audio.createBufferSource();
    source.buffer = buffer;
    const vary = options.vary ?? 0.05;
    source.playbackRate.value = (options.rate ?? 1) * (1 + (Math.random() * 2 - 1) * vary);

    const gain = audio.createGain();
    gain.gain.value = options.volume ?? DEFAULT_VOLUME[name];
    source.connect(gain).connect(master);
    source.start();
}

/* ── Ambience ────────────────────────────────────────────────────────────
   Soundscapes that follow the story: rain at the fort, birds in the park,
   the clock in Olga's room. One scene plays at a time; moving to another
   crossfades over a few seconds so nothing ever cuts. */

export const SCENES = {
    fireplace: { label: 'kominek', gain: 0.85 },
    rain: { label: 'deszcz', gain: 1.3 },
    wind: { label: 'wiatr', gain: 0.22 },
    night: { label: 'noc', gain: 1.3 },
    meadow: { label: 'łąka', gain: 1.4 },
    woods: { label: 'las', gain: 1.2 },
    home: { label: 'pokój Olgi', gain: 1.05 },
    city: { label: 'miasto', gain: 0.75 },
    nightcity: { label: 'miasto nocą', gain: 0.38 },
    park: { label: 'park', gain: 0.48 },
    magic: { label: 'czary', gain: 1.4 },
    mall: { label: 'centrum handlowe', gain: 0.85 },
    cafe: { label: 'herbaciarnia', gain: 1.5 },
} as const;

export type Scene = keyof typeof SCENES;

const AMBIENCE_VOLUME = 0.32;
const FADE_IN = 3;
const FADE_OUT = 2.5;

export const ambienceScene = ref<Scene | null>(null);
let bed: { scene: Scene; source: AudioBufferSourceNode; gain: GainNode } | null = null;
let ducked = false;
let request = 0;

const levelOf = (scene: Scene) => AMBIENCE_VOLUME * SCENES[scene].gain * (ducked ? 0.55 : 1);

function fadeOut(old: NonNullable<typeof bed>) {
    if (!ctx) return;
    const now = ctx.currentTime;
    old.gain.gain.cancelScheduledValues(now);
    old.gain.gain.setValueAtTime(old.gain.gain.value, now);
    old.gain.gain.linearRampToValueAtTime(0, now + FADE_OUT);
    old.source.stop(now + FADE_OUT + 0.1);
}

/** Play `scene` (crossfading from whatever plays now), or fade to silence with null. */
export async function setAmbience(scene: Scene | null) {
    if (bed?.scene === scene) return;
    const ticket = ++request;
    ambienceScene.value = scene;

    if (!scene) {
        if (bed) fadeOut(bed);
        bed = null;
        return;
    }

    const audio = context();
    if (!audio || !master) return;
    const buffer = await load(`/sounds/ambience/${scene}.mp3`);
    if (!buffer || ticket !== request) return; // a newer page asked for something else meanwhile

    if (bed) fadeOut(bed);

    const source = audio.createBufferSource();
    source.buffer = buffer;
    source.loop = true;
    // MP3 frames pad both ends with a hair of silence; loop inside them.
    source.loopStart = 0.06;
    source.loopEnd = buffer.duration - 0.06;

    const gain = audio.createGain();
    gain.gain.setValueAtTime(0, audio.currentTime);
    gain.gain.linearRampToValueAtTime(levelOf(scene), audio.currentTime + FADE_IN);
    source.connect(gain).connect(master);
    // Start somewhere inside the loop so the same scene never sounds "restarted".
    source.start(0, 0.06 + Math.random() * (buffer.duration - 0.2));
    bed = { scene, source, gain };
}

/** Lower the soundscape while the narrator reads, so the voice stays clear. */
export function duckAmbience(on: boolean) {
    ducked = on;
    if (!bed || !ctx) return;
    const now = ctx.currentTime;
    bed.gain.gain.cancelScheduledValues(now);
    bed.gain.gain.setValueAtTime(bed.gain.gain.value, now);
    bed.gain.gain.linearRampToValueAtTime(levelOf(bed.scene), now + 1);
}

export function preloadAmbience(scene: Scene | null | undefined) {
    if (scene && context()) void load(`/sounds/ambience/${scene}.mp3`);
}

export function setSoundEnabled(on: boolean) {
    soundEnabled.value = on;
    try {
        localStorage.setItem(STORAGE_KEY, on ? 'on' : 'off');
    } catch {
        /* private mode: the toggle still works for this visit */
    }
    // Ambience runs through the master bus, so muting fades it too.
    if (master && ctx) {
        master.gain.cancelScheduledValues(ctx.currentTime);
        master.gain.setValueAtTime(master.gain.value, ctx.currentTime);
        master.gain.linearRampToValueAtTime(on ? 1 : 0, ctx.currentTime + 0.4);
    }
}
