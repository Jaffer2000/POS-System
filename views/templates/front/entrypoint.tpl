<!-- entrypoint.tpl -->
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{l s='POS system' mod='tbpos'}</title>
    {foreach $cssFiles as $css}
    <link rel="stylesheet" href="{$css}" />
    {/foreach}
    {foreach $jsFiles as $js}
    <script src="{$js}"></script>
    {/foreach}
  </head>
  <body>
    <div id="app"></div>

    <script>
      document.addEventListener("DOMContentLoaded", () => {
        startPOS(document.getElementById("app"), {
          apiBaseUrl: "{$apiUrl}",
          translations: {$translations|json_encode}
        });
      });
    </script>
  </body>
</html>
