# JEL-IMS Production Hardening Checklist

Use this checklist before exposing JEL-IMS to the public internet.

---

## 1) Critical before launch

- [ ] Remove or rotate default credentials (`admin@jelims.com`, `admin123`).
- [ ] Enforce strong passwords for all Admin/Staff accounts.
- [ ] Store DB credentials in environment variables (not committed in repo).
- [ ] Set web server document root to `public/` only.
- [ ] Block direct access to `app/`, `docs/`, and `database/` from the web.
- [ ] Enable HTTPS and force HTTP -> HTTPS redirect.
- [ ] Configure secure session cookies:
  - [ ] `Secure`
  - [ ] `HttpOnly`
  - [ ] `SameSite=Lax` (or `Strict` where possible)
- [ ] Add CSRF tokens to all state-changing forms:
  - [ ] booking create/cancel
  - [ ] technician assignment
  - [ ] status updates
  - [ ] user/technician management actions
- [ ] Disable detailed PHP error display in production (`display_errors=Off`).

---

## 2) Access control and auth hardening

- [ ] Keep Admin as oversight/governance role (read-only for operations).
- [ ] Keep Staff limited to operational technician/booking flows.
- [ ] Enforce server-side RBAC checks on every sensitive controller action.
- [ ] Add login rate-limiting and temporary lockout after repeated failures.
- [ ] Add password reset flow with expiring, single-use tokens.
- [ ] Consider MFA for Admin accounts.

---

## 3) Application security headers

Add these at web server level (Apache/Nginx) or app middleware:

- [ ] `Content-Security-Policy` (start restrictive, then tune)
- [ ] `X-Frame-Options: DENY` (or CSP `frame-ancestors`)
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `Referrer-Policy: strict-origin-when-cross-origin`
- [ ] `Permissions-Policy` (disable unused browser features)

---

## 4) Database and data protection

- [ ] Use a dedicated DB user with least privilege (no root in production app).
- [ ] Restrict DB host/network access (private network, firewall allowlist).
- [ ] Enable regular automated backups.
- [ ] Encrypt backup files at rest.
- [ ] Test restore process regularly (not just backup creation).
- [ ] Mask/sanitize sensitive fields in exported logs/reports where possible.

---

## 5) Logging, monitoring, and incident readiness

- [ ] Log authentication events (success/failure, lockouts, role-sensitive actions).
- [ ] Log operational audit events (assignment, status changes, account status changes).
- [ ] Never log passwords, tokens, session IDs, or raw secrets.
- [ ] Centralize logs and define retention policy.
- [ ] Set alerts for suspicious patterns (brute force, unusual admin actions).
- [ ] Prepare incident response steps (credential rotation, rollback, containment).

---

## 6) Deployment and maintenance hygiene

- [ ] Run production with a non-debug configuration.
- [ ] Keep PHP, web server, and OS packages updated.
- [ ] Pin and review third-party dependencies/CDN usage.
- [ ] Review file/folder permissions (least privilege).
- [ ] Disable unused PHP functions/extensions where practical.
- [ ] Add periodic security review cycle (monthly/quarterly).

---

## 7) Things you should NOT do

- [ ] Do not keep demo/default credentials.
- [ ] Do not expose project root directly to the internet.
- [ ] Do not commit real secrets to git.
- [ ] Do not run production without TLS.
- [ ] Do not rely on UI hiding alone for authorization.
- [ ] Do not expose stack traces or SQL errors to end users.
- [ ] Do not skip backup restore testing.

---

## 8) Quick verification commands/checks (manual)

- [ ] Confirm `public/` is the only served root.
- [ ] Confirm visiting `/app/...` directly is blocked externally.
- [ ] Confirm login/session cookie has secure flags in browser dev tools.
- [ ] Confirm failed login attempts trigger throttling/lockout.
- [ ] Confirm CSRF token is required on mutating POST requests.
- [ ] Confirm default admin account is removed or fully rotated.

---

## 9) Recommended next implementation steps (JEL-IMS)

1. Add CSRF utility and token checks to all POST forms/controllers.
2. Move DB config to environment variables and provide `.env.example`.
3. Add auth throttling in `AuthController.php`.
4. Add secure headers in web server config.
5. Add deployment profile (`dev` vs `prod`) and disable verbose errors in prod.

