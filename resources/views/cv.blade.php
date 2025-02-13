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
<!DOCTYPE html> {{-- Add DOCTYPE for standards mode --}}
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $cv->name }} - Curriculum Vitae</title>
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        /* Reset and base styles (slightly adjusted) */
        body, html { /* Ensure html and body are 100% height if needed */
            height: auto !important; /* Override any potential height: 100% that can confuse DOMPDF */
        }
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
            color: #333;
            line-height: 1.5;
            padding: 20px;
            font-size: 14px; /* Base font size for body */
        }

        .cv-container {
            max-width: {{ $containerWidth }}px;
            margin: auto;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden; /* Changed from auto to hidden - slightly more robust BFC for floats */
            padding: 0px;
        }


        .sidebar {
            background-color: #2C3E50;
            color: #fff;
            width: 35%;
            float: left;
            padding: 20px; /* Ensure padding is on sidebar/main-content, not just container */
        }

        .main-content {
            width: 65%;
            float: left;
            padding: 20px; /* Ensure padding is on sidebar/main-content */
        }

        .profile-img {
            display: block;
            width: 140px;
            border-radius: 0%;
            border: 3px solid #fff;
            margin: 0 auto 15px auto;
        }


        .sidebar h2, .main-content .section h2 { /* Combined selectors for consistent heading styles */
            font-size: 22px;
            margin-bottom: 10px;
        }

        .sidebar h2 {
            text-align: center;
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
            border-bottom: 2px solid #e74c3c;
            padding-bottom: 5px;
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

        /* Clear floats - using micro clearfix */
        .clearfix:before,
        .clearfix:after {
            content: " ";
            display: table;
        }
        .clearfix:after {
            clear: both;
        }
        .clearfix {
            *zoom: 1; /* For IE 6/7 (Less relevant now, but doesn't hurt) */
        }


        .my-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            padding: 0;
            margin: 0;
        }

        .my-table tr td {
            padding: 0px;
            margin: 0px;
        }

        .hr-1 {
            border: 1.5px solid #10475a;
            padding: 0;
            margin: 10px 0; /* Added margin top and bottom for hr */
            display: block; /* Ensure hr is block-level */
        }


        .title-1 {
            font-size: 1.15em; /* Slightly larger for titles, using em for relative sizing */
            font-weight: bolder;
            color: #F5A509;
            margin-bottom: 10px;
            display: block; /* Ensure titles are block-level */
        }


        .table-label {
            width: 25%; /* Increased label width slightly */
            padding: 5px;
            font-weight: bold;
            text-transform: uppercase !important;
            display: table-cell; /* Ensure table cells are table-cell */
        }

        .table-value {
            width: 75%; /* Adjusted value width */
            padding: 5px 10px;
            background-color: #10475a;
            color: #fff;
            display: table-cell; /* Ensure table cells are table-cell */
        }

        /* Ensure block display for divs that should contain content */
        div, section, article, aside, nav, footer, header {
            display: block;
        }

        p {
            display: block; /* Ensure paragraphs are block-level */
            margin-bottom: 1em; /* Add default bottom margin to paragraphs for spacing */
        }

        ul, ol {
            display: block; /* Ensure lists are block-level */
            margin-bottom: 1em;
            padding-left: 20px; /* Default list indentation */
        }

        li {
            display: list-item; /* Ensure list items are list-item */
        }


    </style>
</head>

<body class="cv-container clearfix"> {{-- Applied clearfix to the container --}}

    <div style="display: block; width: 100%; overflow: hidden;"> {{-- Added wrapper div with overflow:hidden for initial table and info --}}
        <table class="my-table" style="width: 100%; float: left;"> {{-- First table floated left within wrapper --}}
            <tr>
                <td style="width: 150px; vertical-align: top;">
                    @if ($avatar_path != null)
                        <img src="{{ $avatar_path }}" alt="Profile Image" style="width: 150px;" class="profile-img">
                    @endif
                </td>
                <td class="pl-4" style="vertical-align: top; padding-left: 15px;">
                    <div style="border-right: 4px solid #10475a; padding-left: 0px;">
                        <p style="font-weight: bolder; font-size: 36px; color: #000;">{{ $cv->name }}</p>
                        <p style="font-weight: lighter!important; font-size: 20px; " class="text-uppercase">
                            {{ dp($cv->special_qualification) }}
                        </p>
                    </div>
                    <p class="title-1 mt-4 text-uppercase" style="margin-top: 15px !important;">Profile</p>
                    <hr class="hr-1">
                    <p>{{ $cv->objective }}</p>
                </td>
            </tr>
        </table>

        <div class="mr-3 mt-3" style="margin-right: 15px; margin-top: 15px; float: right; width: auto;"> {{-- Second info div floated right --}}
            <div style="background-color: #10475a; color: #fff; border-radius: 10px; padding: 15px;">
                <table style="border-collapse: collapse; width: 100%; color: #fff;">
                    <tr>
                        <td style="width: 33.33%; border: none;">
                            <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">Email Address:</p>
                            <p style="font-size: 14px; line-height: 1rem; font-weight: lighter; margin-bottom: 15px;">{{ dp($cv->email) }}</p>
                        </td>
                        <td style="width: 33.33%; border: none;">
                            <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">Phone number:</p>
                            <p style="font-size: 14px; line-height: 1rem; font-weight: lighter; margin-bottom: 15px;">{{ dp($cv->phone_number_1) }}</p>
                        </td>
                        <td style="width: 33.33%; border: none;">
                            <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">Nationality:</p>
                            <p style="font-size: 14px; line-height: 1rem; font-weight: lighter; margin-bottom: 15px;">{{ dp($cv->nationality) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 33.33%; border: none;">
                            <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">District:</p>
                            <p style="font-size: 14px; line-height: 1rem; font-weight: lighter; margin-bottom: 15px;">{{ dp($cv->district_text) }}</p>
                        </td>
                        <td style="width: 33.33%; border: none;">
                            <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">Address:</p>
                            <p style="font-size: 14px; line-height: 1rem; font-weight: lighter; margin-bottom: 15px;">{{ dp($cv->current_address) }}</p>
                        </td>
                        <td style="width: 33.33%; border: none;">
                            <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">Gender:</p>
                            <p style="font-size: 14px; line-height: 1rem; font-weight: lighter; margin-bottom: 15px;">{{ dp($cv->sex) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 33.33%; border: none;">
                            <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">Date of birth:</p>
                            <p style="font-size: 14px; line-height: 1rem; font-weight: lighter; margin-bottom: 0;">{{ dp($cv->date_of_birth) }}</p>
                        </td>
                        <td style="width: 33.33%; border: none;">
                            <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">Marital Status:</p>
                            <p style="font-size: 14px; line-height: 1rem; font-weight: lighter; margin-bottom: 0;">{{ dp($cv->marital_status) }}</p>
                        </td>
                        <td style="width: 33.33%; border: none;">
                            <p class="text-uppercase" style="font-weight: bolder; font-size: 12px; line-height: .8rem; color: #F5A509;">Religion:</p>
                            <p style="font-size: 14px; line-height: 1rem; font-weight: lighter; margin-bottom: 0;">{{ dp($cv->religion) }}</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>


    <div class="mt-3" style="margin-top: 15px; clear: both; display: block;"> {{-- Added clear:both and display:block --}}
        <table class="my-table" style="width: 100%;">
            <tr>
                <td class="table-label">Looking for:</td>
                <td class="table-value">{{ dp($cv->expected_job_level) }}</td>
                <td class="table-label">Available for:</td>
                <td class="table-value">{{ dp($cv->expected_job_nature) }}</td>
            </tr>
            <tr>
                <td class="table-label">Present Salary:</td>
                <td class="table-value">{{ dp($cv->present_salary) }}</td>
                <td class="table-label">Expected SALARY:</td>
                <td class="table-value">{{ dp($cv->expected_salary) }}</td>
            </tr>
        </table>
    </div>

    <div class="row mr-1" style="margin-right: 5px; display: block; clear: both;"> {{-- Added display:block and clear:both --}}
        <div class="col-12" style="display: block;">
            <p class="title-1 mt-3 text-uppercase m-0" style="margin-top: 15px !important;">career summary</p>
            <hr class="hr-1">
            <p>{{ $cv->career_summary }}</p>
        </div>
    </div>


    <div style="border-left: 3px solid #10475a; padding-left: 10px; margin-left: 23px; padding-bottom: 20px; display: block; clear: both;" class="pt-0 mt-0"> {{-- Added display:block and clear:both --}}
        <p class="title-1 mt-3 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;">
            <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 47px; height: 47px; text-align: center; vertical-align: middle;">
                <img class="mt-2" src="{{ public_path('assets/img/icons/briefcase.png') }}" style="width: 28px; height: 28px; margin-top: 8px;">
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">Employment History</span>
        </p>

        @if (count($employment_history) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">No Employment History</span>
                </p>
            </div>
        @else
            @foreach ($employment_history as $job)
                <div>
                    <p class="mt-3 mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                        <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; vertical-align: middle; border: 4px solid white;">
                        </span>
                        <span class="d-inline-block mb-0 ml-1" style="font-size: 18px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">{{ $job->position }}</span>
                    </p>
                    <div style="margin-left: 10px; line-height: 1.2; font-size: 14px; text-align: justify; display: block;">
                        <p style="font-size: 16px; margin-bottom: 5px;" class="mb-1">{{ $job->companyName }}</p>
                        <p class="mb-1" style="margin-bottom: 5px;"><b>FROM:</b> {{ $job->startDate }}, <b>FOR:</b> {{ (int) $job->employmentPeriod }} Year(s)</p>
                        <p class="mb-1" style="margin-bottom: 5px;"><b class="text-uppercase">Department:</b> {{ $job->department }} </p>
                        <p class="mb-1" style="margin-bottom: 5px;"><b class="text-uppercase">Responsibilities:</b> {{ $job->responsibilities }} </p>
                    </div>
                </div>
            @endforeach
        @endif

        <p class="title-1 mt-4 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;">
            <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 47px; height: 47px; text-align: center; vertical-align: middle;">
                <img class="mt-2" src="{{ public_path('assets/img/icons/education.png') }}" style="width: 30px; height: 30px; margin-top: 8px;">
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">Education</span>
        </p>


        @if (count($educations) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">No Education History</span>
                </p>
            </div>
        @else
            @foreach ($educations as $education)
                <div>
                    <p class="mt-3 mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                        <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; vertical-align: middle; border: 4px solid white;">
                        </span>
                        <span class="d-inline-block mb-0 ml-1 text-uppercase" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">
                            {{ $education->education_level }}
                        </span>
                    </p>
                    <div class="mt-0" style="margin-left: 10px; display: block;">
                        <table class="edu-table">
                            <tr>
                                <th>Institution</th>
                                <td>{{ $education->institution }}</td>
                            </tr>
                            <tr>
                                <th>Major</th>
                                <td>{{ $education->major }}</td>
                            </tr>
                            <tr>
                                <th>Duration</th>
                                <td>{{ $education->duration }}</td>
                            </tr>
                            <tr>
                                <th>Graduation Year</th>
                                <td>{{ $education->graduation_year }}</td>
                            </tr>
                            <tr>
                                <th>Result</th>
                                <td>{{ $education->result }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif


        <p class="title-1 mt-4 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;">
            <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 47px; height: 47px; text-align: center; vertical-align: middle;">
                <img class="mt-2" src="{{ public_path('assets/img/icons/trainings.png') }}" style="width: 30px; height: 30px; margin-top: 8px;">
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">Trainings</span>
        </p>

        @if (count($trainings) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">No Trainings Listed</span>
                </p>
            </div>
        @else
            @foreach ($trainings as $training)
                <div>
                    <p class="mt-3 mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                        <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; vertical-align: middle; border: 4px solid white;">
                        </span>
                        <span class="d-inline-block mb-0 ml-1 text-uppercase" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">
                            {{ $training->training_title }} ({{ $training->year }})
                        </span>
                    </p>
                    <div class="mt-0" style="margin-left: 10px; display: block;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                            <tr style="background-color: #f2f6f8;">
                                <th style="padding: 8px; text-align: left; color: #333; border: 1px solid #ddd;">Provider</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">{{ $training->provider }}</td>
                            </tr>
                            <tr style="background-color: #f8fbfc;">
                                <th style="padding: 8px; text-align: left; color: #333; border: 1px solid #ddd;">Remarks</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">{{ $training->remarks }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif


        <p class="title-1 mt-4 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;">
            <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 47px; height: 47px; text-align: center; vertical-align: middle;">
                <img src="{{ public_path('assets/img/icons/certificate.png') }}" style="width: 30px; height: 30px; margin-top: 12px;">
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">Certificates</span>
        </p>


        @if (count($seconday_schools) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">No Certificates Listed</span>
                </p>
            </div>
        @else
            @foreach ($seconday_schools as $certificate)
                <div>
                    <p class="mt-3 mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                        <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; vertical-align: middle; border: 4px solid white;">
                        </span>
                        <span class="d-inline-block mb-0 ml-1 text-uppercase" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">
                            {{ $certificate->certificate_title }} ({{ $certificate->date_issued }})
                        </span>
                    </p>
                    <div class="mt-0" style="margin-left: 10px; display: block;">
                        <table style="width: 100%; border-collapse: separate; border-spacing: 0 6px; font-size: 14px;">
                            <tr style="background-color: #10475a;">
                                <th style="padding: 8px; text-align: left; color: #fff;">Issuing Authority</th>
                                <td style="border: 1px solid #ddd; padding: 8px; background-color: #fff;">{{ $certificate->issuing_authority }}</td>
                            </tr>
                            <tr style="background-color: #10475a;">
                                <th style="padding: 8px; text-align: left; color: #fff;">Certificate ID</th>
                                <td style="border: 1px solid #ddd; padding: 8px; background-color: #fff;">{{ $certificate->certificate_id }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif


        <p class="title-1 mt-4 text-uppercase" style="margin-left: -35px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important;">
            <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 47px; height: 47px; text-align: center; vertical-align: middle;">
                <img class="mt-2" src="{{ public_path('assets/img/icons/accomplishment.png') }}" style="width: 30px; height: 30px; margin-top: 8px;">
            </span>
            <span class="d-inline-block mb-1 ml-1" style="font-size: 22px; margin-bottom: 5px; margin-left: 5px;">Accomplishments</span>
        </p>

        @if (count($accomplishments) == 0)
            <div>
                <p class="mt-4 mb-0 pb-0" style="margin-left: -23px!important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                    <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; border: 4px solid white;">
                    </span>
                    <span class="d-inline-block mb-0 ml-1" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">No Accomplishments Listed</span>
                </p>
            </div>
        @else
            @foreach ($accomplishments as $accomplishment)
                <div>
                    <p class="mt-3 mb-0 pb-0" style="margin-left: -23px!important; vertical-align: top !important; font-weight: bolder; margin-top: 15px !important; margin-bottom: 0 !important; padding-bottom: 0 !important;">
                        <span style="display: inline-block; background-color: #10475a; color: #fff; border-radius: 100%; width: 15px; height: 15px; text-align: center; vertical-align: middle; border: 4px solid white;">
                        </span>
                        <span class="d-inline-block mb-0 ml-1 text-uppercase" style="font-size: 16px; color: #10475a; margin-bottom: 0 !important; margin-left: 5px;">
                            {{ $accomplishment->title }} ({{ $accomplishment->issueDate }})
                        </span>
                    </p>
                    <div class="mt-0" style="margin-left: 10px; display: block;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                            <tr style="background-color: #f7f7f7;">
                                <th style="padding: 8px; text-align: left; color: #333;">Type</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">{{ $accomplishment->type }}</td>
                            </tr>
                            <tr style="background-color: #f7f7f7;">
                                <th style="padding: 8px; text-align: left; color: #333;">Description</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">{{ $accomplishment->description }}</td>
                            </tr>
                            <tr style="background-color: #f7f7f7;">
                                <th style="padding: 8px; text-align: left; color: #333;">URL</th>
                                <td style="padding: 8px; background-color: #fff; border: 1px solid #ddd;">
                                    <a href="{{ $accomplishment->url }}" target="_blank" style="color: blue; text-decoration: underline;">{{ $accomplishment->url }}</a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="row mr-1 mt-2" style="margin-right: 5px; margin-top: 8px; display: block; clear: both;"> {{-- Added display:block and clear:both --}}
        <div class="col-12" style="display: block;">
            <p class="title-1 mt-3 text-uppercase m-0" style="margin-top: 15px !important;">Special Skills</p>
            <hr class="hr-1">
            <p>{{ dp($cv->special_qualification) }}</p>
        </div>
    </div>

    <div class="row mr-1 mt-3" style="margin-right: 5px; margin-top: 15px; display: block; clear: both;"> {{-- Added display:block and clear:both --}}
        <div class="col-12" style="display: block;">
            <p class="title-1 mt-3 text-uppercase m-0" style="margin-top: 15px !important;">Languages</p>
            <hr class="hr-1">
            <p>{{ dp($cv->languages) }}</p>
        </div>
    </div>


</body>

</html>