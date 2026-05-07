<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Pay Now</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
     </head>
     <body>
       <div class="card">
        

        <form action="{{ $actionUrl }}" method="POST">
            <input type="hidden" name="EncryptTrans" value={{ $EncryptTrans }}>

            <input type="hidden" name="merchIdVal" value={{ $merchIdVal }} />
            <input type="submit" value="Pay Again">
        </form>
    </div>
    </body>
</html>
