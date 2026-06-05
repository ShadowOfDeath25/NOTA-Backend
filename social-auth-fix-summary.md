# Social Auth Fix Summary

## Problem

Google OAuth login completed successfully (user authenticated on Google's side), but after redirecting back to the frontend, the app showed the login page as if authentication never happened.

## Root Cause

The callback route (`GET /api/auth/social/{provider}/callback`) runs under the `api` middleware group, which had no session middleware:

```
api middleware group:
  ├─ HandleCors
  ├─ EnsureFrontendRequestsAreStateful
  └─ SubstituteBindings
```

Sanctum's `EnsureFrontendRequestsAreStateful` only adds session middleware (`StartSession`, `EncryptCookies`, `AddQueuedCookiesToResponse`) for requests that come **from the frontend** (detected via `Referer`/`Origin` headers matching a stateful domain). The Google OAuth callback comes from Google's servers with no such headers, so `fromFrontend()` returns `false` and the session middleware pipeline is skipped.

`Auth::login($user)` ran but with no `StartSession` to persist it — no session cookie was ever set. The frontend's subsequent `GET /api/v1/user` had no session cookie → 401 → login page.

*Why normal email login worked:* It POSTs from the SPA with `Referer: http://localhost:5173`, so Sanctum detects it as a frontend request and runs the full session stack.

## Changes

### 1. `routes/api.php` — Added `web` middleware to callback route

The `web` middleware group includes `EncryptCookies`, `AddQueuedCookiesToResponse`, and `StartSession` — the session middleware needed for `Auth::login()` to persist.

```php
Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->middleware('web');
```

The callback route now runs through both groups:

```
GET|HEAD  api/auth/social/{provider}/callback
           ⇂ api      (HandleCors, EnsureFrontendRequestsAreStateful, SubstituteBindings)
           ⇂ web      (EncryptCookies, AddQueuedCookiesToResponse, StartSession, ShareErrorsFromSession, PreventRequestForgery, SubstituteBindings)
```

### 2. `app/Http/Controllers/SocialAuthController.php` — Updated redirect URL

Changed the post-login redirect from the generic homepage to a dedicated frontend route:

```php
// Before
return redirect("{$frontendUrl}/?provider={$provider}");

// After
return redirect("{$frontendUrl}/auth/social/callback?provider={$provider}");
```

This gives the frontend a specific entry point to handle the OAuth return instead of relying on guards to silently figure it out.

### 3. (Frontend) New `src/pages/auth/SocialCallbackPage/SocialCallbackPage.tsx`

A dedicated page that handles the OAuth return:

- Shows "Completing sign in..." while checking auth
- If authenticated (`user.data` exists) → navigates to `/`
- If not (401) → navigates to `/login?error=social_login_failed`
- If a non-401 error occurs → shows error UI with back-to-login button

### 4. (Frontend) `src/router.tsx` — Added route

Registered the callback page at the top level (no guard wrapping):

```tsx
{
    path: "/auth/social/callback",
    element: <SocialCallbackPage/>
}
```

## Files Modified

| File | Change |
|---|---|
| `routes/api.php` | Added `->middleware('web')` to callback route |
| `app/Http/Controllers/SocialAuthController.php` | Redirect to `/auth/social/callback` instead of `/` |
| `src/pages/auth/SocialCallbackPage/SocialCallbackPage.tsx` | **New** — callback handler component |
| `src/router.tsx` | Added route for `/auth/social/callback` |
