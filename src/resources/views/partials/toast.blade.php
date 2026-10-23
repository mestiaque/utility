{{--
    Shared toast helper for utility pages: utNotify('success' | 'error' | 'warning' | 'info', message)
    Uses the me::master layout's toastr; text is HTML-escaped, "\n" becomes a line break.
    Falls back to alert() for errors when toastr is not available.
--}}
<script>
    window.utNotify = window.utNotify || function (type, message) {
        if (!message) return;
        if (window.toastr && typeof window.toastr[type] === 'function') {
            const safe = String(message).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
            window.toastr[type](safe.replace(/\n/g, '<br>'));
        } else if (type === 'error') {
            alert(message);
        }
    };
</script>
