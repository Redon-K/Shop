# Apex Fuel — Protein & Gym Shop

Lightweight static storefront prototype for a protein/gym shop. Built with plain HTML, CSS and JavaScript; no backend required for the demo.

## Features
- Category bars for Proteins, Pre-Workout, Vitamins and Supplements — each bar holds product cards and scrolls horizontally.
- Search box filters product cards client-side.
- Add to cart + favorites (persisted in localStorage).
- Mini-bar in the bottom-right to open sliding panels for Cart and Favorites.
- Simple checkout placeholder (no real payments).

## Files
- `Home.html` — page structure and category sections.
- `Home.css` — styles: responsive layout, product bars, mini-bar and sliding panels.
- `Home.js` — frontend logic: product render, search, cart/favorites, mini-bar panels.
- `Images/` — product and UI images.
- `README.md` — this file.

## Quick start
1. Open the project folder in your browser: open `Home.html`.
2. Or run a local static server (recommended for some browsers):
   - Python 3: `python3 -m http.server 8000` then open `http://localhost:8000/Home.html`

## Add or edit products
- Preferred: edit `productsData` in `Home.js` (array per category). Example entry:
```js
{ id: 'p4', name: 'Whey Isolate', price: 34.99, img: 'Images/your-image.jpg' }
```
- Alternative: add product card markup inside the matching category div in `Home.html`.

## Development notes
- Cart and favorites live in localStorage keys `cart` and `favs`.
- Category bars use `.Products > div` as horizontal scrollers; product cards are direct children.
- CSS: tweak sizes in `Home.css` (mobile breakpoints present at 600px).
- JS: `Home.js` exposes `window._shop` helpers for debugging (render/update functions).

## Git / collaboration tip
If local and remote branches diverge: commit or stash your changes, then pull with your preferred strategy. Example (safe):
```bash
git add .
git commit -m "WIP: update README"
git pull --rebase origin main
# resolve conflicts if any, then:
git push origin main
```

## Next steps / suggestions
- Move products to a JSON file or small backend for dynamic content.
- Add accessible labels and keyboard support for panels.
- Implement real checkout flow and backend order storage.
