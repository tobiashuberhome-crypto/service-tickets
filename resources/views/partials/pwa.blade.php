<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<meta name="theme-color" content="#d40000">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Tickets">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {}));
}
</script>
