This requests the Whoer WHOIS page for the IP and attempts to extract a few fields. Results are best-effort and may be partial.

Output
- JSON with structure:
- success: true/false
- source: which endpoint was used
- data: associative array of fields or raw snippet

Limitations & notes
- Not a stable, documented API. HTML structure may change and break parsing. [web:1][page:1]
- Rate limits, abuse protections, or CAPTCHAs may prevent automated requests. Be courteous and add delays for loops.
- For production IP geolocation or robust API access, use a documented geolocation API (example: apiip.net, IPinfo, MaxMind). See their docs and pricing.

License
- MIT

Example output (caller IP):
{
"success": true,
"source": "whoer_trace",
"data": {
 "fl": "543f262",
 "h": "whoer.net",
 "ip": "1.2.3.4",
 "ts": "1777320973.000",
 "visit_scheme": "https",
 "uag": "Mozilla/5.0 (...)",
 ...
}
}
