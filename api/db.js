const { createClient } = require("@libsql/client");

function getDb() {
  const url = process.env.TURSO_DATABASE_URL || "file:menu.db";
  const authToken = process.env.TURSO_AUTH_TOKEN || undefined;
  return createClient({ url, authToken });
}

let ready = false;

async function ensureSchema(db) {
  await db.execute(`CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    restaurant_name TEXT NOT NULL
  )`);
  await db.execute(`CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    display_order INTEGER DEFAULT 0
  )`);
  await db.execute(`CREATE TABLE IF NOT EXISTS menu_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL CHECK(price >= 0),
    image_url TEXT,
    is_available INTEGER DEFAULT 1,
    tags TEXT,
    prep_time INTEGER DEFAULT 10,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
  )`);
  await db.execute(`CREATE TABLE IF NOT EXISTS restaurant_settings (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    restaurant_name TEXT NOT NULL DEFAULT 'My Restaurant',
    currency_symbol TEXT DEFAULT '₦'
  )`);
  await db.execute(`CREATE TABLE IF NOT EXISTS sessions (
    token TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
  )`);
  await db.execute(`CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    guest_name TEXT NOT NULL,
    guest_email TEXT NOT NULL,
    phone TEXT,
    note TEXT,
    items_json TEXT NOT NULL,
    total REAL NOT NULL,
    paid INTEGER DEFAULT 1,
    pay_ref TEXT,
    status TEXT DEFAULT 'New',
    created_at TEXT
  )`);
  const extras = [
    "ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'staff'",
    "ALTER TABLE users ADD COLUMN phone TEXT DEFAULT ''",
  ];
  for (const sql of extras) {
    try {
      await db.execute(sql);
    } catch {
      // column already exists
    }
  }

  const settings = await db.execute("SELECT id FROM restaurant_settings WHERE id = 1");
  if (!settings.rows.length) {
    await db.execute({
      sql: "INSERT INTO restaurant_settings (id, restaurant_name, currency_symbol) VALUES (1, ?, ?)",
      args: ["Iya Bisi Kitchen", "₦"],
    });
  }

  const cats = await db.execute("SELECT COUNT(*) AS count FROM categories");
  if (Number(cats.rows[0].count) === 0) {
    await db.execute("INSERT INTO categories (name, display_order) VALUES ('Small Chops', 1)");
    await db.execute("INSERT INTO categories (name, display_order) VALUES ('Soups & Swallow', 2)");
    await db.execute("INSERT INTO categories (name, display_order) VALUES ('Rice & Mains', 3)");
    await db.execute("INSERT INTO categories (name, display_order) VALUES ('Drinks', 4)");
    const seeded = await db.execute("SELECT id, name FROM categories ORDER BY display_order");
    const byName = Object.fromEntries(seeded.rows.map((row) => [row.name, row.id]));
    const dishes = [
      [byName["Small Chops"], "Beef Suya", "Spiced grilled beef with onion, tomato, and extra yaji.", 2500, "", 1, "Spicy", 15],
      [byName["Small Chops"], "Puff Puff", "Soft fried dough balls, lightly sweet and warm.", 800, "", 1, "Vegetarian", 10],
      [byName["Small Chops"], "Asun", "Peppered goat meat, smoky and hot.", 3500, "", 1, "Spicy", 18],
      [byName["Soups & Swallow"], "Egusi with Pounded Yam", "Melon seed soup, assorted meat, and smooth pounded yam.", 4500, "", 1, "Gluten-Free", 25],
      [byName["Soups & Swallow"], "Catfish Pepper Soup", "Fresh catfish in a hot, fragrant broth.", 4000, "", 1, "Spicy,Gluten-Free", 20],
      [byName["Rice & Mains"], "Party Jollof Rice", "Smoky party jollof with fried plantain and coleslaw.", 3200, "", 1, "Spicy", 20],
      [byName["Rice & Mains"], "Ofada Rice and Ayamase", "Local ofada rice with green pepper stew and boiled egg.", 3800, "", 1, "Spicy", 22],
      [byName["Rice & Mains"], "Moi Moi", "Steamed beans pudding with egg and fish.", 1500, "", 1, "Gluten-Free", 30],
      [byName.Drinks, "Zobo", "Cold hibiscus drink with ginger and pineapple.", 700, "", 1, "Vegetarian,Gluten-Free", 5],
      [byName.Drinks, "Chapman", "House Chapman with cucumber, orange, and a light fizz.", 1200, "", 1, "Vegetarian", 4],
    ];
    for (const dish of dishes) {
      await db.execute({
        sql: "INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, tags, prep_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        args: dish,
      });
    }
  }
  ready = true;
}

async function readyDb() {
  const db = getDb();
  if (!db) return null;
  if (!ready) await ensureSchema(db);
  return db;
}

function mapItem(row) {
  return {
    id: Number(row.id),
    category_id: Number(row.category_id),
    name: row.name,
    description: row.description || "",
    price: Number(row.price),
    image_url: row.image_url || "",
    is_available: Number(row.is_available) === 1,
    tags: row.tags ? String(row.tags).split(",").map((tag) => tag.trim()).filter(Boolean) : [],
    prep_time: Number(row.prep_time || 10),
  };
}

module.exports = { getDb, readyDb, mapItem };
