<!DOCTYPE html>
<html lang="{{ $mailLocale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('api.password_reset.mail_subject', [], $mailLocale) }}</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1a1a1a;">
    <p>{{ __('api.password_reset.mail_intro', ['portal' => __('api.password_reset.portal_'.$portalKey, [], $mailLocale)], $mailLocale) }}</p>
    <p style="font-size: 1.5rem; letter-spacing: 0.2em; font-weight: 600;">{{ $otp }}</p>
    <p style="color: #555; font-size: 0.9rem;">{{ __('api.password_reset.mail_expiry', ['minutes' => config('password_reset_otp.otp_ttl_minutes')], $mailLocale) }}</p>
    <p style="color: #555; font-size: 0.9rem;">{{ __('api.password_reset.mail_ignore', [], $mailLocale) }}</p>
</body>
</html>
