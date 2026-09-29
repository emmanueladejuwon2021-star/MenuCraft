const { createClient } = require("@libsql/client");

function getDb() {
  const url = process.env.TURSO_DATABASE_URL;
  const authToken = process.env.TURSO_AUTH_TOKEN;
  if (!url || !authToken) return null;
  return createClient({ url, authToken });
}

let ready = false;

async function ensureSchema(db) {
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
    currency_symbol TEXT DEFAULT '$'
  )`);

  const settings = await db.execute("SELECT id FROM restaurant_settings WHERE id = 1");
  if (!settings.rows.length) {
    await db.execute({
      sql: "INSERT INTO restaurant_settings (id, restaurant_name, currency_symbol) VALUES (1, ?, ?)",
      args: ["Harbor Table", "$"],
    });
  }

  const cats = await db.execute("SELECT COUNT(*) AS count FROM categories");
  if (Number(cats.rows[0].count) === 0) {
    await db.execute("INSERT INTO categories (name, display_order) VALUES ('Starters', 1)");
    await db.execute("INSERT INTO categories (name, display_order) VALUES ('Mains', 2)");
    await db.execute("INSERT INTO categories (name, display_order) VALUES ('Drinks', 3)");
    const seeded = await db.execute("SELECT id, name FROM categories ORDER BY display_order");
    const byName = Object.fromEntries(seeded.rows.map((row) => [row.name, row.id]));
    const dishes = [
      [byName.Starters, "Crispy Calamari", "Lightly fried squid with lemon aioli.", 12.5, "", 1, "Spicy", 12],
      [byName.Starters, "Garden Hummus Plate", "Chickpea spread, warm pita, and raw vegetables.", 9.0, "", 1, "Vegetarian,Gluten-Free", 8],
      [byName.Mains, "Herb Roast Chicken", "Half chicken, pan juices, roasted potatoes.", 22.0, "", 1, "Gluten-Free", 25],
      [byName.Mains, "Mushroom Risotto", "Arborio rice, wild mushrooms, parmesan.", 18.5, "", 1, "Vegetarian", 20],
      [byName.Drinks, "House Lemonade", "Fresh lemon, mint, sparkling water.", 4.5, "", 1, "Vegetarian,Gluten-Free", 3],
      [byName.Drinks, "Chili Mango Cooler", "Mango puree with a gentle chili rim.", 6.0, "", 1, "Spicy,Vegetarian", 4],
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
