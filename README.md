# MenuCraft

A live restaurant menu and price manager. Kitchen staff can mark dishes sold out, managers can change prices in bulk, and guests can open the menu from a QR code.

## Live preview

GitHub Pages: https://emmanueladejuwon2021-star.github.io/MenuCraft/

The Pages site works right away with a built-in sample menu stored in the browser. Connect Turso and deploy on Vercel when you want a shared live kitchen menu.

## Five screens

1. **Live Menu** — guest view with search, categories, and stock badges
2. **Dish Manager** — add, edit, and remove dishes
3. **Stock Toggle** — large on/off switches for busy kitchens
4. **Quick Pricing** — raise or lower a whole category in one tap
5. **QR & Share** — download a QR code and copy the guest link

## Run on your computer

```bash
cd client
npm install
npm run dev
```

Open the printed local address. Changes stay in this browser until you add the cloud database.

## Connect Turso (shared live menu)

1. Create a Turso database and copy the URL and token.
2. Add these values in Vercel project settings:
   - `TURSO_DATABASE_URL`
   - `TURSO_AUTH_TOKEN`
3. Optional client value: `VITE_API_URL` = your Vercel site, for example `https://your-app.vercel.app`
4. Redeploy. After that, GitHub Pages can talk to the same live menu if `VITE_API_URL` is set in the Pages build.

## Deploy

- **GitHub Pages:** every push to `main` builds `/client` and publishes the `gh-pages` branch.
- **Vercel:** import this repository. Build command and output folder are already set in `vercel.json`.

After the first Actions run, open the repository Settings → Pages and set the source to the `gh-pages` branch.

## Project layout

```
/client   Guest and staff screens (React + Vite)
/api      Vercel serverless routes + Turso tables
```
