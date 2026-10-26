// Search shape from https://cuiscan.ro/api-docs.html. All CUIs below are test fixtures.
const http = require('http');
http.createServer((req, res) => {
  const url = new URL(req.url, 'http://localhost');
  res.setHeader('Content-Type', 'application/json');
  if (req.method !== 'GET' || url.pathname !== '/api.php' || url.searchParams.get('action') !== 'search') {
    res.writeHead(501); return res.end(JSON.stringify({error: 'Endpoint not modeled'}));
  }
  const q = url.searchParams.get('q');
  const row = (cui, name = 'ZIRA TEST SRL') => ({cui, name, locality: 'Cluj-Napoca', county: 'Cluj'});
  if (q === 'failure') { res.writeHead(500); return res.end('<html>Unavailable</html>'); }
  if (q === 'ratelimit') { res.writeHead(429); return res.end(JSON.stringify({error: 'Too many requests'})); }
  if (q === 'timeout') { return setTimeout(() => res.end('[]'), 9500); }
  if (q === 'invalid') { return res.end(JSON.stringify({unexpected: []})); }
  if (q === 'objectempty') { return res.end('{}'); }
  if (q === 'malformed') { return res.end('{broken'); }
  if (q === 'badrow') { return res.end(JSON.stringify([row(88000001), {cui: 'x', name: 'BAD'}])); }
  if (q === 'empty') { return res.end('[]'); }
  if (q === 'pepper events') { return res.end(JSON.stringify([row(88300001, 'SALT & PEPPER EVENTS SRL')])); }
  if (q === 'limit') { return res.end(JSON.stringify(Array.from({length: 30}, (_, i) => row(88100000 + i, 'LIMIT TEST ' + i)))); }
  // Live read-only check: software returned 50 rows despite the documented maximum of 30.
  if (q === 'software') { return res.end(JSON.stringify(Array.from({length: 50}, (_, i) => row(88200000 + i, 'SOFTWARE TEST ' + i)))); }
  if (q === 'race') { return res.end(JSON.stringify([row(88000003, 'RACE TEST')])); }
  if (q === 'partial') { return res.end(JSON.stringify([row(88000004, 'PARTIAL TEST A'), row(88000005, 'PARTIAL TEST B')])); }
  res.end(JSON.stringify([row(88000001), row('RO88000001'), row(88000002)]));
}).listen(process.env.PORT || 18984);
