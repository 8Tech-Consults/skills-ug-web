<?php

namespace App\Models;

use Carbon\Carbon;
use Encore\Admin\Form\Field\BelongsToMany;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as RelationsBelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;



    public static function boot()
    {
        parent::boot();

        //created 
        self::created(function ($m) {
            try {
                User::save_cv($m);
            } catch (\Throwable $th) {
                // throw new \Exception($th->getMessage());
            }
        });
        self::updated(function ($m) {
            try {
                User::save_cv($m);
            } catch (\Throwable $th) {
                // throw new \Exception($th->getMessage());
            }
        });
        self::creating(function ($m) {

            $m->email = trim($m->email);
            if ($m->email != null && strlen($m->email) > 3) {
                if (!Utils::validateEmail($m->email)) {
                    throw new \Exception("Invalid email address");
                } else {
                    //check if email exists
                    $u = User::where('email', $m->email)->first();
                    if ($u != null) {
                        throw new \Exception("Email already exists");
                    }
                    //check if username exists
                    $u = User::where('username', $m->email)->first();
                    if ($u != null) {
                        throw new \Exception("Email as Username already exists");
                    }
                }
            }

            $n = $m->first_name . " " . $m->last_name;
            if (strlen(trim($n)) > 1) {
                $m->name = trim($n);
            }
            $m->username = $m->email;
            if ($m->password == null || strlen($m->password) < 2) {
                $m->password = password_hash('4321', PASSWORD_DEFAULT);
            }

            $username = null;
            $phone = trim($m->phone_number_1);
            if (strlen($phone) > 2) {
                $phone = Utils::prepare_phone_number($phone);
                if (Utils::phone_number_is_valid($phone)) {
                    $username = $phone;
                    $m->phone_number_1 = $phone;
                    //check if username exists
                    $u = User::where('phone_number_1', $phone)->first();
                    if ($u != null && $u->id != $m->id) {
                        throw new \Exception("Phone number already exists");
                    }
                    //check if username exists
                    $u = User::where('phone_number_2', $phone)->first();
                    if ($u != null && $u->id != $m->id) {
                        throw new \Exception("Phone number already exists as username.");
                    }
                }
            }

            //check if $username is null or empty
            if ($username == null) {
                //check if email is valid and set it as username using var_filter
                $email = trim($m->email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $username = $email;
                }
            }
            //check if $username is null or empty
            if ($username == null || strlen($username) < 2) {
                throw new \Exception("Invalid username.");
            }

            //check if username exists
            $u = User::where('username', $username)->first();
            if ($u != null) {
                throw new \Exception("Username already exists");
            }
            $m->username = $username;
            $m->verification = 'No';
        });


        self::updating(function ($m) {

            $m->email = trim($m->email);
            if ($m->email != null && strlen($m->email) > 3) {
                if (!Utils::validateEmail($m->email)) {
                    throw new \Exception("Invalid email address");
                } else {
                    //check if email exists
                    $u = User::where('email', $m->email)->first();
                    if ($u != null && $u->id != $m->id) {
                        throw new \Exception("Email already exists");
                    }
                    //check if username exists
                    $u = User::where('username', $m->email)->first();
                    if ($u != null && $u->id != $m->id) {
                        throw new \Exception("Email as Username already exists");
                    }
                }
            }

            $n = $m->first_name . " " . $m->last_name;
            if (strlen(trim($n)) > 1) {
                $m->name = trim($n);
            }
            $m->username = $m->email;
            if ($m->password == null || strlen($m->password) < 2) {
                $m->password = password_hash('4321', PASSWORD_DEFAULT);
            }

            $username = null;
            $phone = trim($m->phone_number_1);
            if (strlen($phone) > 2) {
                $phone = Utils::prepare_phone_number($phone);
                if (Utils::phone_number_is_valid($phone)) {
                    $username = $phone;
                    $m->phone_number_1 = $phone;
                    //check if username exists
                    $u = User::where('phone_number_1', $phone)->first();
                    if ($u != null && $u->id != $m->id) {
                        throw new \Exception("Phone number already exists");
                    }
                    //check if username exists
                    $u = User::where('phone_number_2', $phone)->first();
                    if ($u != null) {
                        throw new \Exception("Phone number already exists as username.");
                    }
                }
            }

            //check if $username is null or empty
            if ($username == null) {
                //check if email is valid and set it as username using var_filter
                $email = trim($m->email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $username = $email;
                }
            }
            //check if $username is null or empty
            if ($username == null || strlen($username) < 2) {
                throw new \Exception("Invalid username.");
            }

            //check if username exists
            $u = User::where('username', $username)->first();
            if ($u != null && $u->id != $m->id) {
                throw new \Exception("Username already exists");
            }
            $m->username = $username;
        });
    }


    public function password_reset_request()
    {
        $user = $this;

        // Check if a verification code was sent less than 3 minutes ago
        if ($user->code_is_sent === "Yes" && $user->code_sent_at !== null) {
            try {
                $code_sent_at = Carbon::parse($user->code_sent_at);
                if ($code_sent_at->diffInMinutes(Carbon::now()) < 3) {
                    throw new Exception("Please wait for 3 minutes before requesting a new verification code.");
                }
            } catch (\Throwable $th) {
                // Log the exception if needed, but don't prevent code generation
            }
        }

        // Generate a new verification code and update the send timestamp and flag
        $user->code = rand(100000, 999999);
        $user->code_is_sent = "Yes";
        $user->code_sent_at = Carbon::now();
        $user->save();

        // Check $user->email validity
        if (!Utils::validateEmail($user->email)) {
            throw new Exception("Invalid email address");
        }

        // Prepare email data
        $data = [
            'email' => $user->email,
            'name' => $user->name,
            'subject' => env('APP_NAME') . " - Password Reset Code",
            'code' => $user->code,
        ];

        try {
            $body = <<<EOF
            <div style="font-family: Arial, sans-serif;">
                <div style="margin: 0 auto; background-color: #ffffff;"> 
                    <p style="font-size: 16px; line-height: 1.6; color: #555555;">
                        Dear {$data['name']},
                    </p>
                    <p style="font-size: 16px; line-height: 1.6; color: #555555;">
                        You have requested to reset your password. Please use the verification code below to proceed.
                    </p>
                    <div style="text-align: center; margin: 30px 0;">
                        <strong style="font-size: 24px; color: #007bff; background-color: #e6f7ff; padding: 10px 20px; border-radius: 5px;">{$data['code']}</strong>
                    </div>
                    <p style="font-size: 16px; line-height: 1.6; color: #555555;">
                        This code will expire in 10 minutes. If you did not request a password reset, please ignore this email.
                    </p>
                    <hr style="border: 1px solid #e0e0e0; margin: 30px 0;">
                    <p style="font-size: 14px; color: #999999; text-align: center;">
                        This is an automated message, please do not reply.
                    </p>
                </div>
            </div>
            EOF;
            $data['body'] = $body;
            $data['view'] = 'mail-1'; // this is the view you are using for the email template.
            $data['data'] = $data['body'];

            Utils::mail_sender($data);
        } catch (\Throwable $th) {
            throw $th; // Re-throw the exception for handling elsewhere
        }
    }


    public function send_mail_verification_code()
    {
        $user = $this;

        // Check if a verification code was sent less than 3 minutes ago
        if ($user->code_is_sent === "Yes" && $user->code_sent_at !== null) {
            try {
                $code_sent_at = Carbon::parse($user->code_sent_at);
                if ($code_sent_at->diffInMinutes(Carbon::now()) < 3) {
                    throw new Exception("Please wait for 3 minutes before requesting a new verification code.");
                }
            } catch (\Throwable $th) {
                // Log the exception if needed, but don't prevent code generation
            }
        }

        // Generate a new verification code and update the send timestamp and flag
        $user->code = rand(100000, 999999);
        $user->code_is_sent = "Yes";
        $user->code_sent_at = Carbon::now();
        $user->save();

        // Check $user->email validity
        if (!Utils::validateEmail($user->email)) {
            throw new Exception("Invalid email address");
        }

        // Prepare email data
        $data = [
            'email' => $user->email,
            'name' => $user->name,
            'subject' => env('APP_NAME') . " - Email Verification Code",
            'code' => $user->code,
            'verification_url' => url('verify-email?code=' . $user->code),
        ];

        try {
            $body = <<<EOF
            <div style="font-family: Arial, sans-serif;  ">
                <div style=" margin: 0 auto; background-color: #ffffff;  "> 
                    <p style="font-size: 16px; line-height: 1.6; color: #555555;">
                        Dear {$data['name']},
                    </p>
                    <p style="font-size: 16px; line-height: 1.6; color: #555555;">
                        Please use the verification code below to confirm your email address.
                    </p>
                    <div style="text-align: center; margin: 30px 0;">
                        <strong style="font-size: 24px; color: #007bff; background-color: #e6f7ff; padding: 10px 20px; border-radius: 5px;">{$data['code']}</strong>
                    </div>
                    <p style="font-size: 16px; line-height: 1.6; color: #555555; text-align: center;">
                        Or, click the button below to verify your email:
                    </p>
                    <div style="text-align: center; margin: 20px 0;">
                        <a href="{$data['verification_url']}" style="background-color: #007bff; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Verify Email</a>
                    </div>
                    <p style="font-size: 16px; line-height: 1.6; color: #555555;">
                        If the button above doesn't work, copy and paste this URL into your browser:
                    </p>
                    <p style="font-size: 14px; line-height: 1.6; color: #777777; word-wrap: break-word;">
                        <a href="{$data['verification_url']}" style="color: #007bff; text-decoration: none;">{$data['verification_url']}</a>
                    </p>
                    <hr style="border: 1px solid #e0e0e0; margin: 30px 0;">
                    <p style="font-size: 14px; color: #999999; text-align: center;">
                        This is an automated message, please do not reply.
                    </p>
                </div>
            </div>
            EOF;
            $data['body'] = $body;
            $data['view'] = 'mail-1'; // this is the view you are using for the email template.
            $data['data'] = $data['body'];

            Utils::mail_sender($data);
        } catch (\Throwable $th) {
            throw $th; // Re-throw the exception for handling elsewhere
        }
    }



    public static function update_rating($id)
    {
        $user = User::find($id);
        /* $tasks = Task::where('assigned_to', $id)->get();
        $rate = 0;
        $count = 0;
        foreach ($tasks as $task) {
            if ($task->manager_submission_status != 'Not Submitted') {
                $rate += $task->rate;
                $count++;
            }
        }
        if ($count > 0) {
            $rate = $rate / $count;
        } */
        $work_load_pending = Task::where('assigned_to', $id)->where('manager_submission_status', 'Not Submitted')
            ->sum('hours');
        $work_load_completed = Task::where('assigned_to', $id)->where('manager_submission_status', 'Done')
            ->sum('hours');
        $user->work_load_pending = $work_load_pending;
        $user->work_load_completed = $work_load_completed;
        $user->save();
    }


    protected $table = "admin_users";

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }


    //appends
    protected $appends = ['short_name', 'district_text', 'preferred_job_category_text'];

    //getter for preferred_job_category_text
    public function getPreferredJobCategoryTextAttribute()
    {
        $category = JobCategory::find($this->preferred_job_category);
        if ($category == null) {
            return "";
        }
        return $category->name;
    }

    //get district text
    public function getDistrictTextAttribute()
    {
        $district = District::find($this->district_id);
        if ($district == null) {
            return "";
        }
        return $district->name;
    }

    public function getShortNameAttribute()
    {
        //in this formart - J. Doe from first_name and last_name
        if (strlen($this->first_name) > 1) {
            $letter_1 = substr($this->first_name, 0, 1);
        } else {
            $letter_1 = $this->first_name;
        }
        return $letter_1 . ". " . $this->last_name;
    }

    //get doctors list

    public static function get_doctors()
    {
        $users = [];
        foreach (
            User::where('company_id', 1)
                ->orderBy('name', 'asc')
                ->get() as $key => $value
        ) {
            $users[$value->id] = $value->name;
        }
        return $users;
    }

    //get card
    public function get_card()
    {
        if ($this->is_dependent == 'Yes') {
            $c = User::find($this->dependent_id);
            return $c;
        } else {
            return $this;
        }
    }

    //GET my roles
    public function get_my_roles()
    {
        $_roles = AdminRoleUser::where('user_id', $this->id)->get();
        $roles = [];
        foreach ($_roles as $key => $value) {
            $role = AdminRole::find($value->role_id);
            $roles[] = $role;
        }

        //check if $roles is empty and create one
        if (count($roles) < 1) {
            $adminRole = new AdminRoleUser();
            $adminRole->role_id = 2;
            $adminRole->user_id = $this->id;
            $adminRole->save();
        } else {
            return $roles;
        }

        $roles = [];
        $_roles = AdminRoleUser::where('user_id', $this->id)->get();
        foreach ($_roles as $key => $value) {
            $role = AdminRole::find($value->role_id);
            $roles[] = $role;
        }

        return [$roles];
    }

    public function get_employment_history(): array
    {
        if ($this->primary_school_name == null || strlen($this->primary_school_name) < 2) {
            return [];
        }
        $recs = [];
        try {
            $recs = json_decode($this->primary_school_name, true);
        } catch (\Throwable $th) {
            //throw $th;
        }
        $objects = [];
        foreach ($recs as $key => $value) {
            $objects[] = (object) $value;
        }
        return $objects;
    }
    public function get_education(): array
    {
        if ($this->degree_university_name == null || strlen($this->degree_university_name) < 2) {
            return [];
        }
        $recs = [];
        try {
            $recs = json_decode($this->degree_university_name, true);
        } catch (\Throwable $th) {
            //throw $th;
        }
        $objects = [];
        foreach ($recs as $key => $value) {
            $objects[] = (object) $value;
        }
        return $objects;
    }
    public function get_trainings(): array
    {
        if ($this->high_school_name == null || strlen($this->high_school_name) < 2) {
            return [];
        }
        $recs = [];
        try {
            $recs = json_decode($this->high_school_name, true);
        } catch (\Throwable $th) {
            //throw $th;
        }
        $objects = [];
        foreach ($recs as $key => $value) {
            $objects[] = (object) $value;
        }
        return $objects;
    }

    public function get_seconday_school(): array
    {
        if ($this->seconday_school_name == null || strlen($this->seconday_school_name) < 2) {
            return [];
        }
        $recs = [];
        try {
            $recs = json_decode($this->seconday_school_name, true);
        } catch (\Throwable $th) {
            //throw $th;
        }
        $objects = [];
        foreach ($recs as $key => $value) {
            $objects[] = (object) $value;
        }
        return $objects;
    }
    public function get_accomplishments(): array
    {
        if ($this->school_pay_payment_code == null || strlen($this->school_pay_payment_code) < 2) {
            return [];
        }
        $recs = [];
        try {
            $recs = json_decode($this->school_pay_payment_code, true);
        } catch (\Throwable $th) {
            //throw $th;
        }
        $objects = [];
        foreach ($recs as $key => $value) {
            $objects[] = (object) $value;
        }
        return $objects;
    }

    public static function save_cv($u)
    {

        //check most minimum fields
        if ($u->first_name == null || strlen($u->first_name) < 2) {
            return;
        }
        if ($u->last_name == null || strlen($u->last_name) < 2) {
            return;
        }
        //if mail not verified
        if ($u->verification != 'Yes') {
            return;
        }


        $old_path = $u->school_pay_account_id;

        if ($old_path != null && strlen($old_path) > 2) {
            $old_path = public_path('storage/' . $old_path);
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }

        $name_slug = \Str::slug($u->name);
        //date slug
        $name_slug = $name_slug . "-cv-" . date('Y-m-d') . "-";
        $path = "files/" . $name_slug . "-skills-ug" . '-' . $u->id . '-' . rand(1000000, 9999999) . ".pdf";


        $FullPath = public_path('storage/' . $path);
        //check if file exists
        if (file_exists($FullPath)) {
            unlink($FullPath);
        }
        $pdf = App::make('dompdf.wrapper');
        $pdf->set_option('enable_html5_parser', TRUE);
        if (isset($_GET['html'])) {
            return view('cv', [
                'cv' => $u,
            ]);
        }
        $pdf->loadHTML(view('cv', [
            'cv' => $u,
        ])->render());

        try {
            $pdf->save($FullPath);
        } catch (\Throwable $th) {
            throw new \Exception("Failed to save cv because " . $th->getMessage());
        }

        $sql = "update admin_users set school_pay_account_id = '$path' where id = " . $u->id;
        DB::update($sql);


        //
    }

    /**
     * Calculate the profile completion percentage based on important personal fields.
     *
     * Only fields that are considered important for a job-seeker's profile are included.
     * The final percentage is rounded to the nearest quarter (0, 25, 50, 75, or 100).
     *
     * @return int The profile completion percentage.
     */
    public function calculateProfileCompletion(): int
    {
        // List of important personal fields (adjust/add as needed)
        $fields = [
            'first_name',
            'last_name',
            'date_of_birth',
            'place_of_birth',
            'sex',
            'home_address',
            'current_address',
            'phone_number_1',
            'email',
            'nationality',
            'religion',
            'marital_status',
            'spouse_name',
            'languages',
            'emergency_person_name',
            'national_id_number',
            'passport_number',
            'tin',
            'nssf_number',
            'primary_school_name',
            'primary_school_year_graduated',
            'seconday_school_name', // or "secondary_school_name" if that is your naming convention
            'seconday_school_year_graduated',
            'high_school_name',
            'high_school_year_graduated',
            'degree_university_name',
            'degree_university_year_graduated',
            'masters_university_name',
            'masters_university_year_graduated',
            'phd_university_name',
            'phd_university_year_graduated',
            'given_name',
        ];

        $total = count($fields);
        $filled = 0;

        // Loop through each field and count if it is set and not empty
        foreach ($fields as $field) {
            if (isset($this->$field) && trim($this->$field) !== "") {
                $filled++;
            }
        }

        // Calculate raw percentage of completed fields
        $rawPercent = ($filled / $total) * 100;
        // Round to nearest quarter (25% increments)
        $roundedPercent = round($rawPercent / 25) * 25;

        return (int) $roundedPercent;
    }
}
