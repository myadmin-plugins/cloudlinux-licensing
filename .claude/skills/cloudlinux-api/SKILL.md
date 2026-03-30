---
name: cloudlinux-api
description: Uses the Detain\Cloudlinux\Cloudlinux client (from detain/cloudlinux-licensing) for license operations — isLicensed(), license(), remove(), licenseList(), kcareList(), imunifyList(). Use when user says 'check license', 'activate IP', 'remove license', 'list licenses', or adds bin/ scripts. Do NOT use for Plugin hook wiring or EventDispatcher registration.
---
# CloudLinux API

## Critical

- **Always** instantiate with `new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY)` — never hard-code credentials.
- **Always** `use Detain\Cloudlinux\Cloudlinux;` at the top of the file.
- **Never** call the XML-RPC endpoint directly from new code — use the `Cloudlinux` client methods.
- `isLicensed($ip, true)` returns an **array** of active type IDs; `isLicensed($ip)` (no second arg) returns bool. Pass `true` whenever you need to inspect which types are active.
- `remove()` without a type ID removes **all** licenses on that IP. Always pass `$type` when you only want to remove one tier.
- After every mutating call (`license()`, `remove()`), check `$response === false` for failure — the client returns `false`, not an exception, on API error.
- Log every API call with `myadmin_log()` before and after; log with `request_log()` after mutation calls.

## Instructions

### Step 1 — Bootstrap (bin/ scripts)

For standalone CLI scripts under `bin/`, load the environment with:

```php
#!/usr/bin/env php
<?php
use Detain\Cloudlinux\Cloudlinux;

require_once __DIR__.'/../../../../include/functions.inc.php';
```

Verify `CLOUDLINUX_LOGIN` and `CLOUDLINUX_KEY` are defined constants after the require before proceeding.

### Step 2 — Bootstrap (src/ helpers)

For helper functions in `src/cloudlinux.inc.php`, use:

```php
<?php
use Detain\Cloudlinux\Cloudlinux;
```

No require needed — Composer autoload handles it at runtime via the plugin loader.

### Step 3 — Instantiate the client

Always create a fresh client per operation:

```php
$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
```

### Step 4 — Check if an IP is licensed

```php
$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
$response = $cl->isLicensed($ipAddress, true); // true = return array of active type IDs
// $response is an array like [0 => 1, 1 => 16] or false/empty when unlicensed
if (is_array($response) && in_array($typeId, array_values($response))) {
    // already has this type licensed
}
```

### Step 5 — Activate a license

```php
myadmin_log('licenses', 'info', 'Activating license for '.$ipAddress, __LINE__, __FILE__);
$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
$response = $cl->license($ipAddress, $typeId);
request_log('licenses', $custid, __FUNCTION__, 'cloudlinux', 'license', [$ipAddress, $typeId], $response, $serviceId);
myadmin_log('licenses', 'info', 'Response: '.json_encode($response), __LINE__, __FILE__);
if ($response === false) {
    // handle error
}
```

Type IDs: `1`=CloudLinux, `16`=KernelCare, `40`=ImunityAV+, `41`=Imunify360 single, `42`=up to 30 users, `43`=up to 250 users, `49`=unlimited.

### Step 6 — Remove a license

```php
myadmin_log('cloudlinux', 'info', "Deactivate CloudLinux({$ipAddress}, {$type}) called", __LINE__, __FILE__);
$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
if ($type == false) {
    $response = $cl->remove($ipAddress);          // removes ALL types on this IP
} else {
    $response = $cl->remove($ipAddress, $type);   // removes only the given type
}
if (!isset($response['success']) || $response['success'] !== true) {
    // send failure email (see Common Issues below)
}
request_log('licenses', false, __FUNCTION__, 'cloudlinux', 'removeLicense', [$ipAddress, $type], $response);
myadmin_log('cloudlinux', 'info', "Deactivate response: ".json_encode($response), __LINE__, __FILE__);
```

### Step 7 — List all licenses

Use the helper function already registered in `src/cloudlinux.inc.php`:

```php
function_requirements('get_cloudlinux_licenses');
$licenses = get_cloudlinux_licenses();
print_r($licenses);
```

Or call directly:

```php
$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
$licenses = $cl->licenseList();   // all CloudLinux licenses
$kcare    = $cl->kcareList();     // KernelCare only
$imunify  = $cl->imunifyList();   // Imunify360 only
```

### Step 8 — Failure email on deactivation error

Mirror the pattern from `src/cloudlinux.inc.php:deactivate_cloudlinux()`:

```php
if (!isset($response['success']) || $response['success'] !== true) {
    $bodyRows = [];
    $bodyRows[] = 'License IP: '.$ipAddress.' unable to deactivate.';
    $bodyRows[] = 'Deactivation Response: .'.json_encode($response);
    $subject = 'Cloudlinux License Deactivation Issue IP: '.$ipAddress;
    $smartyE = new TFSmarty();
    $smartyE->assign('h1', 'Cloudlinux License Deactivation');
    $smartyE->assign('body_rows', $bodyRows);
    $msg = $smartyE->fetch('email/client/client_email.tpl');
    (new \MyAdmin\Mail())->multiMail($subject, $msg, false, 'client/client_email.tpl');
}
```

## Examples

**User says:** "Add a bin script to activate a CloudLinux license for a given IP"

**Actions taken:**
1. Create `bin/activate_cloudlinux.php`
2. Add shebang + `use` + require bootstrap
3. Instantiate client with constants
4. Call `isLicensed()` to check first, then `license()` if needed
5. Print result

**Result:**

```php
#!/usr/bin/env php
<?php
use Detain\Cloudlinux\Cloudlinux;

require_once __DIR__.'/../../../../include/functions.inc.php';

$ipAddress = $_SERVER['argv'][1];
$typeId    = isset($_SERVER['argv'][2]) ? (int)$_SERVER['argv'][2] : 1;

$cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
$existing = $cl->isLicensed($ipAddress, true);
if (is_array($existing) && in_array($typeId, array_values($existing))) {
    echo "IP {$ipAddress} already has type {$typeId} licensed.\n";
    exit(0);
}
$response = $cl->license($ipAddress, $typeId);
if ($response === false) {
    echo "Error licensing {$ipAddress}.\n";
    exit(1);
}
print_r($response);
echo "Success licensing {$ipAddress} type {$typeId}.\n";
```

---

**User says:** "Write a helper to deactivate KernelCare for an IP"

**Result:** Add to `src/cloudlinux.inc.php`:

```php
function deactivate_kcare($ipAddress)
{
    return deactivate_cloudlinux($ipAddress, 16);
}
```

(delegates to the existing `deactivate_cloudlinux()` with type `16`.)

## Common Issues

**`Call to undefined constant CLOUDLINUX_LOGIN`**
The `include/functions.inc.php` bootstrap was not loaded, or it was loaded but the settings have not been initialized. In bin/ scripts, verify the require path resolves: `realpath(__DIR__.'/../../../../include/functions.inc.php')` should exist. In src/ helpers called via plugin loader, `function_requirements()` must have been called first.

**`$response === false` on `license()` or `remove()`**
The XML-RPC call failed (network, bad credentials, or IP already in that state). Check `CLOUDLINUX_LOGIN` and `CLOUDLINUX_KEY` constants are correct. Run `php bin/cloudlinux_check.php <ip>` to verify connectivity. The client does not throw — it returns `false`.

**`isLicensed()` returns `false` but IP is definitely licensed**
You called `isLicensed($ip)` without the `true` second argument — it returns bool. Use `isLicensed($ip, true)` to get the array of active type IDs.

**`in_array($typeId, $response)` always false even though licensed**
Use `in_array($typeId, array_values($response))` — the response array may have non-sequential keys.

**`Class 'TFSmarty' not found` in deactivation error email**
The function is being called outside the MyAdmin request context. `TFSmarty` is only available after the full MyAdmin bootstrap. In CLI/bin scripts, skip the email and only log with `myadmin_log()`.