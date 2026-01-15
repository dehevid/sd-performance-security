# SD (sonderr) Performance & Security

Lightweight WordPress plugin for performance optimization and security hardening. Removes bloat, adds security headers, and optimizes loading.

## Requirements

- WordPress 6.0+
- PHP 8.3+

## Installation

1. Upload the `sd-performance-security` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings under `Settings > SD Performance & Security`

## Features

### Performance

| Feature | Default | Description |
|---------|---------|-------------|
| Disable Emojis | ON | Removes ~15kb JS + CSS |
| Disable Embeds | ON | Removes oEmbed JS |
| Remove jQuery Migrate | ON | Frontend only |
| Heartbeat Frontend | OFF | Completely disable |
| Heartbeat Dashboard | 60s | Reduced frequency |
| Heartbeat Editor | 60s | Autosave still works |
| Remove Dashicons | ON | Non-logged-in users only |
| Remove Query Strings | ON | Improves CDN caching |
| Defer Scripts | OFF | Can cause issues |
| Remove Duotone SVG | ON | Block Editor filter (~10kb) |

### Security

| Feature | Default | Description |
|---------|---------|-------------|
| Disable XML-RPC | ON | Reduces brute-force attack surface |
| Security Headers | ON | X-Content-Type-Options, X-Frame-Options, Referrer-Policy |
| HSTS Header | OFF | Only enable if SSL is permanently configured |
| Permissions-Policy | ON | Blocks camera, microphone, geolocation |
| Hide WP Version | ON | Removes generator meta tag |
| Generic Login Errors | ON | Prevents username enumeration |
| Block User Enumeration | ON | Blocks /?author=1 and REST /users |
| Restrict REST API | OFF | Logged-in users only (may break plugins) |

### Cleanup

| Feature | Default | Description |
|---------|---------|-------------|
| Remove RSD Link | ON | Only needed for XML-RPC clients |
| Remove WLW Manifest | ON | Windows Live Writer (obsolete) |
| Remove Shortlink | ON | ?p=123 link |
| Remove REST API Links | ON | Discovery links |
| Remove Feed Links | ON | RSS/Atom links |
| Remove Adjacent Posts | ON | Saves 2 DB queries |
| Remove DNS Prefetch | ON | s.w.org not needed |
| Disable Comments | OFF | Completely disable |

## wp-config.php Recommendations

These settings cannot be configured via plugin:

```php
// Limit post revisions (reduces database bloat)
define('WP_POST_REVISIONS', 5);

// Disable file editing in admin (security)
define('DISALLOW_FILE_EDIT', true);

// Disable debug mode in production
define('WP_DEBUG', false);
```

## Verification

1. Activate plugin
2. Go to `Settings > SD Performance & Security`
3. Test frontend:
   - View Source: No emoji scripts, no embeds
   - Browser DevTools > Network > Response Headers
   - `/?author=1` should redirect to homepage
   - `/wp-json/wp/v2/users` should return 404 for non-logged-in users

## Important Notes

- **HSTS**: Only enable if SSL is permanently configured (lockout possible otherwise)
- **REST API Restriction**: May break plugins/themes that use REST API without authentication
- **Defer Scripts**: Can break inline scripts - test thoroughly
- **Heartbeat Editor**: Don't set too low - autosave needs it

## Changelog

### 1.0.0
- Initial release
- Performance: Emojis, Embeds, jQuery Migrate, Heartbeat, Dashicons, Query Strings, Defer, Duotone
- Security: XML-RPC, Headers, HSTS, Permissions-Policy, WP Version, Login Errors, User Enumeration, REST API
- Cleanup: RSD, WLW, Shortlink, REST Links, Feed Links, Adjacent Posts, DNS Prefetch, Comments

## License

GPL v2 or later
