@php
    $ssoState = session(config('smartexam-sso.state_session_key'));
    if (blank($ssoState)) {
        $ssoState = \SmartExam\SsoClient\Support\SsoState::remember();
    }
    $ssoRedirectUrl = config('smartexam-sso.callback_url') ?: url(route('smartexam-sso.callback'));
@endphp
<script src="{{ \SmartExam\SsoClient\Support\SsoUrl::overlayScript() }}" defer></script>
<script>
async function signInWithSmartExam() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!csrfToken) {
        alert('CSRF token missing. Please refresh the page and try again.');
        return;
    }

    try {
        const { token, expiresAt, state } = await window.launchSmartExamSsoOverlay({
            baseUrl: @json(config('smartexam-sso.issuer')),
            clientKey: @json(config('smartexam-sso.client_key')),
            redirectUrl: @json($ssoRedirectUrl),
            state: @json($ssoState),
            resolveOnly: true,
        });

        const response = await fetch(@json(url(route('smartexam-sso.exchange', [], false))), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ token, expiresAt, state }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            alert(data.message ?? 'SSO login failed. Please try again.');
            return;
        }

        window.location.href = @json(url(config('smartexam-sso.after_login_redirect', '/')));
    } catch (error) {
        if (error?.message !== 'Popup was closed by the user' && !String(error?.message ?? '').includes('cancelled')) {
            alert(error?.message ?? 'SSO login failed. Please try again.');
        }
    }
}
</script>
