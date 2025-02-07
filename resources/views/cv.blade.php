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

        :root {
            --primary-color: #10475a;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            /* background-color: #f2f2f2; */
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
            border: 1.5px solid #10475a;
            padding: 0;
            margin: 0;
        }

        .hr-2 {
            border: 4px solid #10475a;
            padding: 0;
            margin: 0;
        }

        .title-1 {
            font-size: 16px;
            font-weight: bolder;
            color: #F5A509;
            margin-bottom: 10px;
        }

        .title-2 {
            font-weight: bold;
            color: #ebe9e7;
            margin-bottom: 10px;
        }

        .text-1 {
            font-size: 1px !important;
            color: #414141 !important;
            text-align: justify !important;
            line-height: 1.2 !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }

        .my-table tr td {
            padding: 0;
            margin: 0;
        }

        .table-label {
            width: 15%;
            padding: 5px;
            font-weight: bold;
            text-transform: uppercase !important;
        }

        .table-value {
            width: 30%;
            padding: 5px;
            background-color: #10475a;
            color: #fff;
            padding-left: 10px;
        }
    </style>
</head>
@php
    function dp($data)
    {
        $data = trim($data);
        if ($data == null || $data == '' || $data == 'null' || $data == 'NULL' || $data == 'Null') {
            return '-';
        }
        return $data;
    }
@endphp

<body class="cv-container ">
    <table class="my-table w-100" style="width: 100%; border-collapse: collapse; border: none; padding: 0; margin: 0;">
        <tr>
            <td style="width: 150px; vertical-align: top;">
                <img src="{{ $avatar_path }}" alt="Profile Image" style="width: 150px;" class="profile-img">
            </td>
            <td class="pl-4" style="vertical-align: top;">
                <div class="" style="border-right: 4px solid #10475a; padding-left: 0px;">
                    <p class=" " style="font-weight: bolder;  font-size: 36px;  color: #000;">{{ $cv->name }}
                    </p>
                    <p style="font-weight: lighter!important; font-size: 20px; " class="text-uppercase">
                        {{ dp($cv->special_qualification) }}
                    </p>
                </div>
                <p class="title-1 mt-4 text-uppercase">
                    Profile
                </p>
                <hr class="hr-1 ">
                <p>{{ $cv->objective }}</p>
            </td>
        </tr>
    </table>


    <div class="mr-3">
        <div style="margin-top: 0px; background-color: #10475a;  color: #fff; 
    width: 100%;
    border-radius: 10px;
 
    "
            class="pl-3 pr-3 py-3   ">
            <table style="border-collapse: collapse; width: 100%; color: #fff;">
                <tr>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase"
                            style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Email Address:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3">
                            {{ dp($cv->email) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase"
                            style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Phone number:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3">
                            {{ dp($cv->phone_number_1) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase"
                            style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Nationality:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3">
                            {{ dp($cv->nationality) }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase"
                            style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            District of residence:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3">
                            {{ dp($cv->district_text) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase"
                            style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Phone number:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3">
                            {{ dp($cv->phone_number_1) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase"
                            style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Nationality:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3">
                            {{ dp($cv->nationality) }}
                        </p>
                    </td>
                </tr>


            </table>
        </div>
    </div>

    <div class="mt-3">
        <table class="my-table w-100"
            style="width: 100%; border-collapse: collapse; border: none; padding: 0; margin: 0;">
            <tr>
                <td class="table-label">
                    lable 1:
                </td>
                <td class="pr-3 ">
                    <div class="  table-value pl-2 pt-1 pb-1 w-100">
                        value 1
                    </div>
                </td>
                <td class="table-label pl-3">
                    lable 2:
                </td>
                <td class=" ">
                    <div class="  table-value pl-2 pt-1 pb-1 w-100">
                        value 2
                    </div>
                </td>
            </tr>
            <tr class="">
                <td class="table-label">
                    <div class="mt-2 w-100">
                        lable 2:
                    </div>
                </td>
                <td class="pr-3 ">
                    <div class="mt-2 table-value pl-2 pt-1 pb-1 w-100">
                        value 2
                    </div>
                </td>
                <td class="table-label pl-3">
                    <div class="mt-2">
                        lable 3:
                    </div>
                </td>
                <td class="">
                    <div class="mt-2 table-value pl-2 pt-1 pb-1 w-100">
                        value 2
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="row mr-1">
        <div class="col-12 ">
            <p class="title-1 mt-3 text-uppercase m-0 ">
                career summary
            </p>
            <hr class="hr-1  ">
            <p>{{ $cv->career_summary }}</p>

        </div>
    </div>

    <div style="border-left: 3px solid #10475a; padding-left: 10px; margin-left: 23px; padding-bottom: 20px;"
        class="pt-0 mt-0">
        <p class="title-1 mt-3 text-uppercase"
            style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder">
            <span
                style="display: inline-block;  background-color: #10475a; color: #fff;  border-radius: 100%;
                width: 47px; height: 47px; text-align: center; vertical-align: middle; 
                ">
                <img class="mt-2" src="{{ public_path('assets/img/icons/briefcase.png') }}"
                    style="width: 28px; height: 28px; ">
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px;"> Work
                Experience</span>
        </p>

        <div>
            <p class="mt-3  mb-0 pb-0"
                style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder">
                <span
                    style="display: inline-block;  background-color: #10475a; color: #fff;  border-radius: 100%;
            width:  15px; height: 15px; text-align: center; vertical-align: middle; 
            border: 4px solid white; 

            ">
                </span>
                <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a;">
                    Company name one #1</span>
            </p>
            <div style="margin-left: 10px; line-height: 1.2; font-size: 14px; text-align: justify;" class="mt-0">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Magnam rem architecto debitis dolore quam
                eligendi
                ab doloremque dolores soluta natus minima consequatur veritatis ipsam, consequuntur deserunt unde!
                Officiis,
                sequi ipsam?
            </div>
        </div>
        <div>
            <p class="mt-3  mb-0 pb-0"
                style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder">
                <span
                    style="display: inline-block;  background-color: #10475a; color: #fff;  border-radius: 100%;
            width:  15px; height: 15px; text-align: center; vertical-align: middle; 
            border: 4px solid white; 

            ">
                </span>
                <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a;">
                    Company name one #1</span>
            </p>
            <div style="margin-left: 10px; line-height: 1.2; font-size: 14px; text-align: justify;" class="mt-0">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Magnam rem architecto debitis dolore quam
                eligendi
                ab doloremque dolores soluta natus minima consequatur veritatis ipsam, consequuntur deserunt unde!
                Officiis,
                sequi ipsam?
            </div>
        </div>
        <div>
            <p class="mt-3  mb-0 pb-0"
                style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder">
                <span
                    style="display: inline-block;  background-color: #10475a; color: #fff;  border-radius: 100%;
            width:  15px; height: 15px; text-align: center; vertical-align: middle; 
            border: 4px solid white; 

            ">
                </span>
                <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a;">
                    Company name one #1</span>
            </p>
            <div style="margin-left: 10px; line-height: 1.2; font-size: 14px; text-align: justify;" class="mt-0">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Magnam rem architecto debitis dolore quam
                eligendi
                ab doloremque dolores soluta natus minima consequatur veritatis ipsam, consequuntur deserunt unde!
                Officiis,
                sequi ipsam?
            </div>
        </div>

        <p class="title-1 mt-4 text-uppercase"
            style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder">
            <span
                style="display: inline-block;  background-color: #10475a; color: #fff;  border-radius: 100%;
            width: 47px; height: 47px; text-align: center; vertical-align: middle; 
            ">
                <img class="mt-2" src="{{ public_path('assets/img/icons/education.png') }}"
                    style="width: 30px; height: 30px; ">
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px;"> Education & Trainings</span>
        </p>

        <div>
            <p class="mt-3  mb-0 pb-0"
                style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder">
                <span
                    style="display: inline-block;  background-color: #10475a; color: #fff;  border-radius: 100%;
            width:  15px; height: 15px; text-align: center; vertical-align: middle; 
            border: 4px solid white; 

            ">
                </span>
                <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a;">
                    Company name one #1</span>
            </p>
            <div style="margin-left: 10px; line-height: 1.2; font-size: 14px; text-align: justify;" class="mt-0">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Magnam rem architecto debitis dolore quam
                eligendi
                ab doloremque dolores soluta natus minima consequatur veritatis ipsam, consequuntur deserunt unde!
                Officiis,
                sequi ipsam?
            </div>
        </div>
        <div>
            <p class="mt-3  mb-0 pb-0"
                style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder">
                <span
                    style="display: inline-block;  background-color: #10475a; color: #fff;  border-radius: 100%;
            width:  15px; height: 15px; text-align: center; vertical-align: middle; 
            border: 4px solid white; 

            ">
                </span>
                <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a;">
                    Company name one #1</span>
            </p>
            <div style="margin-left: 10px; line-height: 1.2; font-size: 14px; text-align: justify;" class="mt-0">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Magnam rem architecto debitis dolore quam
                eligendi
                ab doloremque dolores soluta natus minima consequatur veritatis ipsam, consequuntur deserunt unde!
                Officiis,
                sequi ipsam?
            </div>
        </div>


        <p class="title-1 mt-4 text-uppercase"
            style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder">
            <span
                style="display: inline-block;  background-color: #10475a; color: #fff;  border-radius: 100%;
        width: 47px; height: 47px; text-align: center; vertical-align: middle; 
        ">
                <img class="mt-2" src="{{ public_path('assets/img/icons/accomplishment.png') }}"
                    style="width: 30px; height: 30px; ">
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px;">Accomplishments</span>
        </p>

        <div>
            <p class="mt-3  mb-0 pb-0"
                style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder">
                <span
                    style="display: inline-block;  background-color: #10475a; color: #fff;  border-radius: 100%;
        width:  15px; height: 15px; text-align: center; vertical-align: middle; 
        border: 4px solid white; 

        ">
                </span>
                <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a;">
                    Company name one #1</span>
            </p>
            <div style="margin-left: 10px; line-height: 1.2; font-size: 14px; text-align: justify;" class="mt-0">
                Lorem ipsum dolor sit amet consectetur adipisicing elit. Magnam rem architecto debitis dolore quam
                eligendi
                ab doloremque dolores soluta natus minima consequatur veritatis ipsam, consequuntur deserunt unde!
                Officiis,
                sequi ipsam?
            </div>
        </div>
    </div>
    <div class="row mr-1 mt-2">
        <div class="col-12 ">
            <p class="title-1 mt-3 text-uppercase m-0 ">
                Skills
            </p>
            <hr class="hr-1  ">
            <p>{{ $cv->career_summary }}</p>

        </div>
    </div>
    <div class="row mr-1 mt-3">
        <div class="col-12 ">
            <p class="title-1 mt-3 text-uppercase m-0 ">
                Languages
            </p>
            <hr class="hr-1  ">
            <p>{{ $cv->career_summary }}</p>

        </div>
    </div>


</body>
