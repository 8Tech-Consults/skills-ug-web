<?php
if (!isset($body)) {
    $body = 'Hello Muhindo Mubarka, Use the following to reset your TaskEase password.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eitht Tech Consults Limited</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

    <!-- Custom Styles -->
    <style>
        /* create css var primary color as #066f6e */
        :root {
            --primary-color: #066f6e;
        }

        body {
            font-family: Arial, sans-serif;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #066f6e;
            color: #fff;
            text-align: center;
            padding: 10px;
        }

        .content {
            padding: 20px;
            background-color: #fff;
            color: #424649;
        }

        .footer {
            background-color: white;
            text-align: center;
            padding: 10px;
            padding-top: 20px;
        }

        .text-primary {
            color: var(--primary-color) !important;
        }
    </style>
</head>

<body style="background-color: #e7f6ff; background: #e7f6ff;">

    <div class="email-container" style="background-color: #e7f6ff; background: #e7f6ff;">
        <!-- Header -->
        <div class="footer" style="border-bottom: 2px solid #066f6e;">
            <h2 style="color: #066f6e;">{{ env('APP_NAME') }}</h2>
        </div>
        <!-- Content -->
        <div class="content"
            style="
        font-family: 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
        font-size: 16px;
        ">
            {!! $body !!}</div>

    </div>
</body>

</html>
