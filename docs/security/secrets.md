# Clean-Room Secret Handling

This repo must not copy credential-bearing files from `vusa244`.

`vusa244` is reference material only. Do not copy its `composer.json`,
`composer.lock`, `auth.json`, `.env`, `app/etc/env.php`, Docker files, or CI
configuration into this project.

## Composer Auth

Use credential-free repository URLs in `composer.json`. Put real credentials in
local `auth.json` or CI secrets only.

Local setup:

1. Copy `auth.json.example` to `auth.json`.
2. Replace placeholder values with current, rotated vendor credentials.
3. Do not commit `auth.json`.

CI setup:

- Inject Composer credentials through CI secrets or BuildKit secrets.
- Do not bake credentials into image layers.
- Run `scripts/security/scan-secrets.sh` before publishing images.

## Rotations Required

The old `vusa244` repo exposed private Composer credentials. Rotate:

- Mirasvit credentials used by the old Composer repository URLs.
- Firebear credentials used by the old Composer repository URL.

Also review whether Magento Marketplace, Aheadworks, MageWorx, Amasty,
Rootways, payment, shipping, or integration credentials were exposed outside the
new repo.

## Sensitive Files

The following must stay out of Git and Docker contexts:

- `auth.json`, `auth.json.bak`
- `.env`, `.env.*`
- `app/etc/env.php`
- `*.sql`, `*.sql.gz`, `*.dump`, `*.bak`
- private keys and certs
- raw media/customer uploads where sensitive
- Magento generated/runtime files

