# RFC: Convert to a REST API with OpenAPI validation

> **Status as of 2026-08-21 (code at `0914871`): OPEN.**
> Forward-looking; nothing in the recent fixes affects it.
> Line references below were re-verified against that commit.


## Current state

The backend is a collection of ~37 standalone PHP scripts. Each script reads
`$_POST`/`$_GET` directly, does ad-hoc validation, and echoes either JSON or a
bare string (`"badSession"`, `"tooLong"`, `"1"`, etc.). There is:

- **No formal API contract** — clients must read PHP source to know what
  fields to send and what responses to expect.
- **No input validation at the API boundary** — missing/invalid fields produce
  PHP notices, undefined-variable warnings, or silent failures.
- **Inconsistent response shape** — some endpoints return objects, some return
  arrays, some return scalar strings. Error responses are not structured.
- **No discoverability** — no machine-readable description of endpoints,
  methods, schemas, or auth requirements.

This works for a single front-end maintained by the same author, but it makes
third-party clients, wrapper apps, and future migrations (e.g. SQLite) harder
than they need to be.

## What "REST + OpenAPI" means here

- **REST**: each endpoint is a resource operation with a stable URL and HTTP
  method (`GET /posts`, `POST /posts`, `POST /posts/{id}/comments`,
  `DELETE /posts/{id}`). The current action-per-script shape maps cleanly onto
  this — the change is mostly URL convention and method semantics.
- **OpenAPI**: a single `openapi.yaml` (or `openapi.json`) file that describes
  every endpoint, request/response schema, auth requirements, and error codes.
  It becomes the source of truth for the API.
- **Validation**: a small PHP middleware that checks incoming requests against
  the OpenAPI schema before the handler runs, returning `400` with a structured
  error body when validation fails. This eliminates the current scattered
  `strlen`/`isset` checks.

## Why it matters

- **Single source of truth** — the front-end, wrapper apps, and any future
  consumers all read the same contract. No more guessing whether a field is
  called `uname` or `user` or `userId`.
- **Structured errors** — replace `"badSession"` / `"tooLong"` / `"1"` with
  `{"error":"badSession","message":"..."}`, which is parseable by any client.
- **Validation in one place** — the current ad-hoc checks are spread across
  every file. A central schema means missing fields are caught once, not 37
  times.
- **Migration safety** — when the SQLite migration happens (PR #30), the
  OpenAPI spec stays stable even if the implementation layer changes. Clients
  don’t break.
- **Wrapper apps benefit immediately** — the Android/iOS wrappers (PR #32) can
  generate a typed client from the spec instead of hand-rolling HTTP calls.

## Options

### A. Add OpenAPI spec + validation middleware on top of current scripts

Keep the existing PHP files as controllers. Add:

1. `openapi.yaml` — describe every endpoint.
2. `php/validate.php` — a tiny middleware that reads the relevant schema
   fragment, checks `$_POST`/`$_GET`/headers, and either calls the handler or
   returns `400`.

```php
// example validate.php sketch
function validateRequest(string $operationId, array &$input): bool {
    $spec = openapi_spec_for($operationId); // load from openapi.yaml
    $errors = [];
    foreach ($spec['requestBody']['content']['application/json']['schema']['required'] as $field) {
        if (!isset($input[$field])) {
            $errors[] = "missing $field";
        }
    }
    if ($errors) {
        http_response_code(400);
        echo json_encode(['error' => 'validation_failed', 'details' => $errors]);
        return false;
    }
    return true;
}
```

- **Pro**: smallest migration; each file gets a one-line validation call.
- **Pro**: OpenAPI spec can be written incrementally (one endpoint at a time).
- **Con**: the current scripts still echo bare strings in places; those need
  to be normalized to a JSON envelope as part of the same effort.

### B. Rewrite endpoints as proper REST resources with a shared router

Replace the one-file-per-action layout with a small router (`index.php`) that
maps `METHOD /path` to a handler class. Handlers return structured arrays; a
single `response.php` serializes them and sets headers.

```php
// index.php sketch
$router = new Router();
$router->post('/posts', [PostController::class, 'create']);
$router->get('/posts/{id}', [PostController::class, 'show']);
$router->delete('/posts/{id}', [PostController::class, 'delete']);
$router->run();
```

- **Pro**: clean architecture; validation, auth, and response formatting happen
  in one place.
- **Pro**: makes the SQLite migration easier because controllers no longer
  touch file paths directly.
- **Con**: larger rewrite; every endpoint moves to a new structure. Harder to
  review in small PRs.

### C. Status quo

Keep the current layout. Not recommended long-term, but viable if the app
remains a single front-end with no third-party consumers.

## Proposed path

Start with **option A** and migrate to **option B** later:

1. Add `openapi.yaml` with the current endpoints described in REST terms.
2. Add `php/validate.php` and wire it into the hot paths first
   (`signIn.php`, `createJsonPost.php`, `newGetPost.php`, `likePost.php`,
   `addComment.php`).
3. Normalize all responses to a JSON envelope:
   ```php
   echo json_encode(['data' => $result, 'error' => null]);
   ```
   Bare-string responses become:
   ```php
   echo json_encode(['data' => null, 'error' => 'badSession', 'message' => '...']);
   ```
4. Once validation + envelope are stable, refactor into a router (option B)
   endpoint by endpoint.

## OpenAPI sketch (example)

```yaml
openapi: 3.0.3
info:
  title: Geo-Cam API
  version: 1.0.0
paths:
  /auth/signin:
    post:
      operationId: signIn
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [uname, pass]
              properties:
                uname: {type: string, maxLength: 25}
                pass: {type: string, minLength: 1}
      responses:
        '200':
          description: Session token
          content:
            application/json:
              schema:
                type: object
                properties:
                  data: {type: string}
                  error: {type: string, nullable: true}
        '400':
          $ref: '#/components/responses/ValidationError'
  /posts:
    get:
      operationId: listPosts
      parameters:
        - in: query
          name: lat
          required: true
          schema: {type: number}
        - in: query
          name: long
          required: true
          schema: {type: number}
      responses:
        '200':
          description: Posts near the user
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Post'
                  error: {type: string, nullable: true}
components:
  schemas:
    Post:
      type: object
      required: [id, user, lat, long, time, visibility]
      properties:
        id: {type: integer}
        user: {type: integer}
        lat: {type: number}
        long: {type: number}
        time: {type: integer}
        visibility: {type: string, enum: [all, following]}
        hidden: {type: boolean}
  responses:
    ValidationError:
      description: Invalid request
      content:
        application/json:
          schema:
            type: object
            properties:
              data: {type: string, nullable: true, example: null}
              error: {type: string, example: validation_failed}
              message: {type: string}
              details:
                type: array
                items: {type: string}
```

## Interaction with other RFCs

- **PR #34 (CORS)** — CORS headers are the transport-level access control;
  OpenAPI is the contract-level control. Both are useful; they solve different
  problems.
- **PR #30 (SQLite)** — a REST API with a shared router makes the SQLite
  migration a controller change, not an endpoint rewrite.
- **PR #32 (mobile notifications)** — wrapper apps can generate typed clients
  from the OpenAPI spec instead of hand-rolling HTTP calls.
- **PR #35 (rate limiting)** — rate-limit responses fit naturally into the
  structured error envelope (`429 Too Many Requests` with a JSON body).

## Questions for reviewers

1. Should we adopt a JSON envelope for all responses now, or only for errors?
2. Is the `openapi.yaml` file committed to the repo, or served dynamically by
   the backend?
3. Do we want auto-generated docs (Swagger UI / Redoc) alongside the spec, or
   is the YAML file enough?
4. Should the migration happen incrementally (option A) or all at once (option
   B)?

## References

- `php/signIn.php`, `php/createJsonPost.php`, `php/newGetPost.php` — endpoints
  with the most inconsistent response shapes.
- PR #34 — CORS enforcement
- PR #30 — SQLite migration
- PR #32 — mobile wrapper apps
- PR #35 — rate limiting
