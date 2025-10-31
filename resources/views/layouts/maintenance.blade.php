<!doctype html>
<html @php(language_attributes())>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php(do_action('get_header'))
    @php(wp_head())
    
    @vite(['resources/assets/css/maintenance.css', 'resources/assets/js/maintenance.js'])
  </head>
  <body @php(body_class())>
    @php(wp_body_open())

    @yield('content')

    @php(do_action('get_footer'))
    @php(wp_footer())
  </body>
</html>
