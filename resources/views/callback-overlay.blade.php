<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completing sign-in…</title>
</head>
<body>
    <script>
        (function () {
            const payload = {
                type: 'ums-sso-complete',
                redirectUrl: @json($redirectUrl),
                token: @json($token),
                expiresAt: @json($expiresAt),
                state: @json($state),
            };

            if (window.parent === window) {
                window.location.replace(payload.redirectUrl);
                return;
            }

            window.parent.postMessage(payload, '*');
        })();
    </script>
</body>
</html>
