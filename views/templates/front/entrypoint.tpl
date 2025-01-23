<!-- entrypoint.tpl -->
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TBPOS</title>
    <!-- Include the CSS -->
    <link rel="stylesheet" href="{$tbpos_css}" />
  </head>
  <body>
    <!-- Mount Vue.js App -->
    <div id="app"></div>

    <!-- Include JavaScript files -->
    <script src="{$tbpos_js}"></script>
    <script src="{$tbpos_router}"></script>
  </body>
</html>
