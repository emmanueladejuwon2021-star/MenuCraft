const { run } = require("../run");

module.exports = (req, res) => run(req, res, `/api/orders/${req.query.id}`);
