const express = require("express");
const path = require("path");
const apiApp = require("./api/index");

async function startServer() {
  const app = express();
  const PORT = process.env.PORT || 3000;

  // Serve API routes
  app.use(apiApp);

  if (process.env.NODE_ENV === "production") {
    // Serve static files in production
    app.use(express.static(path.join(__dirname, "client/dist")));
    app.get("*", (req, res) => {
      res.sendFile(path.join(__dirname, "client/dist/index.html"));
    });
  } else {
    // In development, mount Vite dev server as a middleware
    const { createServer: createViteServer } = require("vite");
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
      root: path.join(__dirname, "client"),
    });
    app.use(vite.middlewares);
  }

  app.listen(PORT, "0.0.0.0", () => {
    console.log(`Server listening on http://0.0.0.0:${PORT}`);
  });
}

startServer().catch((err) => {
  console.error("Failed to start server", err);
});
