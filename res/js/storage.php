<!doctype html>
<html lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>Playa Storage</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
        <meta http-equiv="pragma" content="no-cache" />
        <link rel="shortcut icon" href=""/>
    </head>
    <body>
    <script src="require.js"  type="text/javascript"></script>
    <script type="text/javascript">
        require.config({
          enforceDefine: true,
          paths: {
            Storage: [
              'storage.min'
            ],
            PlayaLog: [
              'playalog.min'
            ]
          }
        });

        
        requirejs(["Storage"], function(storage) {
            //window["displayMessage"] = storage.prototype.displayMessage;
            if (window.addEventListener) {
              window.addEventListener("message", storage.displayMessage, false);
            } else {
              window.attachEvent("onmessage", storage.displayMessage);
            }
        });
    </script>
    </body>
</html>
