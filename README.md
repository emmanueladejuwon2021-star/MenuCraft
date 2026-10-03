# MenuCraft

A live restaurant menu and price manager. Kitchen staff can mark dishes sold out, managers can change prices in bulk, and guests can open the menu from a QR code.

## Live preview

GitHub Pages: https://emmanueladejuwon2021-star.github.io/MenuCraft/

The Pages site works right away with a built-in sample menu stored in the browser. Connect Turso and deploy on Vercel when you want a shared live kitchen menu.

## Guest site and kitchen door

Guests only see Home, Menu, Plate, and Account. There is no kitchen login on those screens.

Staff open a hidden path:

- Local: http://localhost:5173/#/iyabisi/kitchenlock
- GitHub Pages: https://emmanueladejuwon2021-star.github.io/MenuCraft/#/iyabisi/kitchenlock

Bookmark that link on the kitchen tablet. Do not print it on the guest QR code. If someone types `/account` on the guest site, they are sent back to the menu.

After staff sign in, kitchen tools appear: dishes, stock, prices, orders, and share.

The kitchen path is only a bookmark. It is not a lock. Dish, price, stock, category, settings, and order-status changes need a staff session. The first account created from that kitchen form becomes staff. A guest form never becomes staff. Later staff accounts must be created by someone who is already signed in as staff. A blank email cannot read other orders. Order totals use the prices stored on the menu, not the numbers sent by the browser.

Checkout asks for a table note only. It does not collect card numbers.

## Five screens

1. Live Menu — guest view with search, categories, and stock badges
2. Dish Manager — add, edit, and remove dishes
3. Stock Toggle — large on/off switches for busy kitchens
4. Quick Pricing — raise or lower a whole category in one tap
5. QR and Share — download a QR code and copy the guest link

## Run on your computer

cd client

npm install

npm run dev

Open the printed local address. Changes stay in this browser until you add the cloud database.

Price rules are covered by `npm test` from the repo root. CI runs those tests and a client build on every pull request.

## Connect Turso (shared live menu)

1. Create a Turso database and copy the URL and token.
2. Add these values in Vercel project settings: TURSO_DATABASE_URL and TURSO_AUTH_TOKEN.
3. Optional client value: VITE_API_URL = your Vercel site.
4. Optional: MENUCRAFT_SEED = 1 only if you want the sample Nigerian menu on an empty database.
5. Redeploy. After that, GitHub Pages can talk to the same live menu if VITE_API_URL is set in the Pages build.

## Deploy

GitHub Pages: every push to main builds /client and publishes the gh-pages branch.

Vercel: import this repository. Build command and output folder are already set in vercel.json.

After the first Actions run, open the repository Settings, then Pages, and set the source to the gh-pages branch.

## Project layout

/client   Guest and staff screens

/api      Vercel routes and Turso tables
