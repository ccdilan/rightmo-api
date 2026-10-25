# Catalog performance evidence

Verified locally on 25 October 2026 using MySQL 8.4 in the supplied Docker stack.

## Query budget

`ProductQueryPerformanceTest` renders real API responses over 48 products in eight categories. It verifies both 12-item and 48-item pages:

| Request | SQL queries |
| --- | ---: |
| Unfiltered product listing | 3 |
| Listing with eight selected categories | 4 |

The three baseline queries count products, retrieve the page, and eager-load categories. Multi-category validation adds one batched existence query, not one query per selected category. The test uses Sanctum's test authentication; token lookup/update overhead in a real bearer-token request is outside this budget. Increasing the page size must not introduce N+1 category queries.

## Existing ordering index

The products schema already has `(created_at, id)`, price, rating, and category indexes. No redundant migration was added.

For the 24-product demo catalog, EXPLAIN chooses a table scan and filesort. That is a reasonable optimizer choice for a tiny table, not evidence that the index is missing.

A temporary table copied with `CREATE TEMPORARY TABLE product_query_probe LIKE products` was populated with 5,000 synthetic products, distinct timestamps, and the same schema. It was removed after the connection-local experiment; application rows were unchanged. For `ORDER BY created_at DESC, id DESC LIMIT 12`:

| Plan | Observed work |
| --- | --- |
| Existing ordering index explicitly ignored for comparison | Scan 5,000 rows and sort; observed root time about 9.15 ms |
| Normal optimizer with the existing index available | Reverse index scan of 12 rows, no sort; observed root time about 1.08 ms |

These are single local diagnostic samples, not a production latency guarantee or a statistically controlled benchmark. The useful evidence is the change in work and plan. Avoid forcing index hints in application queries based on these samples.

## Boundaries and future scaling

- Search is capped at eight terms and 160 characters; category selection at 20 IDs; page size at 100. Sort columns/directions remain allowlisted and all values are bound.
- Literal substring searches use leading-wildcard LIKE conditions. A normal B-tree does not make that search indexed. At materially larger scale, measure query plans on representative data and evaluate full-text search with an explicit decision about changed matching semantics.
- Authenticated catalog data remains uncached across requests. Stable, public image filenames retain the existing asset caching policy.
- Catalog rendering now creates one currency formatter per render, reused across all cards. It does not cache user data globally.
- Image previews use local blob URLs, released when a selection changes or its component is hidden/unmounted. Full uploaded images still use the existing public-asset delivery; responsive thumbnails/CDN work remains a separately measurable future improvement.
