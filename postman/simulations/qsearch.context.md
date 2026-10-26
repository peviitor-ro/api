# qsearch simulation

The CUIscan mock models the public search response from
https://cuiscan.ro/api-docs.html (checked 2026-10-26), with synthetic companies.
An owner workspace/mock search was skipped because no authenticated workspace
was established for this repository. Orbit discovery was attempted but its host
was unreachable; the provider's own documentation supplied the contract.

Run `php tests/integration/TestCompanyQsearch.php --postman` from the repository
root. The runner starts this simulation and the actual endpoint against a
separate PHP Solr stand-in, using random localhost ports and a temporary API copy.
`--serve` keeps the sandbox available for manual Postman requests.

On 2026-10-26, the final run passed all 11 HTTP/collection checks and all four
feature unit checks. CUIscan member
request events confirmed the endpoint called it, including zira and failure
scenarios; no member was silent. Invalid client requests produced no provider
requests. Timeout took approximately 8.5 seconds including quota spacing.

Postman CLI 1.76 requires write access to its local profile/dataset directory
even though this mock does not use datasets; a restricted sandbox initially
prevented startup. Running with profile access resolved that failure.

These runs prove behavior against the documented response shape and local Solr
stand-in. They do not verify live CUIscan availability, a deployed Solr schema,
or completeness of the provider's search.

Live read-only requests on 2026-10-26 returned 28 rows for zira and 50 for software.
The documentation still says 30: enforcing that number as a schema constraint
caused a reproducible 502 with `CUIscan returned an unsupported search response`.
The software regression reproduces 50 valid rows over HTTP. All returned rows
must be accepted, with a conservative warning at or above the documented threshold.

The pepper events regression covers an existing company disappearing from docs:
a wildcard term containing an escaped space returned zero in local Solr, while
an ordered complexphrase query returned company 29099990. Multiword searches
must use wildcard phrases; preserve the single-word query behavior.
