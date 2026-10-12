<?php
// includes/db.php - SQLite Database Connection and Initializer for MenuCraft

function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dbFile = getenv('DB_PATH') ?: __DIR__ . '/../menu.db';
    $isNew = !file_exists($dbFile);

    try {
        $pdo = new PDO('sqlite:' . $dbFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
    } catch (PDOException $e) {
        die("Database connection failed: " . htmlspecialchars($e->getMessage()));
    }

    ensureSchema($pdo);
    return $pdo;
}

function ensureSchema(PDO $db): void {
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        restaurant_name TEXT NOT NULL,
        role TEXT DEFAULT 'guest',
        phone TEXT DEFAULT ''
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        display_order INTEGER DEFAULT 0
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS menu_items (
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
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS restaurant_settings (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        restaurant_name TEXT NOT NULL DEFAULT 'Iya Bisi Kitchen',
        currency_symbol TEXT DEFAULT '₦'
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS sessions (
        token TEXT PRIMARY KEY,
        user_id INTEGER NOT NULL,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        guest_name TEXT NOT NULL,
        guest_email TEXT NOT NULL,
        phone TEXT,
        note TEXT,
        items_json TEXT NOT NULL,
        total REAL NOT NULL,
        paid INTEGER DEFAULT 0,
        pay_ref TEXT,
        status TEXT DEFAULT 'New',
        view_token TEXT,
        created_at TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS staff_claim (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        user_id INTEGER NOT NULL DEFAULT 0
    )");

    // Initialize default restaurant settings if not set
    $stmt = $db->query("SELECT id FROM restaurant_settings WHERE id = 1");
    if (!$stmt->fetch()) {
        $ins = $db->prepare("INSERT INTO restaurant_settings (id, restaurant_name, currency_symbol) VALUES (1, ?, ?)");
        $ins->execute(['Iya Bisi Kitchen', '₦']);
    }

    // Initialize staff claim if not set
    $claim = $db->query("SELECT id FROM staff_claim WHERE id = 1");
    if (!$claim->fetch()) {
        $staff = $db->query("SELECT id FROM users WHERE role = 'staff' ORDER BY id LIMIT 1")->fetch();
        $staffId = $staff ? (int)$staff['id'] : 0;
        $ins = $db->prepare("INSERT INTO staff_claim (id, user_id) VALUES (1, ?)");
        $ins->execute([$staffId]);
    }

    // Seed default categories and dishes if categories table is empty
    $catCount = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount === 0) {
        seedInitialMenu($db);
    }

    $initialized = true;
}

function seedInitialMenu(PDO $db): void {
    $categories = [
        ['Small Chops', 1],
        ['Soups & Swallow', 2],
        ['Rice & Mains', 3],
        ['Drinks', 4],
    ];

    $catStmt = $db->prepare("INSERT INTO categories (name, display_order) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }

    $catMap = [];
    foreach ($db->query("SELECT id, name FROM categories")->fetchAll() as $row) {
        $catMap[$row['name']] = (int)$row['id'];
    }

    $dishes = [
        [$catMap['Small Chops'], 'Beef Suya', 'Spiced grilled beef with onion, tomato, and extra yaji.', 2500, 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=800&q=80', 1, 'Spicy', 15],
        [$catMap['Small Chops'], 'Puff Puff', 'Soft fried dough balls, lightly sweet and warm.', 800, 'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=800&q=80', 1, 'Vegetarian', 10],
        [$catMap['Small Chops'], 'Asun', 'Peppered goat meat, smoky and hot.', 3500, 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?auto=format&fit=crop&w=800&q=80', 1, 'Spicy', 18],
        [$catMap['Soups & Swallow'], 'Egusi with Pounded Yam', 'Melon seed soup, assorted meat, and smooth pounded yam.', 4500, 'https://images.unsplash.com/photo-1476718406336-bb5a9690ee2a?auto=format&fit=crop&w=800&q=80', 1, 'Gluten-Free', 25],
        [$catMap['Soups & Swallow'], 'Catfish Pepper Soup', 'Fresh catfish in a hot, fragrant broth.', 4000, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=800&q=80', 1, 'Spicy,Gluten-Free', 20],
        [$catMap['Rice & Mains'], 'Party Jollof Rice', 'Smoky party jollof with fried plantain and coleslaw.', 3200, 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=800&q=80', 1, 'Spicy', 20],
        [$catMap['Rice & Mains'], 'Ofada Rice and Ayamase', 'Local ofada rice with green pepper stew and boiled egg.', 3800, 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=800&q=80', 1, 'Spicy', 22],
        [$catMap['Rice & Mains'], 'Moi Moi', 'Steamed beans pudding with egg and fish.', 1500, 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=800&q=80', 1, 'Gluten-Free', 30],
        [$catMap['Drinks'], 'Zobo', 'Cold hibiscus drink with ginger and pineapple.', 700, 'https://images.unsplash.com/photo-1497534446932-c925b458314e?auto=format&fit=crop&w=800&q=80', 1, 'Vegetarian,Gluten-Free', 5],
        [$catMap['Drinks'], 'Chapman', 'House Chapman with cucumber, orange, and a light fizz.', 1200, 'https://images.unsplash.com/photo-1513558161293-64a4e5b29d1c?auto=format&fit=crop&w=800&q=80', 1, 'Vegetarian', 4],
    ];

    $dishStmt = $db->prepare("INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, tags, prep_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($dishes as $dish) {
        $dishStmt->execute($dish);
    }
}
