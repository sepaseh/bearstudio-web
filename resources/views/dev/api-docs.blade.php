<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bear Studio API documentation</title>
    <link rel="stylesheet" href="/dev/swagger/swagger-ui.css">
</head>
<body>
<main id="swagger-ui" aria-label="API documentation"></main>
<script src="/dev/swagger/swagger-ui-bundle.js"></script>
<script>
window.addEventListener('load', () => {
    SwaggerUIBundle({
        url: '/dev/openapi.json', dom_id: '#swagger-ui', validatorUrl: null,
        withCredentials: true, deepLinking: true,
        requestInterceptor: async (request) => {
            const target = new URL(request.url, window.location.origin);
            if (target.origin === window.location.origin && target.pathname.startsWith('/api/v1/') && !['GET', 'HEAD', 'OPTIONS'].includes(request.method.toUpperCase())) {
                const response = await fetch('/api/v1/auth/csrf', {credentials: 'same-origin', headers: {Accept: 'application/json'}});
                if (!response.ok) throw new Error('Unable to obtain a CSRF token.');
                request.headers['X-CSRF-TOKEN'] = (await response.json()).requestToken;
            }
            return request;
        }
    });
});
</script>
</body>
</html>
