<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminRoleUser;
use App\Models\Consultation;
use App\Models\DoseItem;
use App\Models\DoseItemRecord;
use App\Models\FlutterWaveLog;
use App\Models\Image;
use App\Models\LaundryOrder;
use App\Models\LaundryOrderItem;
use App\Models\LaundryOrderItemType;
use App\Models\Meeting;
use App\Models\PaymentRecord;
use App\Models\Project;
use App\Models\Service;
use App\Models\Task;
use App\Models\Trip;
use App\Models\User;
use App\Models\Utils;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiAuthController extends Controller
{

    use ApiResponser;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {

        /* $token = auth('api')->attempt([
            'username' => 'admin',
            'password' => 'admin',
        ]);
        die($token); */
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $query = auth('api')->user();
        return $this->success($query, $message = "Profile details", 200);
    }


    public function users()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }

        $admin_user_roles = AdminRoleUser::wherein('role_id', [1, 3, 4])
            ->get()
            ->pluck('user_id')
            ->toArray();

        $users = User::wherein('id', $admin_user_roles)
            ->get();

        return $this->success($users, $message = "Success", 200);
    }

    public function my_roles()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }
        $u = User::find($u->id);
        if ($u == null) {
            return $this->error('Account not found');
        }
        $roles = $u->get_my_roles();
        return $this->success($roles, $message = "Success", 200);
        return $this->success($u->get_my_roles(), $message = "Success", 200);
    }

    public function laundry_orders()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }

        $orders = [];
        //admin
        //customer
        //driver
        //washer

        //if admin
        if ($u->isRole('admin')) {
            $orders = LaundryOrder::where([])
                ->get();
        }

        //if customer
        if ($u->isRole('customer')) {
            $orders = LaundryOrder::where([
                'user_id' => $u->id
            ])
                ->get();
        }

        //if driver
        if ($u->isRole('driver')) {
            $orders = LaundryOrder::where([
                'driver_id' => $u->id
            ])
                ->orWhere([
                    'delivery_driver_id' => $u->id
                ])
                ->get();
        }

        //if washer
        if ($u->isRole('washer')) {
            $orders[] = LaundryOrder::where([
                'washer_id' => $u->id
            ])
                ->get();
        }


        return $this->success($orders, $message = "Success", 200);
    }




    public function trips()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }

        $orders = [];
        //admin
        //customer
        //driver
        //washer

        //if driver
        if ($u->isRole('driver')) {
            $orders = Trip::where([
                'driver_id' => $u->id
            ])
                ->get();
        }

        //if admin
        if ($u->isRole('admin')) {
            $orders = Trip::where([])
                ->get();
        }

        return $this->success($orders, $message = "Success", 200);
    }



    public function projects()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }
        return $this->success(Project::where([
            'company_id' => $u->company_id
        ])
            ->get(), $message = "Success =>{$u->company_id}<=", 200);
    }

    public function services()
    {
        return $this->success(Service::all(), $message = "Success", 200);
    }
    public function dose_item_records()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }
        $my_consultations = Consultation::where([
            'patient_id' => $u->id
        ])->get();
        $consultation_ids = [];
        foreach ($my_consultations as $key => $value) {
            $consultation_ids[] = $value->id;
        }
        $recs = DoseItemRecord::whereIn('consultation_id', $consultation_ids)
            ->get();
        return $this->success($recs, $message = "Success", 200);
    }


    public function dose_item_records_state(Request $r)
    {
        $rec = DoseItemRecord::find($r->id);
        if ($rec == null) {
            return $this->error('Record not found.');
        }
        $due_date = Carbon::parse($rec->due_date);
        //check if is future date and dont accept
        if ($due_date->isFuture()) {
            return $this->error('Cannot change state of future record.');
        }
        $rec->status = $r->status;
        $rec->save();
        $rec = DoseItemRecord::find($r->id);
        return $this->success($rec, $message = "Success", 200);
    }

    public function consultations()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }
        $conds = [];
        if (!$u->isRole('admin')) {
            $conds['patient_id'] = $u->id;
        }
        return $this->success(
            Consultation::where($conds)
                ->get(),
            $message = "Success",
            200
        );
    }

    public function laundry_order_item_types()
    {
        return $this->success(LaundryOrderItemType::where([])
            ->get(), $message = "Success", 200);
    }

    public function tasks()
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }
        return $this->success(Task::where([
            'assigned_to' => $u->id,
        ])
            ->orWhere([
                'manager_id' => $u->id,
            ])
            ->get(), $message = "Success", 200);
    }

    public function tasks_update_status(Request $r)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('Account not found');
        }

        if ($r->task_id == null) {
            return $this->error('Task ID is required.');
        }


        $task = Task::find($r->task_id);
        if ($task == null) {
            return $this->error('Task not found. ' . $r->task_id);
        }
        if (strlen($r->delegate_submission_status) > 2) {
            $task->delegate_submission_status = $r->delegate_submission_status;
        }
        if (strlen($r->manager_submission_status) > 2) {
            $task->manager_submission_status = $r->manager_submission_status;
        }
        if (strlen($r->delegate_submission_remarks) > 2) {
            $task->delegate_submission_remarks = $r->delegate_submission_remarks;
        }
        if (strlen($r->manager_submission_remarks) > 2) {
            $task->manager_submission_remarks = $r->manager_submission_remarks;
        }

        try {
            $task->save();
        } catch (\Throwable $th) {
            return $this->error('Failed to update task.');
        }
        $task = Task::find($r->task_id);
        if ($task == null) {
            return $this->error('Task not found.');
        }

        return $this->success($task, $message = "Success", 200);
    }





    public function login(Request $r)
    {
        if ($r->username == null) {
            return $this->error('Username is required.');
        }

        if ($r->password == null) {
            return $this->error('Password is required.');
        }

        $password = trim($r->password);
        if (strlen($password) < 3) {
            return $this->error('Password is invalid.');
        }

        $username = $r->username;
        if ($username == null) {
            return $this->error('Username is required.');
        }
        $username = trim($r->username);
        //check if username is less than 3
        if (strlen($username) < 3) {
            return $this->error('Username is invalid.');
        }

        $u = User::where('phone_number_1', $username)->first();
        if ($u == null) {
            $u = User::where('username', $username)->first();
        }
        if ($u == null) {
            $u = User::where('email', $username)->first();
        }
        if ($u == null) {
            return $this->error('User account not found.');
        }

        if ($u->status == 3) {
        }

        JWTAuth::factory()->setTTL(60 * 24 * 30 * 365);

        $token = auth('api')->attempt([
            'id' => $u->id,
            'password' => trim($password),
        ]);

        if ($token == null) {
            return $this->error('Wrong credentials.');
        }

        $u->token = $token;
        $u->remember_token = $token;

        return $this->success($u, 'Logged in successfully.');
    }


    public function register(Request $r)
    {
        if ($r->phone_number_1 == null) {
            return $this->error('Phone number is required.');
        }

        $phone_number = Utils::prepare_phone_number(trim($r->phone_number));


        if (!Utils::phone_number_is_valid($phone_number)) {
            return $this->error('Invalid phone number. -=>' . $phone_number);
        }

        if ($r->password == null) {
            return $this->error('Password is required.');
        }

        //check if  password is greater than 6
        if (strlen($r->password) < 4) {
            return $this->error('Password must be at least 6 characters.');
        }

        if ($r->name == null) {
            return $this->error('Name is required.');
        }

        $email = trim($r->email);
        if ($email != null) {
            $email = trim($email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->error('Invalid email address.');
            }
        }



        $u = Administrator::where('phone_number_1', $phone_number)
            ->orWhere('username', $phone_number)->first();
        if ($u != null) {
            return $this->error('User with same phone number already exists.');
        }

        $user = new Administrator();

        $name = $r->name;
        //replace all spaces with single space
        $name = preg_replace('!\s+!', ' ', $name);

        $x = explode(' ', $name);

        //check if last name is set
        if (!isset($x[1])) {
            return $this->error('Last name is required.');
        }

        if (
            isset($x[0]) &&
            isset($x[1])
        ) {
            $user->first_name = $x[0];
            $user->last_name = $x[1];
        } else {
            $user->first_name = $name;
        }

        //user with same email
        $u = Administrator::where('email', $email)->first();
        if ($u != null) {
            return $this->error('User with same email already exists.');
        }

        // same username
        $u = Administrator::where('username', $phone_number)->first();
        if ($u != null) {
            return $this->error('User with same phone number already exists.');
        }

        //same user name as email
        $u = Administrator::where('username', $email)->first();
        if ($u != null) {
            return $this->error('User with same email already exists.');
        }


        $user->phone_number_1 = $phone_number;
        $user->username = $phone_number;
        $user->email = $email;
        $user->name = $name;
        $user->password = password_hash(trim($r->password), PASSWORD_DEFAULT);
        if (!$user->save()) {
            return $this->error('Failed to create account. Please try again.');
        }

        $new_user = Administrator::find($user->id);
        if ($new_user == null) {
            return $this->error('Account created successfully but failed to log you in.');
        }
        Config::set('jwt.ttl', 60 * 24 * 30 * 365);

        $token = auth('api')->attempt([
            'id' => $new_user->id,
            'password' => trim($r->password),
        ]);

        if ($token == null) {
            return $this->error('Account created successfully but failed to log you in.');
        }

        if (!$token) {
            return $this->error('Account created successfully but failed to log you in..');
        }

        $new_user->token = $token;
        $new_user->remember_token = $token;
        return $this->success($new_user, 'Account created successfully.');
    }



    public function consultation_create(Request $val)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "User not found.",
            ]);
        }


        $consultation = new Consultation();
        $consultation->patient_id = $u->id;
        $consultation->receptionist_id = $u->id;
        $consultation->company_id = $u->company_id;
        $consultation->main_status = 'Pending';
        $consultation->request_status = 'Pending';
        $consultation->patient_name = $u->name;
        $consultation->patient_contact = $u->phone_number_1;
        $consultation->preferred_date_and_time = $val->preferred_date_and_time;
        $services_requested = $val->services_requested;
        $services_requested = str_replace('[', ',', $services_requested);
        $services_requested = str_replace(']', ',', $services_requested);

        $consultation->services_requested = $services_requested;
        $consultation->reason_for_consultation = $val->reason_for_consultation;
        $consultation->request_remarks = $val->request_remarks;
        $consultation->specify_specialist = $val->specify_specialist;
        $consultation->specialist_id = $val->specialist_id;
        $consultation->main_remarks = $val->main_remarks;
        $consultation->payemnt_status = 'Not Paid';
        $consultation->request_date = Carbon::now();
        $consultation->save();
        $consultation = Consultation::find($consultation->id);

        return Utils::response([
            'status' => 1,
            'data' => $consultation,
            'code' => 1,
            'message' => 'Consultation created successfully.',
        ]);
    }


    public function trip_create(Request $val)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "User not found.",
            ]);
        }

        if ($val->is_creating == 'YES') {
            $trip = new Trip();
            $trip->driver_id = $u->id;

            $existingTrip = Trip::where([
                'driver_id' => $u->id,
                'status' => 'ONGOING',
                'type' => $val->type,
            ])->first();
            if ($existingTrip != null) {
                return Utils::response([
                    'status' => 0,
                    'code' => 0,
                    'message' => "You already have an active trip for ({$val->type}).",
                ]);
            }
            $trip->type = $val->type;
            $trip->status = 'ONGOING';
            $trip->start_location = $val->start_location;
            $trip->end_location = $val->end_location;
            $trip->vehicle = $val->vehicle;
            $trip->payment_status = 'NOT PAID';
            $trip->start_time = Carbon::now();
            $trip->notes = date('Y-m-d,H:ia') . ' - ' . $val->type;
            $trip->code = rand(10000, 99999);

            try {
                $trip->save();
                $trip = Trip::find($trip->id);
                if ($trip == null) {
                    return Utils::response([
                        'status' => 0,
                        'code' => 0,
                        'message' => "Failed to find create trip.",
                    ]);
                }
            } catch (\Throwable $th) {
                return $this->error('Failed to create trip because ' . $th->getMessage());
            }
            return Utils::response([
                'status' => 1,
                'data' => $trip,
                'code' => 1,
                'message' => 'Trip created successfully.',
            ]);
        } else if ($val->is_creating == 'NO') {
            $trip = Trip::find($val->id);

            if ($trip == null) {
                return Utils::response([
                    'status' => 0,
                    'code' => 0,
                    'message' => "Trip not found.",
                ]);
            }
            $trip->status = $val->status;
            $trip->type = $val->type;
            $trip->start_location = ($val->start_location == null) ? $trip->start_location : $val->start_location;
            $trip->end_location = ($val->end_location == null) ? $trip->end_location : $val->end_location;
            $trip->end_time = ($val->end_time == null) ? Carbon::now() : $val->end_time;
            $trip->distance = $val->distance;
            $trip->amount = $val->amount;
            $trip->payment_status = ($val->payment_status == null) ? $trip->payment_status : $val->payment_status;
            $trip->payment_method = ($val->payment_method == null) ? $trip->payment_method : $val->payment_method;
            $trip->payment_date = ($val->payment_date == null) ? $trip->payment_date : $val->payment_date;
            $trip->notes = ($val->notes == null) ? $trip->notes : $val->notes;

            try {
                $trip->save();
            } catch (\Throwable $th) {
                return $this->error('Failed to update trip because ' . $th->getMessage());
            }
            $trip = Trip::find($trip->id);
            return Utils::response([
                'status' => 1,
                'data' => $trip,
                'code' => 1,
                'message' => 'Trip updated successfully.',
            ]);
        } else {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Invalid request.",
            ]);
        }
        /* 

        	id	created_at	updated_at	type	status	start_location	end_location	start_time	end_time	driver_id	vehicle	items	distance	duration	amount	payment_status	payment_method	payment_date	code	code_verification	notes	


        */
    }

    public function order_create_create(Request $val)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "User not found.",
            ]);
        }


        //validate for local_id
        if ($val->local_id == null) {
            return $this->error('Local ID is required.');
        }

        //local_id len > 5
        if (strlen($val->local_id) < 5) {
            return $this->error('Local ID is invalid.');
        }


        $order = LaundryOrder::where([
            'local_id' => $val->local_id
        ])->first();

        $isCreating = false;
        if ($order == null) {
            $order = new LaundryOrder();
            $isCreating = true;
        } else {
            $isCreating = false;
        }

        if ($isCreating) {
            $order->user_id = $u->id;
            $order->customer_name = $val->customer_name;
            $order->customer_phone = $val->customer_phone;
            $order->customer_address = $val->customer_address;
            $order->pickup_address = $val->pickup_address;
            $order->pickup_gps = $val->pickup_gps;
            $order->delivery_address = $val->delivery_address;
            $order->special_instructions = $val->special_instructions;
            $order->total_amount = $val->total_amount;
            $order->payment_status = $val->payment_status;
            $order->payment_method = $val->payment_method;
            $order->payment_date = $val->payment_date;
            $order->stripe_payment_link = $val->stripe_payment_link;
            $order->payment_reference = $val->payment_reference;
            $order->payment_notes = $val->payment_notes;
            $order->customer_photos = $val->customer_photos;
            $order->scheduled_pickup_time = $val->scheduled_pickup_time;
            $order->assigned_driver_id = $val->assigned_driver_id;
            $order->driver_id = $val->driver_id;
            $order->actual_pickup_time = $val->actual_pickup_time;
            $order->pickup_notes = $val->pickup_notes;
            $order->laundry_delivery_time = $val->laundry_delivery_time;
            $order->washer_assignment_time = $val->washer_assignment_time;
            $order->assigned_washer_id = $val->assigned_washer_id;
            $order->washer_id = $val->washer_id;
            $order->washing_start_time = $val->washing_start_time;
            $order->washing_end_time = $val->washing_end_time;
            $order->drying_start_time = $val->drying_start_time;
            $order->drying_end_time = $val->drying_end_time;
            $order->scheduled_delivery_time = $val->scheduled_delivery_time;
            $order->delivery_driver_id = $val->delivery_driver_id;
            $order->actual_delivery_time = $val->actual_delivery_time;
            $order->delivery_notes = $val->delivery_notes;
            $order->final_payment_date = $val->final_payment_date;
            $order->receipt_approved_date = $val->receipt_approved_date;
            $order->rating = $val->rating;
            $order->driver_amount = $val->driver_amount;
            $order->driving_distance = $val->driving_distance;
            $order->feedback = $val->feedback;
            $order->local_id = $val->local_id;
            $order->status = 'PENDING';


            try {
                $order->save();
            } catch (\Throwable $th) {
                return $this->error($th->getMessage());
            }
            $order = LaundryOrder::find($order->id);


            $message = "Order created successfully.";
            if ($isCreating) {
                $message = "Order created successfully.";
            } else {
                $message = "Order updated successfully.";
            }

            return Utils::response([
                'status' => 1,
                'data' => $order,
                'code' => 1,
                'message' => $message,
            ]);
        }


        $accepted_tasks = [
            'BILLING',
            'PICKUP',
            strtoupper('Picked Up'),
            strtoupper('Washing in Progress'),
            'ASSIGN WASHER',
            'READY FOR DELIVERY',
            'OUT FOR DELIVERY',
            'DELIVERED',
            'COMPLETED',
        ];

        if (!$isCreating) {
            if (!in_array(trim($val->task), $accepted_tasks)) {
                return $this->error('Invalid order status. -> #' . $val->task);
            }
        }
        //COMPLETED
        if (!$isCreating && $val->task == 'COMPLETED') {
            //CHECK if order is paid
            $is_paid = 'Not Paid';
            try {
                $order->is_order_paid();
            } catch (\Throwable $th) {
                return $this->error($th->getMessage());
            }
            if ($order->payment_status != 'Paid') {
                return $this->error('Order is not paid.');
            }
            $order->status = strtoupper('COMPLETED');
            try {
                $order->save();
                $order = LaundryOrder::find($order->id);
            } catch (\Throwable $th) {
                return $this->error('Failed to update order because ' . $th->getMessage());
            }
            return $this->success($order, $message = "Order completed successfully.", 200);
        }
        if (!$isCreating && $val->task == 'DELIVERED') {
            if (strlen(trim($val->delivery_notes)) < 1) {
                return $this->error('Invalid delivery CODE. #' . $val->delivery_notes);
            }
            $order->status = strtoupper('DELIVERED');
            $order->delivery_notes = $val->delivery_notes;
            $order->delivery_driver_id = $u->id;
            try {
                $order->save();
                $order = LaundryOrder::find($order->id);
            } catch (\Throwable $th) {
                return $this->error('Failed to update order because ' . $th->getMessage());
            }
            return $this->success($order, $message = "Order delivered successfully.", 200);
        }
        if (!$isCreating && $val->task == 'OUT FOR DELIVERY') {
            $order->status = strtoupper('OUT FOR DELIVERY');
            try {
                $order->save();
                $order = LaundryOrder::find($order->id);
            } catch (\Throwable $th) {
                return $this->error('Failed to update order because ' . $th->getMessage());
            }
            return $this->success($order, $message = "Order is out for delivery.", 200);
        }

        if (!$isCreating && $val->task == 'READY FOR DELIVERY') {
            $driver = User::find($val->driver_id);
            if ($driver == null) {
                return $this->error('Driver not found.');
            }
            $order->driver_id = $driver->id;
            $order->status = strtoupper('Ready for Delivery');
            $order->delivery_notes = rand(10000, 99999);  //generate random number
            try {
                $order->save();
                $order = LaundryOrder::find($order->id);
            } catch (\Throwable $th) {
                return $this->error('Failed to update order because ' . $th->getMessage());
            }
            return $this->success($order, $message = "Ready for delivery.", 200);
        }
        if (!$isCreating && $val->task == 'WASHING IN PROGRESS') {
            $order->status = strtoupper('Washing in Progress');
            try {
                $order->save();
                $order = LaundryOrder::find($order->id);
            } catch (\Throwable $th) {
                return $this->error('Failed to update order because ' . $th->getMessage());
            }
            return $this->success($order, $message = "Washing in progress.", 200);
        }
        if (!$isCreating && $val->task == 'ASSIGN WASHER') {
            $washer = User::find($val->washer_id);
            if ($washer == null) {
                return $this->error('Washer not found.');
            }


            $items_json = $val->items;
            if ($items_json == null) {
                if ($isCreating) {
                    return $this->error('Items are required.');
                }
            }
            $items = [];
            try {
                $items = json_decode($items_json);
            } catch (\Throwable $th) {
                return $this->error('Failed to parse items.');
            }

            //ifnotarray
            if (!is_array($items)) {
                return $this->error('Items must be an array.');
            }

            if (count($items) < 1) {
                return $this->error('Items must have at least one item.');
            }


            foreach ($items as $item) {
                $order_item = LaundryOrderItem::where([
                    'local_id' => $item->local_id
                ])->first();
                if ($order_item == null) {
                    $order_item = new LaundryOrderItem();
                }
                /* 
                order_id	order_number	status	washer_id	washer_name	washer_notes	washer_photos		
                */
                //validate $item->name
                if ($item->name == null || strlen($item->name) < 2) {
                    return $this->error('Item name is required.');
                }
                //validate $item->type 
                if ($item->type == null || strlen($item->type) < 2) {
                    return $this->error('Item type is required.');
                }
                //customer_notes
                if ($item->quantity == null || strlen($item->quantity) < 1) {
                    return $this->error('Item quantity is required.');
                }
                //price
                if ($item->price == null || strlen($item->price) < 1) {
                    return $this->error('Item price is required.');
                }
                //total
                if ($item->total == null || strlen($item->total) < 1) {
                    return $this->error('Item total is required.');
                }

                //local_id
                if ($item->local_id == null || strlen($item->local_id) < 1) {
                    return $this->error('Item local_id is required.');
                }
                //order_local_id
                if ($item->order_local_id == null || strlen($item->order_local_id) < 1) {
                    return $this->error('Item order_local_id is required.');
                }

                //laundry_order_item_type_id
                if ($item->laundry_order_item_type_id == null || strlen($item->laundry_order_item_type_id) < 1) {
                    return $this->error('Item laundry_order_item_type_id is required.');
                }

                if ($order_item == null) {
                    $order_item = new LaundryOrderItem();
                }

                $order_item->name = $item->name;
                $order_item->type = $item->type;
                $order_item->customer_notes = $item->customer_notes;
                $order_item->customer_photos = $item->customer_photos;
                $order_item->quantity = $item->quantity;
                $order_item->price = $item->price;
                $order_item->total = $item->total;
                $order_item->order_id = $order->id;
                $order_item->order_number = $order->order_number;
                $order_item->status = $item->status;
                $order_item->washer_id = $item->washer_id;
                $order_item->washer_name = $item->washer_name;
                $order_item->washer_notes = $item->washer_notes;
                $order_item->washer_photos = $item->washer_photos;
                $order_item->local_id = $item->local_id;
                $order_item->order_local_id = $order->local_id;
                $order_item->laundry_order_item_type_id = $item->laundry_order_item_type_id;
                $order_item->save();
            }

            $order->washer_id = $washer->id;
            $order->status = strtoupper('Awaiting Washing');
            try {
                $order->save();
                $order = LaundryOrder::find($order->id);
            } catch (\Throwable $th) {
                return $this->error('Failed to update order because ' . $th->getMessage());
            }
            return $this->success($order, $message = "Washer assigned successfully.", 200);
        }
        if (!$isCreating && $val->task == 'BILLING') {

            $accepted_payment_status = [
                'Paid',
                'Not Paid',
            ];

            if (!in_array($val->payment_status, $accepted_payment_status)) {
                return $this->error('Invalid payment status.');
            }

            //total_amount float
            $total_amount = (float) $val->total_amount;
            $weight = (float) $val->weight;

            if ($weight < 0.1) {
                return $this->error('Weight must be greater than 0.');
            }

            if (!is_float($total_amount)) {
                return $this->error('Total amount must be a float.');
            }
            //service_amount float
            $service_amount = (float)$val->service_amount;
            if (!is_float($service_amount)) {
                return $this->error('Service amount must be a float.');
            }

            //washing_amount float
            $washing_amount = (float) $val->washing_amount;
            if (!is_float($washing_amount)) {
                return $this->error('Washing amount must be a float.');
            }
            $order->total_amount = $total_amount;
            $order->service_amount = $service_amount;
            $order->washing_amount = $washing_amount;
            $order->weight = $weight;
            $order->status = strtoupper('READY FOR PAYMENT');

            $order->payment_status = $val->payment_status;
            if ($val->payment_status == 'Paid') {
                $order->payment_reference = $val->payment_reference;
                $order->payment_method = $val->payment_method;
                $order->payment_notes = $val->payment_notes;
                $order->payment_status = 'Paid';
                $order->payment_date = Carbon::now();
                $order->status = strtoupper('PENDING');
            }

            try {
                $order->save();
                $order = LaundryOrder::find($order->id);
            } catch (\Throwable $th) {
                return $this->error('Failed to update order because ' . $th->getMessage());
            }

            return $this->success($order, $message = "Billing updated successfully. #" . $order->id, 200);
        }

        if (!$isCreating && $val->task == 'PICKUP') {
            //driver_id
            $driver = User::find($val->driver_id);
            if ($driver == null) {
                return $this->error('Driver not found.');
            }
            $order->driver_id = $driver->id;
            $order->pickup_notes = $val->pickup_notes;
            $order->pickup_address = $val->pickup_address;
            $order->status = 'Awaiting Pickup';
            try {
                $order->save();
                $order = LaundryOrder::find($order->id);
            } catch (\Throwable $th) {
                return $this->error('Failed to update order because ' . $th->getMessage());
            }
            return $this->success($order, $message = "Pickup updated successfully.", 200);
        }

        if (!$isCreating && $val->task == strtoupper('Picked Up')) {
            $order->status = strtoupper('Picked Up');
            $order->pickup_notes = $val->pickup_notes;
            $order->delivery_driver_id = $u->id;
            try {
                $order->save();
                $order = LaundryOrder::find($order->id);
            } catch (\Throwable $th) {
                return $this->error('Failed to update order because ' . $th->getMessage());
            }
            return $this->success($order, $message = "Order picked up successfully.", 200);
        }

        if (!$isCreating) {
            return $this->error('Invalid task #' . $val->task);
        }
    }


    public function get_order_payment_link(Request $val)
    {
        $order = LaundryOrder::find($val->id);
        if ($order == null) {
            return $this->error('Order not found.');
        }
        try {
            $order->get_payment_link();
        } catch (\Throwable $th) {
            return $this->error($th->getMessage());
        }
        $order = LaundryOrder::find($val->id);
        return $this->success($order, $message = "Success", 200);
    }

    public function tasks_create(Request $val)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "User not found.",
            ]);
        }

        if ($val->assign_to_type != 'to_me') {
            if ($val->assigned_to == null) {
                return Utils::response([
                    'status' => 0,
                    'code' => 0,
                    'message' => "Assigned to is required.",
                ]);
            }
        }

        $message = "";
        $newTask = new Task();
        try {
            $task = new Task();
            $task->company_id = $u->id;
            $task->meeting_id = null;
            $task->assigned_to = $val->assigned_to;
            $task->project_id = $val->project_id;
            $task->created_by = $u->id;
            $task->name = $val->name;
            $task->task_description = $val->task_description;
            $task->due_to_date = Carbon::parse($val->due_to_date);
            $task->priority = 'Medium';
            $task->save();
        } catch (\Throwable $th) {
            $message = $th->getMessage();
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => $message,
            ]);
        }

        $newTask = Task::find($task->id);

        return Utils::response([
            'status' => 1,
            'data' => $newTask,
            'code' => 1,
            'message' => 'Task created successfully.',
        ]);
    }


    public function meetings_create(Request $val)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "User not found.",
            ]);
        }

        if (!(isset($val->resolutions)) || $val->resolutions == null) {
            //return resolutions not set
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Resolutions not set"
            ]);
        }

        $meeting = new Meeting();
        $meeting->created_by = $u->id;
        $meeting->company_id = $u->company_id;
        $meeting->name = $val->gps_latitude;
        $meeting->details = $val->details;
        $meeting->minutes_of_meeting = $val->details;
        $meeting->location = $val->location_text;
        $meeting->meeting_start_time = $val->start_date;
        $meeting->meeting_end_time = $val->end_date;
        $meeting->meeting_end_time = $val->session_date;
        $local_id = $val->id;
        $files = [];
        foreach (
            Image::where([
                'parent_id' => $local_id
            ])->get() as $key => $value
        ) {
            $files[] = 'images/' . $value->src;
        }
        $meeting->attendance_list_pictures = $files;

        try {
            $meeting->save();
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            return $this->error($msg);
        }


        $resolutions = null;
        try {
            $resolutions = json_decode($val->resolutions);
        } catch (\Throwable $th) {
            $resolutions = null;
        }


        if (($resolutions != null) && is_array($resolutions)) {
            foreach ($resolutions as $key => $res) {
                $task = new Task();
                $task->company_id = $u->id;
                $task->meeting_id = $meeting->id;
                $task->created_by = $u->id;
                $task->project_id = 1;
                $task->project_section_id = 1;
                $task->project_id = 1;
                $task->rate = 0;
                $task->hours = 0;
                $task->assigned_to = $res->assigned_to;
                $manager = Administrator::find($res->assigned_to);
                if ($manager != null) {
                    $task->manager_id = $manager->id;
                }
                $task->company_id = $u->company_id;
                $task->name = $res->name;
                $task->task_description = $res->task_description;
                $task->due_to_date = $res->due_to_date;
                $task->assign_to_type = $res->assign_to_type;
                $task->delegate_submission_status = 'Pending';
                $task->manager_submission_status = 'Pending';
                $task->is_submitted = 'Pending';
                $task->delegate_submission_remarks = '';
                $task->manager_submission_remarks = '';
                $task->priority = 'Medium';
                $task->save();
            }
        }

        $meeting = Meeting::find($meeting->id);
        return Utils::response([
            'status' => 1,
            'data' => $meeting,
            'code' => 1,
            'message' => 'Meeting created successfully.',
        ]);
    }



    public function password_change(Request $request)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $administrator_id = $u->id;

        $u = Administrator::find($administrator_id);
        if ($u == null) {
            return $this->error('User not found.');
        }

        if (
            $request->password == null ||
            strlen($request->password) < 2
        ) {
            return $this->error('Password is missing.');
        }

        //check if  current_password 
        if (
            $request->current_password == null ||
            strlen($request->current_password) < 2
        ) {
            return $this->error('Current password is missing.');
        }

        //check if  current_password
        if (
            !(password_verify($request->current_password, $u->password))
        ) {
            return $this->error('Current password is incorrect.');
        }

        $u->password = password_hash($request->password, PASSWORD_DEFAULT);
        $msg = "";
        $code = 1;
        try {
            $u->save();
            $msg = "Password changed successfully.";
            return $this->success($u, $msg, $code);
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            $code = 0;
            return $this->error($msg);
        }
        return $this->success(null, $msg, $code);
    }


    public function delete_profile(Request $request)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $administrator_id = $u->id;

        $u = Administrator::find($administrator_id);
        if ($u == null) {
            return $this->error('User not found.');
        }
        $u->status = '3';
        $u->save();
        return $this->success(null, $message = "Deleted successfully!", 1);
    }


    public function consultation_card_payment(Request $request)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $administrator_id = $u->id;

        $u = Administrator::find($administrator_id);
        if ($u == null) {
            return $this->error('User not found.');
        }
        //check for consultation_id
        if (
            $request->consultation_id == null ||
            strlen($request->consultation_id) < 1
        ) {
            return $this->error('Consultation ID is missing.');
        }
        $consultation = Consultation::find($request->consultation_id);
        if ($consultation == null) {
            return $this->error('Consultation not found.');
        }

        //validate amount_paid
        if (
            $request->amount_paid == null ||
            strlen($request->amount_paid) < 1
        ) {
            return $this->error('Amount payable is missing.');
        }

        // amount_paid should be less than or equal to amount_paid
        if (
            $request->amount_paid > $consultation->total_due
        ) {
            return $this->error('Amount payable is greater than amount paid.');
        }

        //amount_payable should be more th 500
        if (
            $request->amount_paid < 500
        ) {
            return $this->error('Amount payable should be more than 500.');
        }

        //validate payment_method
        if (
            $request->payment_method == null ||
            strlen($request->payment_method) < 1
        ) {
            return $this->error('Payment method is missing.');
        }

        $u = User::find($u->id);
        $card = $u->get_card();
        if ($card == null) {
            return $this->error('Card not found.');
        }

        if ($card->card_status != 'Active') {
            return $this->error('Card is not active. Current status is ' . $u->card_status . ".");
        }

        $amount = (int)($request->amount_paid);
        $card_balance = (int)($card->card_balance);
        if ($amount > $card_balance) {
            $card_max_credit = (int)($card->card_max_credit);
            $acceptable_credit = $card_max_credit - $card_balance;
            if ($amount > $acceptable_credit) {
                return $this->error('Amount payable is greater than card credit limit. (UGX ' . $card_max_credit . ")");
            }
        }


        $paymentRecord = new PaymentRecord();
        $paymentRecord->consultation_id = $consultation->id;
        $paymentRecord->description = 'Consultation payment for ' . $consultation->name_text;
        $paymentRecord->amount_payable = $consultation->total_due;
        $paymentRecord->amount_paid = $amount;
        $paymentRecord->balance = $consultation->total_due - $amount;
        $paymentRecord->payment_date = Carbon::now();
        $paymentRecord->payment_time = Carbon::now();
        $paymentRecord->payment_method = $request->payment_method;
        $paymentRecord->payment_reference = rand(100000, 999999) . rand(100000, 999999);
        $paymentRecord->payment_status = 'Success';
        $paymentRecord->payment_remarks = 'Payment through mobile money.';
        $paymentRecord->payment_phone_number = $u->phone_number_1;
        $paymentRecord->payment_channel = 'Mobile App';
        $paymentRecord->created_by_id = $u->id;
        $paymentRecord->cash_receipt_number = $paymentRecord->payment_reference;
        $paymentRecord->card_id = $card->id;
        $paymentRecord->company_id = $card->company_id;
        $paymentRecord->card_number = $card->card_number;

        try {
            $paymentRecord->save();
        } catch (\Throwable $th) {
            return $this->error('Failed to save payment record.');
        }
        $paymentRecord = PaymentRecord::find($paymentRecord->id);
        return $this->success($paymentRecord, $message = "Payment successful.", 1);
    }



    public function stripe_payment_verification(Request $request)
    {
        $fw = LaundryOrder::find($request->id);
        if ($fw == null) {
            return Utils::response([
                'status' => 0,
                'message' => "Payment record not found."
            ]);
        }
        if ($fw->payment_status == 'Paid') {
            return Utils::response([
                'status' => 1,
                'message' => 'Payment successful.',
                'data' => $fw
            ]);
        }

        $fw->is_order_paid();
        $fw = LaundryOrder::find($request->id);
        if ($fw->payment_status == 'Paid') {
            return Utils::response([
                'status' => 1,
                'message' => 'Payment successful.',
                'data' => $fw
            ]);
        } else {
            return Utils::response([
                'status' => 0,
                'message' => "Payment not successful.",
                'data' => $fw
            ]);
        }
    }


    public function flutterwave_payment_verification(Request $request)
    {
        $fw = FlutterWaveLog::find($request->id);
        if ($fw == null) {
            return Utils::response([
                'status' => 0,
                'message' => "Payment record not found."
            ]);
        }
        $fw->is_order_paid();
        $fw = FlutterWaveLog::find($request->id);
        if ($fw->status == 'Paid') {
            return Utils::response([
                'status' => 1,
                'message' => "Payment successful.",
                'data' => $fw
            ]);
        } else {
            return Utils::response([
                'status' => 0,
                'message' => "Payment not successful.",
                'data' => $fw
            ]);
        }
    }
    public function consultation_flutterwave_payment(Request $request)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $administrator_id = $u->id;

        $u = Administrator::find($administrator_id);
        if ($u == null) {
            return $this->error('User not found.');
        }
        //check for consultation_id
        if (
            $request->consultation_id == null ||
            strlen($request->consultation_id) < 1
        ) {
            return $this->error('Consultation ID is missing.');
        }
        $consultation = Consultation::find($request->consultation_id);
        if ($consultation == null) {
            return $this->error('Consultation not found.');
        }

        //validate amount_paid
        if (
            $request->amount_paid == null ||
            strlen($request->amount_paid) < 1
        ) {
            return $this->error('Amount payable is missing.');
        }

        // amount_paid should be less than or equal to amount_paid
        if (
            $request->amount_paid > $consultation->total_due
        ) {
            return $this->error('Amount payable is greater than amount paid.');
        }

        $phone_number = Utils::prepare_phone_number($request->payment_phone_number);

        //check if phone number is valid
        if (!Utils::phone_number_is_valid($phone_number)) {
            return $this->error('Invalid phone number.');
        }

        //amount_payable should be more th 500
        if (
            $request->amount_paid < 500
        ) {
            return $this->error('Amount payable should be more than 500.');
        }

        //validate payment_method
        if (
            $request->payment_method == null ||
            strlen($request->payment_method) < 1
        ) {
            return $this->error('Payment method is missing.');
        }
        $amount = (int)($request->amount_paid);
        FlutterWaveLog::where([
            'status' => 'Pending',
            'consultation_id' => $consultation->id,
        ])->delete();


        $fw = new FlutterWaveLog();
        $fw->consultation_id = $consultation->id;
        $fw->flutterwave_payment_amount = $amount;
        $fw->status = 'Pending';
        $fw->flutterwave_payment_type = 'Consultation';
        $fw->flutterwave_payment_customer_phone_number = $phone_number;
        $fw->flutterwave_payment_status = 'Pending';
        $phone_number_type = substr($phone_number, 0, 6);


        if (
            $phone_number_type == '+25670' ||
            $phone_number_type == '+25675' ||
            $phone_number_type == '+25674'
        ) {
            $phone_number_type = 'AIRTEL';
        } else if (
            $phone_number_type == '+25677' ||
            $phone_number_type == '+25678' ||
            $phone_number_type == '+25676'
        ) {
            $phone_number_type = 'MTN';
        }

        if (
            $phone_number_type != 'MTN' &&
            $phone_number_type != 'AIRTEL'
        ) {
            return Utils::response([
                'status' => 0,
                'message' => "Phone number must be MTN or AIRTEL. ($phone_number_type)"
            ]);
        }

        $phone_number = str_replace([
            '+256'
        ], "0", $phone_number);



        try {
            $fw->uuid = Utils::generate_uuid();
            $payment_link = $fw->generate_payment_link(
                $phone_number,
                $phone_number_type,
                $amount,
                $fw->uuid
            );
            if (strlen($payment_link) < 5) {
                return Utils::response([
                    'status' => 0,
                    'message' => "Failed to generate payment link."
                ]);
            }
            $fw->flutterwave_payment_link = $payment_link;
            $fw->save();
            return Utils::response([
                'status' => 1,
                'message' => "Payment link generated successfully.",
                'data' => $fw
            ]);
        } catch (\Throwable $th) {
            return Utils::response([
                'status' => 0,
                'message' => "Failed because " . $th->getMessage()
            ]);
        }





        return $this->success($paymentRecord, $message = "Payment successful.", 1);
    }



    public function update_profile(Request $request)
    {
        $u = auth('api')->user();
        if ($u == null) {
            return $this->error('User not found.');
        }
        $administrator_id = $u->id;

        $u = Administrator::find($administrator_id);
        if ($u == null) {
            return $this->error('User not found.');
        }

        if (
            $request->first_name == null ||
            strlen($request->first_name) < 2
        ) {
            return $this->error('First name is missing.');
        }
        //validate all
        if (
            $request->last_name == null ||
            strlen($request->last_name) < 2
        ) {
            return $this->error('Last name is missing.');
        }



        if ($request->phone_number_1 != null && strlen($request->phone_number_1) > 4) {
            $anotherUser = Administrator::where([
                'phone_number_1' => $request->phone_number_1
            ])->first();
            if ($anotherUser != null) {
                if ($anotherUser->id != $u->id) {
                    return $this->error('Phone number is already taken.');
                }
            }

            $anotherUser = Administrator::where([
                'username' => $request->phone_number_1
            ])->first();
            if ($anotherUser != null) {
                if ($anotherUser->id != $u->id) {
                    return $this->error('Phone number is already taken.');
                }
            }

            $anotherUser = Administrator::where([
                'email' => $request->phone_number_1
            ])->first();
            if ($anotherUser != null) {
                if ($anotherUser->id != $u->id) {
                    return $this->error('Phone number is already taken.');
                }
            }
        }




        if ($request->email != null && strlen($request->email) > 4) {

            if (
                $request->email != null &&
                strlen($request->email) > 5
            ) {
                $anotherUser = Administrator::where([
                    'email' => $request->email
                ])->first();
                if ($anotherUser != null) {
                    if ($anotherUser->id != $u->id) {
                        return $this->error('Email is already taken.');
                    }
                }
                //check for username as well
                $anotherUser = Administrator::where([
                    'username' => $request->email
                ])->first();
                if ($anotherUser != null) {
                    if ($anotherUser->id != $u->id) {
                        return $this->error('Email is already taken.');
                    }
                }
                // //validate email
                // if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
                //     return $this->error('Invalid email address.');
                // }
            }
        }



        $msg = "";
        //first letter to upper case
        $u->first_name = $request->first_name;

        //change first letter to upper case
        $u->first_name = ucfirst($u->first_name);


        $u->last_name = ucfirst($request->last_name);
        $u->phone_number_1 = $request->phone_number_1;
        $u->email = $request->email;
        $u->home_address = ucfirst($request->home_address);

        $images = [];
        if (!empty($_FILES)) {
            $images = Utils::upload_images_2($_FILES, false);
        }
        if (!empty($images)) {
            $u->avatar = 'images/' . $images[0];
        }

        $code = 1;
        try {
            $u->save();
            $u = Administrator::find($administrator_id);
            $msg = "Updated successfully.";
            return $this->success($u, $msg, $code);
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            $code = 0;
            return $this->error($msg);
        }
        return $this->success(null, $msg, $code);
    }





    public function upload_media(Request $request)
    {

        $u = auth('api')->user();
        if ($u == null) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "User not found.",
            ]);
        }

        //check for type
        if (
            !isset($request->type) ||
            $request->type == null ||
            (strlen(($request->type))) < 3
        ) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Type is missing.",
            ]);
        }

        $administrator_id = $u->id;
        if (
            !isset($request->parent_id) ||
            $request->parent_id == null
        ) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Local parent ID is missing. 1",
            ]);
        }

        if (
            !isset($request->parent_endpoint) ||
            $request->parent_endpoint == null ||
            (strlen(($request->parent_endpoint))) < 3
        ) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Local parent ID endpoint is missing.",
            ]);
        }



        if (
            empty($_FILES)
        ) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => "Files not found.",
            ]);
        }


        $images = Utils::upload_images_2($_FILES, false);
        $_images = [];

        if (empty($images)) {
            return Utils::response([
                'status' => 0,
                'code' => 0,
                'message' => 'Failed to upload files.',
                'data' => null
            ]);
        }


        $msg = "";
        foreach ($images as $src) {

            if ($request->parent_endpoint == 'edit') {
                $img = Image::find($request->local_parent_id);
                if ($img) {
                    return Utils::response([
                        'status' => 0,
                        'code' => 0,
                        'message' => "Original photo not found",
                    ]);
                }
                $img->src =  $src;
                $img->thumbnail =  null;
                $img->save();
                return Utils::response([
                    'status' => 1,
                    'code' => 1,
                    'data' => json_encode($img),
                    'message' => "File updated.",
                ]);
            }


            $img = new Image();
            $img->administrator_id =  $administrator_id;
            $img->src =  $src;
            $img->thumbnail =  null;
            $img->parent_endpoint =  $request->parent_endpoint;
            $img->type =  $request->type;
            $img->product_id =  $request->product_id;
            $img->parent_id =  $request->parent_id;
            $img->size = 0;
            $img->note = '';
            if (
                isset($request->note)
            ) {
                $img->note =  $request->note;
                $msg .= "Note not set. ";
            }



            $img->save();
            $_images[] = $img;
        }
        //Utils::process_images_in_backround();
        return Utils::response([
            'status' => 1,
            'code' => 1,
            'data' => json_encode($_POST),
            'message' => "File uploaded successfully.",
        ]);
    }
}
