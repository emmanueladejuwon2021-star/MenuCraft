const { run } = require("../run");

module.exports = (req, res) => run(req, res, `/api/categories/${req.query.id}`);
