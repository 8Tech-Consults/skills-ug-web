@php
    // Layout dimensions
    $containerWidth = 800;
    $avatar_path = public_path('storage/' . $cv->avatar);
    if (isset($_GET['html'])) {
        $avatar_path = url('storage/' . $cv->avatar);
    }

@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $cv->name }} - Curriculum Vitae</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @include('css', [])
    <style>
        /* Reset some basic elements */
        body,
        h1,
        h2,
        h3,
        h4,
        h5,
        p,
        ul,
        li,
        table,
        th,
        td {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f2f2f2;
            color: #333;
            line-height: 1.5;
            padding: 20px;
        }

        .cv-container {
            max-width: {{ $containerWidth }}px;
            margin: auto;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: auto;
            padding: 0px;
            vertical-align: top !important;
        }

        .sidebar,
        .main-content {
            padding: 20px;
            vertical-align: top !important;
        }

        .sidebar {
            background-color: #2C3E50;
            color: #fff;
            width: 35%;
            float: left;
            vertical-align: top !important;
        }

        .main-content {
            width: 65%;
            float: left;
            vertical-align: top !important;
        }

        .profile-img {
            display: block;
            width: 140px;
            border-radius: 0%;
            border: 3px solid #fff;
            margin: 0 auto 15px auto;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar li {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .main-content .section {
            margin-bottom: 25px;
        }

        .main-content .section h2 {
            color: #e74c3c;
            margin-bottom: 10px;
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 5px;
        }

        .main-content p,
        .main-content li {
            font-size: 14px;
        }

        /* Education table style */
        .edu-table {
            width: 100%;
            border-collapse: collapse;
        }

        .edu-table th,
        .edu-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .edu-table th {
            background-color: #f7f7f7;
        }

        /* Clear floats */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .my-table tr td {
            padding: 0px;
            margin: 0px;
        }

        .hr-1 {
            border: 1px solid #ffb846;
            padding: 0;
            margin: 0;
        }

        .hr-2 {
            border: 2px solid #ffb846;
            padding: 0;
            margin: 0;
        }

        .title-1 {
            font-size: 24px;
            font-weight: bold;
            color: black;
            margin-bottom: 10px;
        }

        .title-2 {
            font-weight: bold;
            color: #ebe9e7;
            margin-bottom: 10px;
        }

        .text-1 {
            font-size: 16px !important;
            color: #414141 !important;
            text-align: justify !important;
            line-height: 1.2 !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }
    </style>
</head>

<body class="cv-container clearfix">
    <table class="my-table" style="width: 100%; border-collapse: collapse; border: none; padding: 0; margin: 0;">
        <tr>
            <td class="sidebar p-3" style="vertical-align: top;">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Enim, quidem deserunt reprehenderit veniam sunt
                ullam corporis illo vitae? Et quam atque voluptate dolorem ut placeat excepturi autem ullam cum minima?
            </td>
            <td style="vertical-align: top;">
                @for ($i = 0; $i < 100; $i++)
                    Lorem ipsum dolor sit amet consectetur adipisicing elit. Inventore ipsum incidunt voluptates dolorem
                    rem
                    quae, aut officia culpa! Totam eaque iure amet perferendis architecto esse enim dolorem dignissimos
                    sunt
                    omnis?
                @endfor
            </td>
        </tr>
    </table>
    </table>
    <table class="table my-table">
        @for ($i = 0; $i < 100; $i++)
            <tr>
                <td>
                    <p class="mb-">
                        Lorem, ipsum dolor sit amet consectetur adipisicing elit. Fugiat animi mollitia explicabo, ea
                        porro
                        maiores
                        ex,
                        laboriosam eaque modi, perferendis temporibus veniam nisi consectetur optio quae distinctio!
                        Deserunt,
                        eos
                        unde.
                    </p>
                </td>
            </tr>
        @endfor
    </table>

</body>
