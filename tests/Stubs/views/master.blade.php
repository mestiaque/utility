<!doctype html>
<html>
<head><title>@yield('title')</title></head>
<body>
@stack('buttons')
@yield('content')
@stack('scripts')
</body>
</html>
