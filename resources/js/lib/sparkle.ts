/*
 * Little bursts of magic: stars that pop out of a button, and coins that fly
 * from where they were spent (or earned) into the purse in the header.
 */

const COLORS = ['#f2b84b', '#fbe3a4', '#ec8fae', '#9b8ad6', '#7cc4a4', '#ffffff'];

const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function burst(x: number, y: number, { count = 14, spread = 90, colors = COLORS } = {}) {
    if (reduced()) return;
    const host = document.createElement('div');
    host.className = 'burst';
    host.style.left = `${x}px`;
    host.style.top = `${y}px`;

    for (let i = 0; i < count; i++) {
        const angle = (Math.PI * 2 * i) / count + Math.random() * 0.6;
        const distance = spread * (0.45 + Math.random() * 0.75);
        const piece = document.createElement('i');
        if (Math.random() < 0.35) piece.className = 'dot';
        piece.style.setProperty('--x', `${Math.cos(angle) * distance}px`);
        piece.style.setProperty('--y', `${Math.sin(angle) * distance}px`);
        piece.style.setProperty('--s', `${6 + Math.random() * 10}px`);
        piece.style.setProperty('--r', `${Math.random() * 360}deg`);
        piece.style.setProperty('--c', colors[i % colors.length]);
        host.appendChild(piece);
    }

    document.body.appendChild(host);
    setTimeout(() => host.remove(), 1000);
}

export function burstFrom(el: Element | null | undefined, options?: Parameters<typeof burst>[2]) {
    if (!el) return;
    const r = el.getBoundingClientRect();
    burst(r.left + r.width / 2, r.top + r.height / 2, options);
}

const COIN_SVG = `<svg viewBox="0 0 32 32" width="28" height="28"><circle cx="16" cy="16" r="15" fill="#c98a1d"/><circle cx="16" cy="15" r="13" fill="#f2b84b"/><circle cx="16" cy="15" r="9.5" fill="none" stroke="#c98a1d" stroke-width="1.5"/><path d="M16 9.5l1.7 3.6 3.9.5-2.9 2.7.8 3.9-3.5-2-3.5 2 .8-3.9-2.9-2.7 3.9-.5z" fill="#fff6dc"/></svg>`;

/** Coins arc from one element to another (defaults to the header purse). */
export function coinsFly(from: Element | null | undefined, to?: Element | null, count = 5) {
    if (!from || reduced()) return;
    const target = to ?? document.querySelector('[data-purse]');
    if (!target) return;
    const a = from.getBoundingClientRect();
    const b = target.getBoundingClientRect();

    for (let i = 0; i < count; i++) {
        const coin = document.createElement('div');
        coin.className = 'coin-fly';
        coin.innerHTML = COIN_SVG;
        const sx = a.left + a.width / 2 + (Math.random() - 0.5) * 30;
        const sy = a.top + a.height / 2 + (Math.random() - 0.5) * 10;
        coin.style.left = `${sx}px`;
        coin.style.top = `${sy}px`;
        coin.style.setProperty('--x', `${b.left + b.width / 2 - sx}px`);
        coin.style.setProperty('--y', `${b.top + b.height / 2 - sy}px`);
        coin.style.setProperty('--delay', `${i * 70}ms`);
        document.body.appendChild(coin);
        setTimeout(() => coin.remove(), 900 + i * 70);
    }
}
