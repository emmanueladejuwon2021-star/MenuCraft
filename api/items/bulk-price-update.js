const { run } = require("../run");

module.exports = (req, res) => run(req, res, "/api/items/bulk-price-update");
