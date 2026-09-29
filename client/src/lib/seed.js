export const STARTER_DATA = {
  settings: {
    restaurant_name: "Iya Bisi Kitchen",
    currency_symbol: "₦",
  },
  categories: [
    { id: 1, name: "Small Chops", display_order: 1 },
    { id: 2, name: "Soups & Swallow", display_order: 2 },
    { id: 3, name: "Rice & Mains", display_order: 3 },
    { id: 4, name: "Drinks", display_order: 4 },
  ],
  items: [
    { id: 1, category_id: 1, name: "Beef Suya", description: "Spiced grilled beef with onion, tomato, and extra yaji.", price: 2500, image_url: "https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Spicy"], prep_time: 15 },
    { id: 2, category_id: 1, name: "Puff Puff", description: "Soft fried dough balls, lightly sweet and warm.", price: 800, image_url: "https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Vegetarian"], prep_time: 10 },
    { id: 3, category_id: 1, name: "Asun", description: "Peppered goat meat, smoky and hot.", price: 3500, image_url: "https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Spicy"], prep_time: 18 },
    { id: 4, category_id: 2, name: "Egusi with Pounded Yam", description: "Melon seed soup, assorted meat, and smooth pounded yam.", price: 4500, image_url: "https://images.unsplash.com/photo-1476718406336-bb5a9690ee2a?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Gluten-Free"], prep_time: 25 },
    { id: 5, category_id: 2, name: "Catfish Pepper Soup", description: "Fresh catfish in a hot, fragrant broth.", price: 4000, image_url: "https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Spicy", "Gluten-Free"], prep_time: 20 },
    { id: 6, category_id: 3, name: "Party Jollof Rice", description: "Smoky party jollof with fried plantain and coleslaw.", price: 3200, image_url: "https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Spicy"], prep_time: 20 },
    { id: 7, category_id: 3, name: "Ofada Rice and Ayamase", description: "Local ofada rice with green pepper stew and boiled egg.", price: 3800, image_url: "https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Spicy"], prep_time: 22 },
    { id: 8, category_id: 3, name: "Moi Moi", description: "Steamed beans pudding with egg and fish.", price: 1500, image_url: "https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Gluten-Free"], prep_time: 30 },
    { id: 9, category_id: 4, name: "Zobo", description: "Cold hibiscus drink with ginger and pineapple.", price: 700, image_url: "https://images.unsplash.com/photo-1497534446932-c925b458314e?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Vegetarian", "Gluten-Free"], prep_time: 5 },
    { id: 10, category_id: 4, name: "Chapman", description: "House Chapman with cucumber, orange, and a light fizz.", price: 1200, image_url: "https://images.unsplash.com/photo-1513558161293-64a4e5b29d1c?auto=format&fit=crop&w=800&q=80", is_available: true, tags: ["Vegetarian"], prep_time: 4 }
  ],
};

export const ALL_TAGS = ["Gluten-Free", "Vegetarian", "Spicy"];
