{{-- resources/views/emails/creative_template.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet" />
</head>

<body
    style="font-family: 'Montserrat', sans-serif; background-color: #e3eff8; color: #333; line-height: 1.6; padding: 10px;">
    <div
        style="max-width: 600px; margin: 20px auto; background: #fff; border: 1px solid #e0e0e0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); overflow: hidden;">
        <div style="background: linear-gradient(90deg, #114786, #f33d02); padding: 15px; text-align: center;">
            <h1 style="font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 3px;">{{ env('APP_NAME') }}</h1>
            <p style="font-size: 12px; color: #fff; opacity: 0.9;">{{ $title }}</p>
        </div>
        <div style="padding: 20px;">
            {!! $body !!}
        </div>
        <div
            style="background: #fafafa; padding: 10px 15px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #e0e0e0;">
            <a href="#" style="color: #114786; text-decoration: none; margin: 0 6px; font-weight: 600;">About
                Us</a> |
            <a href="#" style="color: #114786; text-decoration: none; margin: 0 6px; font-weight: 600;">Privacy
                Policy</a> |
            <a href="#" style="color: #114786; text-decoration: none; margin: 0 6px; font-weight: 600;">Terms of
                Service</a> |
            <a href="#"
                style="color: #114786; text-decoration: none; margin: 0 6px; font-weight: 600;">Contact</a>
            <br />
            &copy; {{ date('Y') }} {{ env('APP_NAME') }}. All rights reserved.
        </div>
    </div>
</body>

</html>
