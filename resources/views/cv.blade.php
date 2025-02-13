@php
    $employment_history = $cv->get_employment_history();
    $educations = $cv->get_education();
    $trainings = $cv->get_trainings();
    $seconday_schools = $cv->get_seconday_school();
    $accomplishments = $cv->get_accomplishments();

    $containerWidth = 800;
    $avatar_path = public_path('storage/' . $cv->avatar);

    if (strlen($cv->avatar) < 4 || !file_exists($avatar_path)) {
        $avatar_path = public_path('assets/img/user-1.png');
    }

    if (!file_exists($avatar_path)) {
        $avatar_path = null;
    }

    function dp($data)
    {
        $data = trim($data);
        if ($data == null || $data == '' || $data == 'null' || $data == 'NULL' || $data == 'Null') {
            return '-';
        }
        return $data;
    }
@endphp
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $cv->name }} - Curriculum Vitae</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    {{-- @include('css', []) --}} {{-- Assuming you have a css blade file, if not remove this line --}}
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

        .clearfix { /* Add clearfix to cv-container */
            overflow: auto; /* Or overflow: hidden;  This also can help establish BFC */
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

<body class="cv-container clearfix"> {{-- ADDED clearfix class here --}}
    <table class="my-table w-100" style="width: 100%; border-collapse: collapse; border: none; padding: 0; margin: 0;">
        <tr>
            <td style="width: 150px; vertical-align: top;">
                @if ($avatar_path != null)
                    <img src="{{ $avatar_path }}" alt="Profile Image" style="width: 150px;" class="profile-img">
                @endif
            </td>
            <td class="pl-4" style="vertical-align: top; padding-left: 15px;"> {{-- Added padding-left for class pl-4 --}}
                <div class="" style="border-right: 4px solid #10475a; padding-left: 0px;">
                    <p class=" " style="font-weight: bolder;   font-size: 36px;   color: #000;">{{ $cv->name }}
                    </p>
                    <p style="font-weight: lighter!important; font-size: 20px; " class="text-uppercase">
                        {{ dp($cv->special_qualification) }}
                    </p>
                </div>
                <p class="title-1 mt-4 text-uppercase" style="margin-top: 15px !important;"> {{-- Added margin-top for class mt-4 --}}
                    Profile
                </p>
                <hr class="hr-1 ">
                <p>{{ $cv->objective }}</p>
            </td>
        </tr>
    </table>


    <div class="mr-3 mt-3" style="margin-right: 15px; margin-top: 15px;"> {{-- Added margin-right and margin-top for classes mr-3 and mt-3 --}}
        <div style="  background-color: #10475a;   color: #fff;
    width: 100%;
    border-radius: 10px;

    " class="pl-3 pr-3 py-3    " style="padding-left: 15px; padding-right: 15px; padding-top: 15px; padding-bottom: 15px;"> {{-- Added padding for classes pl-3, pr-3 and py-3 --}}
            <table style="border-collapse: collapse; width: 100%; color: #fff;">
                <tr>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Email Address:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3" style="margin-bottom: 15px;"> {{-- Added margin-bottom for class mb-3 --}}
                            {{ dp($cv->email) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Phone number:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3" style="margin-bottom: 15px;"> {{-- Added margin-bottom for class mb-3 --}}
                            {{ dp($cv->phone_number_1) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Nationality:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3" style="margin-bottom: 15px;"> {{-- Added margin-bottom for class mb-3 --}}
                            {{ dp($cv->nationality) }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            District of residence:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3" style="margin-bottom: 15px;"> {{-- Added margin-bottom for class mb-3 --}}
                            {{ dp($cv->district_text) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Address:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3" style="margin-bottom: 15px;"> {{-- Added margin-bottom for class mb-3 --}}
                            {{ dp($cv->current_address) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Gender:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-3" style="margin-bottom: 15px;"> {{-- Added margin-bottom for class mb-3 --}}
                            {{ dp($cv->sex) }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Date of birth:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-0">
                            {{ dp($cv->date_of_birth) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Marital Status:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-0">
                            {{ dp($cv->marital_status) }}
                        </p>
                    </td>
                    <td style="width: 33.33%; border: none;">
                        <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">
                            Religion:</p>
                        <p style="font-size: 14px; line-height: 1rem; font-weight: lighter;" class="mb-0">
                            {{ dp($cv->religion) }}
                        </p>
                    </td>
                </tr>


            </table>
        </div>
    </div>

    <div class="mt-3" style="margin-top: 15px;"> {{-- Added margin-top for class mt-3 --}}
        <table class="my-table w-100" style="width: 100%; border-collapse: collapse; border: none; padding: 0; margin: 0;">
            <tr>
                <td class="table-label" style="font-size: 12px; line-height: 1rem;">
                    Looking for:
                </td>
                <td class="pr-3 " style="padding-right: 15px;"> {{-- Added padding-right for class pr-3 --}}
                    <div class="   table-value pl-2 pt-0 pb-1 w-100" style="font-size: 14px; padding-left: 10px; padding-top: 0px; padding-bottom: 5px;"> {{-- Added padding for classes pl-2, pt-0, pb-1 --}}
                        {{ dp($cv->expected_job_level) }}
                    </div>
                </td>
                <td class="table-label pl-3" style="font-size: 12px; line-height: .9rem; padding-left: 15px;"> {{-- Added padding-left for class pl-3 --}}
                    Available for:
                </td>
                <td class=" ">
                    <div class="   table-value pl-2 pt-0 pb-1 w-100" style="font-size: 14px; padding-left: 10px; padding-top: 0px; padding-bottom: 5px;"> {{-- Added padding for classes pl-2, pt-0, pb-1 --}}
                        {{ dp($cv->expected_job_nature) }}
                    </div>
                </td>
            </tr>
            <tr class="">
                <td class="table-label">
                    <div class="mt-2 w-100" style="font-size: 12px; line-height: 1rem; margin-top: 8px;"> {{-- Added margin-top for class mt-2 --}}
                        Present Salary:
                    </div>
                </td>
                <td class="pr-3 " style="font-size: 12px; padding-right: 15px;"> {{-- Added padding-right for class pr-3 --}}
                    <div class="mt-2 table-value pl-2 pt-1 pb-1 w-100" style="font-size: 12px; margin-top: 8px; padding-left: 10px; padding-top: 5px; padding-bottom: 5px;"> {{-- Added padding and margin for classes mt-2, pl-2, pt-1, pb-1 --}}
                        {{ dp($cv->present_salary) }}
                    </div>
                </td>
                <td class="table-label pl-3" style="font-size: 12px; line-height: .8rem; padding-left: 15px;"> {{-- Added padding-left for class pl-3 --}}
                    <div class="mt-2" style="margin-top: 8px;"> {{-- Added margin-top for class mt-2 --}}
                        Expected SALARY:
                    </div>
                </td>
                <td class="">
                    <div class="mt-2 table-value pl-2 pt-1 pb-1 w-100" style="font-size: 12px; margin-top: 8px; padding-left: 10px; padding-top: 5px; padding-bottom: 5px;"> {{-- Added padding and margin for classes mt-2, pl-2, pt-1, pb-1 --}}
                        {{ dp($cv->expected_salary) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="row mr-1" style="margin-right: 5px;"> {{-- Added margin-right for class mr-1 --}}
        <div class="col-12 ">
            <p class="title-1 mt-3 text-uppercase m-0 " style="margin-top: 15px !important; margin-bottom: 0 !important;"> {{-- Added margin-top and margin-bottom for classes mt-3 and m-0 --}}
                career summary
            </p>
            <hr class="hr-1  ">
            <p>{{ $cv->career_summary }}</p>

        </div>
    </div>

    <div style="border-left: 3px solid #10475a; padding-left: 10px; margin-left: 23px; padding-bottom: 20px;" class="pt-0 mt-0" style="padding-top: 0 !important; margin-top: 0 !important;"> {{-- Added padding reset for classes pt-0 --}}
        <p class="title-1 mt-3 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;"> {{-- Added margin-top for class mt-3 --}}
            <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
        width: 47px; height: 47px; text-align: center; vertical-align: middle;
        ">
                <img class="mt-2" src="{{ public_path('assets/img/icons/briefcase.png') }}" style="width: 28px; height: 28px; margin-top: 8px;"> {{-- Added margin-top for class mt-2 --}}
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">{{-- Added margin-bottom for class mb-1 --}}Employment History</span>
        </p>

        {{-- if $employment_history is empty --}}
        @if (count($employment_history) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-4, mb-0, pb-0 --}}
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%;
                    width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                        No Employment History
                    </span>
                </p>
            </div>
        @else
            @foreach ($employment_history as $job)
                <div>
                    <p class="mt-3   mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-3, mb-0, pb-0 --}}
                        <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
                        width:  15px; height: 15px; text-align: center; vertical-align: middle;
                        border: 4px solid white;
                        ">
                        </span>
                        <span class="d-inline-block mb-0 ml-1" style="font-size: 18px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                            {{ $job->position }}</span>
                    </p>
                    <div style="margin-left: 10px; line-height: 1.2; font-size: 14px; text-align: justify;" class="mt-0" style="margin-top: 0 !important;"> {{-- Added margin reset for class mt-0 --}}
                        <p style="font-size: 16px;   margin-bottom: 5px;" class="mb-1"> {{-- Added margin-bottom for class mb-1 --}} {{ $job->companyName }}</p>
                        <p class="mb-1" style="margin-bottom: 5px;"><b>FROM:</b> {{ $job->startDate }}, <b>FOR:
                            </b>{{ (int) $job->employmentPeriod }} Year(s)</p>
                        <p class="mb-1" style="margin-bottom: 5px;"><b class="text-uppercase">Department:</b> {{ $job->department }} </p>
                        <p class="mb-1" style="margin-bottom: 5px;"><b class="text-uppercase">Responsibilities:</b>
                            {{ $job->responsibilities }} </p>


                    </div>
                </div>
            @endforeach
        @endif

        <p class="title-1 mt-4 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;"> {{-- Added margin-top for class mt-4 --}}
            <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
        width: 47px; height: 47px; text-align: center; vertical-align: middle;
        ">
                <img class="mt-2" src="{{ public_path('assets/img/icons/education.png') }}" style="width: 30px; height: 30px; margin-top: 8px;"> {{-- Added margin-top for class mt-2 --}}
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">{{-- Added margin-bottom for class mb-1 --}} Education</span>
        </p>

        {{-- if $educations is empty --}}
        @if (count($educations) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-4, mb-0, pb-0 --}}
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%;
                width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                        No Education History
                    </span>
                </p>
            </div>
        @else
            @foreach ($educations as $education)
                <div>
                    <p class="mt-3   mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-3, mb-0, pb-0 --}}
                        <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
                width:  15px; height: 15px; text-align: center; vertical-align: middle;
                border: 4px solid white;">
                        </span>
                        <span class="d-inline-block mb-0 ml-1 text-uppercase" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                            {{ $education->education_level }}
                        </span>
                    </p>
                    <div class="mt-0" style="margin-left: 10px; margin-top: 0 !important;"> {{-- Added margin reset for class mt-0 --}}
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <tr>
                                <th style="background-color: #f7f7f7; padding: 8px; text-align: left;">Institution</th>
                                <td style="border: 1px solid #ddd; padding: 8px;">{{ $education->institution }}</td>
                            </tr>
                            <tr>
                                <th style="background-color: #f7f7f7; padding: 8px; text-align: left;">Major</th>
                                <td style="border: 1px solid #ddd; padding: 8px;">{{ $education->major }}</td>
                            </tr>
                            <tr>
                                <th style="background-color: #f7f7f7; padding: 8px; text-align: left;">Duration</th>
                                <td style="border: 1px solid #ddd; padding: 8px;">{{ $education->duration }}</td>
                            </tr>
                            <tr>
                                <th style="background-color: #f7f7f7; padding: 8px; text-align: left;">Graduation Year
                                </th>
                                <td style="border: 1px solid #ddd; padding: 8px;">{{ $education->graduation_year }}
                                </td>
                            </tr>
                            <tr>
                                <th style="background-color: #f7f7f7; padding: 8px; text-align: left;">Result</th>
                                <td style="border: 1px solid #ddd; padding: 8px;">{{ $education->result }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif

        <p class="title-1 mt-4 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;"> {{-- Added margin-top for class mt-4 --}}
            <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
        width: 47px; height: 47px; text-align: center; vertical-align: middle;">
                <img class="mt-2" src="{{ public_path('assets/img/icons/trainings.png') }}" style="width: 30px; height: 30px; margin-top: 8px;"> {{-- Added margin-top for class mt-2 --}}
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">{{-- Added margin-bottom for class mb-1 --}}Trainings</span>
        </p>
        @if (count($trainings) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-4, mb-0, pb-0 --}}
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%;
                    width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                        No Trainings Listed
                    </span>
                </p>
            </div>
        @else
            @foreach ($trainings as $training)
                <div>
                    <p class="mt-3 mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-3, mb-0, pb-0 --}}
                        <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
                                                width: 15px; height: 15px; text-align: center; vertical-align: middle;
                                                border: 4px solid white;">
                        </span>
                        <span class="d-inline-block mb-0 ml-1 text-uppercase" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                            {{ $training->training_title }} ({{ $training->year }})
                        </span>
                    </p>
                    <div class="mt-0" style="margin-left: 10px; margin-top: 0 !important;"> {{-- Added margin reset for class mt-0 --}}
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <tr style="background-color: #f2f6f8;">
                                <th style="padding: 8px; text-align: left; color: #333; border: 1px solid #ddd;">
                                    Provider</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">
                                    {{ $training->provider }}
                                </td>
                            </tr>
                            <tr style="background-color: #f8fbfc;">
                                <th style="padding: 8px; text-align: left; color: #333; border: 1px solid #ddd;">
                                    Remarks</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">
                                    {{ $training->remarks }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif

        <p class="title-1 mt-4 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;"> {{-- Added margin-top for class mt-4 --}}
            <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
        width: 47px; height: 47px; text-align: center; vertical-align: middle;">
                <img src="{{ public_path('assets/img/icons/certificate.png') }}" style="width: 30px; height: 30px; margin-top: 12px;"> {{-- Added margin-top for specific style --}}
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">{{-- Added margin-bottom for class mb-1 --}}Certificates</span>
        </p>
        @if (count($seconday_schools) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-4, mb-0, pb-0 --}}
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%;
                    width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                        No Certificates Listed
                    </span>
                </p>
            </div>
        @else
            @foreach ($seconday_schools as $certificate)
                <div>
                    <p class="mt-3 mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-3, mb-0, pb-0 --}}
                        <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
                                                    width: 15px; height: 15px; text-align: center; vertical-align: middle;
                                                    border: 4px solid white;">
                        </span>
                        <span class="d-inline-block mb-0 ml-1 text-uppercase" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                            {{ $certificate->certificate_title }} ({{ $certificate->date_issued }})
                        </span>
                    </p>
                    <div class="mt-0" style="margin-left: 10px; margin-top: 0 !important;"> {{-- Added margin reset for class mt-0 --}}
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0 6px; font-size: 14px;">
                            <tr style="background-color: #10475a;">
                                <th style="padding: 8px; text-align: left; color: #fff;">Issuing Authority</th>
                                <td style="border: 1px solid #ddd; padding: 8px; background-color: #fff;">
                                    {{ $certificate->issuing_authority }}
                                </td>
                            </tr>
                            <tr style="background-color: #10475a;">
                                <th style="padding: 8px; text-align: left; color: #fff;">Certificate ID</th>
                                <td style="border: 1px solid #ddd; padding: 8px; background-color: #fff;">
                                    {{ $certificate->certificate_id }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif


        <p class="title-1 mt-4 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;"> {{-- Added margin-top for class mt-4 --}}
            <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
    width: 47px; height: 47px; text-align: center; vertical-align: middle;
    ">
                <img class="mt-2" src="{{ public_path('assets/img/icons/accomplishment.png') }}" style="width: 30px; height: 30px; margin-top: 8px;"> {{-- Added margin-top for class mt-2 --}}
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">{{-- Added margin-bottom for class mb-1 --}}Accomplishments</span>
        </p>

        @if (count($accomplishments) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-4, mb-0, pb-0 --}}
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%;
                    width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                        No Accomplishments Listed
                    </span>
                </p>
            </div>
        @else
            @foreach ($accomplishments as $accomplishment)
                <div>
                    <p class="mt-3 mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;"> {{-- Added margin reset for classes mt-3, mb-0, pb-0 --}}
                        <span style="display: inline-block;   background-color: #10475a; color: #fff;   border-radius: 100%;
                                                    width: 15px; height: 15px; text-align: center; vertical-align: middle;
                                                    border: 4px solid white;">
                        </span>
                        <span class="d-inline-block mb-0 ml-1 text-uppercase" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{-- Added margin-bottom for class mb-0 --}}
                            {{ $accomplishment->title }} ({{ $accomplishment->issueDate }})
                        </span>
                    </p>
                    <div class="mt-0" style="margin-left: 10px; margin-top: 0 !important;"> {{-- Added margin reset for class mt-0 --}}
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                            <tr style="background-color: #f7f7f7;">
                                <th style="padding: 8px; text-align: left; color: #333;">Type</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">
                                    {{ $accomplishment->type }}
                                </td>
                            </tr>
                            <tr style="background-color: #f7f7f7;">
                                <th style="padding: 8px; text-align: left; color: #333;">Description</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">
                                    {{ $accomplishment->description }}
                                </td>
                            </tr>
                            <tr style="background-color: #f7f7f7;">
                                <th style="padding: 8px; text-align: left; color: #333;">URL</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">
                                    <a href="{{ $accomplishment->url }}" target="_blank" style="color: blue; text-decoration: underline;">{{ $accomplishment->url }}</a> {{--Used inline style for link --}}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
    <div class="row mr-1 mt-2" style="margin-right: 5px; margin-top: 8px;"> {{-- Added margin for classes mr-1 and mt-2 --}}
        <div class="col-12 ">
            <p class="title-1 mt-3 text-uppercase m-0 " style="margin-top: 15px !important; margin-bottom: 0 !important;"> {{-- Added margin-top for classes mt-3 and m-0 --}}
                Special Skills
            </p>
            <hr class="hr-1  ">
            <p>{{ dp($cv->special_qualification) }}</p>

        </div>
    </div>
    <div class="row mr-1 mt-3" style="margin-right: 5px; margin-top: 15px;"> {{-- Added margin for classes mr-1 and mt-3 --}}
        <div class="col-12 ">
            <p class="title-1 mt-3 text-uppercase m-0 " style="margin-top: 15px !important; margin-bottom: 0 !important;"> {{-- Added margin-top for classes mt-3 and m-0 --}}
                Languages
            </p>
            <hr class="hr-1  ">
            <p>{{ dp($cv->languages) }}</p>

        </div>
    </div>


</body>

</html>