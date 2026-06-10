# ums/sso-client

Laravel package for **consumer applications** that authenticate users via SmartExam SSO.

> **Documentation:** See [SSO Laravel Package](sso-package.md) in the SmartExam developer docs (`docs/developer-guide/sso-package.md`).

SmartExam (UMS) issues a signed token after the user approves SSO. This package verifies that token and logs the user into your Laravel app.

> **Browser flow:** Use the CDN script from SmartExam — `/js/sso-overlay.js` — to open the SSO prompt. This package handles the **server-side** callback and session creation.

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

## Installation

### From this monorepo (path)

In your consumer app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../smartexam-new/packages/ums/sso-client",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "ums/sso-client": "@dev"
    }
}
```

```bash
composer require ums/sso-client:@dev
php artisan vendor:publish --tag=smartexam-sso-config
php artisan vendor:publish --tag=smartexam-sso-migrations
php artisan migrate
```

### From Packagist (when published)

```bash
composer require ums-lspl/sso-client
php artisan vendor:publish --tag=smartexam-sso-config
```

## Configuration

`.env` on your consumer app:

```env
SMARTEXAM_URL=https://umsdemo.ucanapply.com
SSO_CLIENT_KEY=your-client-key
SSO_CLIENT_SECRET=your-client-secret
SSO_CALLBACK_URL=https://mail-server.ucanapply.com/sso/callback
SSO_AFTER_LOGIN_REDIRECT=/
```

Register the callback URL in **SmartExam Admin → SSO → Applications**.

## Routes (auto-registered)

| Method | Route | Name |
|--------|-------|------|
| GET | `/sso/callback` | `smartexam-sso.callback` |
| POST | `/api/sso/exchange` | `smartexam-sso.exchange` |

Customize paths in `config/smartexam-sso.php` under `routes`.

## Browser integration

Load the overlay helper from SmartExam:

```html
<script src="https://umsdemo.ucanapply.com/js/sso-overlay.js" defer></script>
<script>
  async function signInWithSmartExam() {
    const { token, expiresAt, state } = await window.launchSmartExamSsoOverlay({
      baseUrl: 'https://umsdemo.ucanapply.com',
      clientKey: 'YOUR_CLIENT_KEY',
      redirectUrl: 'https://mail-server.ucanapply.com/sso/callback',
    });

    await fetch('/api/sso/exchange', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      credentials: 'same-origin',
      body: JSON.stringify({ token, expiresAt, state }),
    });

    window.location.reload();
  }
</script>
```

Or use a full redirect to the callback URL (GET) — `SsoCallbackController` handles that automatically.

## CSRF state (recommended)

Before opening SSO, store state in session:

```php
use SmartExam\SsoClient\Support\SsoState;

$state = SsoState::remember();
// pass $state to launchSmartExamSsoOverlay({ ..., state })
```

## Custom user provisioning

Implement `SmartExam\SsoClient\Contracts\SsoUserProvisioner`:

```php
namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use SmartExam\SsoClient\Contracts\SsoUserProvisioner;

class MailServerSsoProvisioner implements SsoUserProvisioner
{
    public function fromPayload(array $payload): Authenticatable
    {
        return User::firstOrCreate(
            ['email' => $payload['email']],
            ['name' => $payload['name'], 'smartexam_id' => $payload['sub']]
        );
    }
}
```

Register in `config/smartexam-sso.php`:

```php
'user_provisioner' => App\Services\MailServerSsoProvisioner::class,
```

## Testing

```bash
cd packages/ums/sso-client
composer install
./vendor/bin/phpunit
```

## License

MIT
