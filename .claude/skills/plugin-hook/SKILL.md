---
name: plugin-hook
description: Adds a new Symfony EventDispatcher hook to src/Plugin.php — registers it in getHooks() and implements the static handler. Use when user says 'add hook', 'handle event', 'new plugin action', or adds a new lifecycle event to the CloudLinux licensing plugin. Do NOT use for modifying the detain/cloudlinux-licensing API client or adding new API methods to cloudlinux.inc.php.
---
# plugin-hook

## Critical

- **Always** call `$event->stopPropagation()` at the end of any handler that actually processes the event (i.e., inside the `if ($event['category'] == get_service_define('CLOUDLINUX'))` block).
- **Always** guard handler logic with the category check before doing any work — other plugins listen to the same events.
- **Never** call `$event->stopPropagation()` outside the category guard; doing so silently kills other plugins' handlers.
- Handler methods MUST be `public static` — the EventDispatcher calls them statically via the array registered in `getHooks()`.
- Log every significant action with `myadmin_log()` before and after the API call, not just on error.

## Instructions

### Step 1 — Choose the event name and handler name

Event names follow the pattern `{module}.{action}` for service lifecycle events, or a top-level name like `plugin.install`, `function.requirements`.

Existing events in `src/Plugin.php`:
- `plugin.install` / `plugin.uninstall`
- `licenses.settings`
- `licenses.activate` / `licenses.reactivate`
- `licenses.deactivate` / `licenses.deactivate_ip`
- `licenses.change_ip`
- `function.requirements`

Handler naming convention: `get` + PascalCase action (e.g., `licenses.suspend` → `getSuspend`).

Verify the event name does not already exist in `getHooks()` before proceeding.

### Step 2 — Register the hook in `getHooks()`

Open `src/Plugin.php` and add one entry to the array returned by `getHooks()`:

```php
public static function getHooks()
{
    return [
        // ... existing entries ...
        self::$module.'.suspend' => [__CLASS__, 'getSuspend'],  // <-- add here
    ];
}
```

Use `self::$module.'.'` prefix for service lifecycle events. Use a bare string for cross-module events like `'function.requirements'`.

Verify the array entry uses `[__CLASS__, 'MethodName']` — not a closure or string.

### Step 3 — Implement the handler method

Add a `public static` method to the `Plugin` class in `src/Plugin.php`. Use this skeleton:

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getSuspend(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('CLOUDLINUX')) {
        myadmin_log(self::$module, 'info', 'Cloudlinux Suspend', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        // --- perform the action ---
        $cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
        $response = $cl->remove($serviceClass->getIp(), $event['field1']);
        myadmin_log(self::$module, 'info', 'Response: '.json_encode($response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        if ($response === false) {
            $event['status'] = 'error';
            $event['status_text'] = 'Error suspending the license.';
        } else {
            $event['status'] = 'ok';
            $event['status_text'] = 'The license has been suspended.';
        }
        $event->stopPropagation();
    }
}
```

For handlers that delegate to a helper function in `src/cloudlinux.inc.php`, use `function_requirements()` first:

```php
public static function getDeactivate(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('CLOUDLINUX')) {
        myadmin_log(self::$module, 'info', 'Cloudlinux Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        function_requirements('deactivate_cloudlinux');
        $event['success'] = deactivate_cloudlinux($serviceClass->getIp(), $event['field1']);
        $event->stopPropagation();
    }
}
```

For non-service events (no category check needed), omit the guard — see `getRequirements()` in `src/Plugin.php` as an example that intentionally does NOT stop propagation.

Verify `$event->stopPropagation()` is inside the `if` block, not after it.

### Step 4 — Add API request logging (when calling the CloudLinux API)

After any `$cl->license()`, `$cl->remove()`, or similar mutating API call, add:

```php
request_log(self::$module, $GLOBALS['tf']->session->account_id, __FUNCTION__, 'cloudlinux', 'license', [$serviceClass->getIp(), $event['field1']], $response, $serviceClass->getId());
```

Read-only calls (`isLicensed`, `licenseList`) do not require `request_log()`.

### Step 5 — Run tests

```
composer test
```

Verify all existing tests pass. The `tests/PluginTest.php` uses reflection to inspect `getHooks()`, so new entries are automatically validated.

## Examples

**User says:** "Add a hook to handle `licenses.suspend` by removing the CloudLinux license for the IP"

**Actions taken:**

1. Add to `getHooks()` array in `src/Plugin.php`:
   ```php
   self::$module.'.suspend' => [__CLASS__, 'getSuspend'],
   ```

2. Add method to `Plugin` class in `src/Plugin.php`:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    * @throws \Detain\Cloudlinux\XmlRpcException
    */
   public static function getSuspend(GenericEvent $event)
   {
       $serviceClass = $event->getSubject();
       if ($event['category'] == get_service_define('CLOUDLINUX')) {
           myadmin_log(self::$module, 'info', 'Cloudlinux Suspend', __LINE__, __FILE__, self::$module, $serviceClass->getId());
           $cl = new Cloudlinux(CLOUDLINUX_LOGIN, CLOUDLINUX_KEY);
           $response = $cl->remove($serviceClass->getIp(), $event['field1']);
           request_log(self::$module, $GLOBALS['tf']->session->account_id, __FUNCTION__, 'cloudlinux', 'removeLicense', [$serviceClass->getIp(), $event['field1']], $response, $serviceClass->getId());
           myadmin_log(self::$module, 'info', 'Response: '.json_encode($response), __LINE__, __FILE__, self::$module, $serviceClass->getId());
           if ($response === false) {
               $event['status'] = 'error';
               $event['status_text'] = 'Error suspending the license.';
           } else {
               $event['status'] = 'ok';
               $event['status_text'] = 'The license has been suspended.';
           }
           $event->stopPropagation();
       }
   }
   ```

3. Run `composer test` — all tests pass.

**Result:** `licenses.suspend` events dispatched to this plugin will remove the CloudLinux license and set `$event['status']`.

## Common Issues

**Hook fires but does nothing / other plugins' handlers run too:**
You called `$event->stopPropagation()` outside the `if ($event['category'] == ...)` block, or forgot it entirely. It must be the last statement *inside* the category guard.

**`Fatal error: Call to undefined function get_service_define()`:**
The bootstrap hasn't loaded `include/functions.inc.php`. In tests, verify `tests/bootstrap.php` is resolving the Composer autoloader correctly. Run `composer test` from the plugin root, not from a subdirectory.

**`Undefined index: category` on `$event['category']`:**
The event was dispatched without setting the `category` key. Check the caller's `run_event()` call — it must pass a data array with `'category'` set to the service type integer.

**`Call to undefined function deactivate_cloudlinux()`:**
You forgot `function_requirements('deactivate_cloudlinux')` before calling the helper. Always call it immediately before the function, not at the top of the handler.

**`CLOUDLINUX_LOGIN` / `CLOUDLINUX_KEY` undefined constants:**
These are defined from the MyAdmin settings system at runtime. In CLI scripts or tests outside MyAdmin, you must define them manually or mock them. See `bin/cloudlinux_check.php` for the CLI pattern.

**New hook not appearing in `PluginTest::testHooksRegistered`:**
You added the method but forgot to add the entry to `getHooks()` in `src/Plugin.php`. Both changes are required — the test reflects `getHooks()` directly.
