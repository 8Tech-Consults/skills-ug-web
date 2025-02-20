{{-- resources/views/emails/creative_template.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }}</title>
    <!-- Import Google Font: Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet" />
</head>

<body style="margin:0; padding:0; background-color:#e3eff8; font-family:'Montserrat', sans-serif;">
    <!-- Wrapper Table -->
    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#e3eff8; padding:20px 0;">
        <tr>
            <td align="center">
                <!-- Email Container -->
                <table width="600" border="0" cellspacing="0" cellpadding="0"
                    style="background-color:#ffffff; border:1px solid #e0e0e0; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding:15px; background: linear-gradient(90deg, #114786, #f33d02);">
                            <h1 style="font-size:20px; font-weight:700; color:#ffffff; margin:0;">{{ env('APP_NAME') }}
                            </h1>
                            <p style="font-size:12px; color:#ffffff; opacity:0.9; margin:3px 0 0 0;">{{ $title }}
                            </p>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:20px; color:#424649; font-size:16px; line-height:1.6;">
                            {!! $body !!}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center"
                            style="padding:10px 15px; background-color:#fafafa; border-top:1px solid #e0e0e0; font-size:12px; color:#777;">
                            <a href="#"
                                style="color:#114786; text-decoration:none; margin:0 6px; font-weight:600;">About Us</a>
                            |
                            <a href="#"
                                style="color:#114786; text-decoration:none; margin:0 6px; font-weight:600;">Privacy
                                Policy</a> |
                            <a href="#"
                                style="color:#114786; text-decoration:none; margin:0 6px; font-weight:600;">Terms of
                                Service</a> |
                            <a href="#"
                                style="color:#114786; text-decoration:none; margin:0 6px; font-weight:600;">Contact</a>
                            <br />
                            &copy; {{ date('Y') }} {{ env('APP_NAME') }}. All rights reserved.
                        </td>
                    </tr>
                </table>
                <!-- End Email Container -->
            </td>
        </tr>
    </table>
</body>

</html>
