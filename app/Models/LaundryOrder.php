<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LaundryOrder extends Model
{
    use HasFactory;

    //boot
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $m = self::do_prepare($model);
            $m->status = 'PENDING';
            return $m;
        });
        static::updating(function ($model) {
            $m = self::do_prepare($model);
            return $m;
        });

        //updated
        static::updated(function ($model) {
            self::send_mails($model);
        });

        //created
        static::created(function ($model) {
            self::send_mails($model);
        });
    }

    //static do prepare
    public static function send_mails($order)
    {
        $orderStages = [
            "AWAITING WASHING",
            "READY FOR PAYMENT",
            "WASHING IN PROGRESS",
            "READY FOR DELIVERY",
            "OUT FOR DELIVERY",
            "DELIVERED",
            "CANCELLED",
            "COMPLETED"
        ];
        $mails = [
            'ugnewz24@gmail.com',
            'mubahood360@gmail.com',
            'mubs0x@gmail.com',
            'muhindo@8technologies.net',
            'mama.ugx@gmail.com',
        ];
        /* $users = User::all();
        foreach ($users as $key => $u) {
            shuffle($mails);
            $mail = $mails[0];
            $u->email = $mail;
            echo "User: " . $u->name . " - " . $u->email . "<br>";
        }
        die(); */


        if ($order->status == 'PENDING' && $order->order_received_email_sent != 'Yes') {
            $order->send_order_received_email();
        } elseif ($order->status == 'PICKUP' && $order->driver_email_sent != 'Yes') {
            $order->send_driver_email_sent();
        } elseif ($order->status == 'PICKED UP' && $order->order_picked_up_email_sent != 'Yes') {
            $order->send_order_picked_up_email();
        } elseif ($order->status == 'READY FOR PAYMENT' && $order->order_ready_for_payment_email_sent != 'Yes') {
            $order->send_order_ready_for_payment_email();
        }
    }

    //send_order_ready_for_payment_email 
    public function send_order_ready_for_payment_email()
    {
        $app_name = env('APP_NAME');
        $subject = $app_name . ' - ORDER #' . $this->id . " Updates.";
        //pay using this link       
        //stripe_payment_link
        if ($this->stripe_payment_link == null || strlen($this->stripe_payment_link) < 5) {
            $this->get_payment_link();
            return;
        }
 

        $body =
            <<<EOD
        <p>Dear <b>{$this->customer->name}</b>,</p>
        <p>Your order #{$this->id} is ready for payment. Please click the link below to pay.</p>
        <p><a href="{$this->stripe_payment_link}">Pay Now</a></p>
        <p>Thank you for choosing {$app_name}.</p>
        <p>Best regards,</p>
        <p>{$app_name} Team.</p>
        EOD;

        $data = [
            'subject' => $subject,
            'body' => $body,
            'email' => $this->customer->email,
            'name' => $app_name,
        ];
        try {
            Utils::mail_sender($data);
            $sql = "UPDATE laundry_orders SET order_ready_for_payment_email_sent = 'Yes' WHERE id = " . $this->id;
            DB::update($sql);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    //send_order_picked_up_email
    public function send_order_picked_up_email()
    {
        $app_name = env('APP_NAME');
        $subject = $app_name . ' - ORDER #' . $this->id . " Updates.";

        $body =
            <<<EOD
        <p>Dear <b>{$this->customer->name}</b>,</p>
        <p>Your order #{$this->id} has been picked up. We will notify you when it is ready for payment.</p>
        <p>Thank you for choosing {$app_name}.</p>
        <p>Best regards,</p>
        <p>{$app_name} Team.</p>
        EOD;

        $data = [
            'subject' => $subject,
            'body' => $body,
            'email' => $this->customer->email,
            'name' => $app_name,
        ];
        try {
            Utils::mail_sender($data);
            $sql = "UPDATE laundry_orders SET order_picked_up_email_sent = 'Yes' WHERE id = " . $this->id;
            DB::update($sql);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    //pickup_driver
    public function pickup_driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }


    //send_driver_email_sent
    public function send_driver_email_sent()
    {
        $app_name = env('APP_NAME');
        $subject = $app_name . ' - ORDER #' . $this->id . " Updates.";

        $body =
            <<<EOD
        <p>Dear <b>{$this->pickup_driver->name}</b>,</p>
        <p>You have been assigned to pick up order #{$this->id}. Please check your mobile app for more details.</p>
        <p>Best regards,</p>
        <p>{$app_name} Team.</p>
        EOD;

        $data = [
            'subject' => $subject,
            'body' => $body,
            'email' => $this->pickup_driver->email,
            'name' => $app_name,
        ];
        try {
            Utils::mail_sender($data);
            $sql = "UPDATE laundry_orders SET driver_email_sent = 'Yes' WHERE id = " . $this->id;
            DB::update($sql);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    //send_order_received_email
    public function send_order_received_email()
    {
        $app_name = env('APP_NAME');
        $subject = $app_name . ' - ORDER #' . $this->id . " Updates.";
        $body =
            <<<EOD
        <p>Dear <b>{$this->customer->name}</b>,</p>
        <p>Your order has been received and is being processed. We will notify you when it is ready for pickup.</p>
        <p>Thank you for choosing {$app_name}.</p>
        <p>Best regards,</p>
        <p>{$app_name} Team.</p>
        EOD;

        $data = [
            'subject' => $subject,
            'body' => $body,
            'email' => $this->customer->email,
            'name' => $app_name,
        ];
        try {
            Utils::mail_sender($data);
            $sql = "UPDATE laundry_orders SET order_received_email_sent = 'Yes' WHERE id = " . $this->id;
            DB::update($sql);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function do_prepare($data)
    {
        $customer = User::find($data->user_id);
        if ($customer == null) {
            throw new \Exception("Customer not found", 1);
        }
        //customer_name
        if ($data->customer_name == null || strlen($data->customer_name) < 2) {
            $data->customer_name = $customer->name;
        }
        //customer_phone
        if ($data->customer_phone == null || strlen($data->customer_phone) < 2) {
            $data->customer_phone = $customer->phone_number_1;
        }
        //customer_address
        if ($data->customer_address == null || strlen($data->customer_address) < 2) {
            $data->customer_address = $customer->home_address;
        }

        //pickup_address
        if ($data->pickup_address == null || strlen($data->pickup_address) < 2) {
            $data->pickup_address = $customer->home_address;
        }

        //delivery_address
        if ($data->delivery_address == null || strlen($data->delivery_address) < 2) {
            $data->delivery_address = $data->pickup_address;
        }
        $data->status = strtoupper($data->status);
        return $data;
    }


    //belongs to customer
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    /* 

id
created_at
updated_at
user_id
 

total_amount
payment_status
payment_method
payment_date
stripe_payment_link
payment_reference
payment_notes
customer_photos
scheduled_pickup_time
assigned_driver_id
driver_id
actual_pickup_time
pickup_notes
laundry_delivery_time
washer_assignment_time
assigned_washer_id
washer_id
washing_start_time
washing_end_time
drying_start_time
drying_end_time
scheduled_delivery_time
delivery_driver_id
actual_delivery_time
delivery_notes
final_payment_date
receipt_approved_date
rating
driver_amount
driving_distance
feedback
local_id
 
*/
    public function get_payment_link()
    {
        if ($this->stripe_payment_link != null && strlen($this->stripe_payment_link) > 5 && $this->payment_reference != null && strlen($this->payment_reference) > 2) {
            return $this->stripe_payment_link;
        }

        $stripe = env('STRIPE_KEY');
        $stripe = new \Stripe\StripeClient(
            env('STRIPE_KEY')
        );

        $name = "Order #" . $this->id . ", " . $this->customer->name . " - " . date('Y-m-d H:i:s');
        $resp = null;
        try {
            $resp = $stripe->products->create([
                'name' => $name,
                'default_price_data' => [
                    'currency' => 'cad',
                    'unit_amount' => $this->total_amount * 100,
                ],
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
        if ($resp == null) {
            throw new \Exception("Error Processing Request", 1);
        }
        if ($resp->default_price == null) {
            throw new \Exception("Error Processing Request", 1);
        }
        $linkResp = null;
        try {
            $linkResp = $stripe->paymentLinks->create([
                'currency' => 'cad',
                'line_items' => [
                    [
                        'price' => $resp->default_price,
                        'quantity' => 1,
                    ]
                ]
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
        if ($linkResp == null) {
            throw new \Exception("Error Processing Request", 1);
        }

        $this->stripe_payment_link = $linkResp->url;
        $this->payment_reference = $linkResp->id;
        $this->save();
        return $linkResp->url;
    }

    //is_order_paid check with stripe
    public function is_order_paid()
    {
        //check payment_status is yes
        if ($this->payment_status == 'Paid') {
            return 'Paid';
        }

        //check if stripe_payment_link is not empty
        if ($this->stripe_payment_link == null || strlen($this->stripe_payment_link) < 5) {
            return 'Not Paid';
        }

        $stripe = new \Stripe\StripeClient(
            env('STRIPE_KEY')
        );

        try {
            $paymentLink = $stripe->paymentLinks->retrieve(
                $this->payment_reference
            );

            // Check the status of the payment link
            if ($paymentLink->status == 'paid') {
                $sql = "UPDATE laundry_orders SET payment_status = 'Paid', payment_date = '" . date('Y-m-d H:i:s') . "' WHERE id = " . $this->id;
                $this->payment_status = 'Paid';
                $this->payment_date = date('Y-m-d H:i:s');
                $this->save();
                return 'Paid';
            } elseif ($paymentLink->status === 'requires_payment_method') {
                return 'Not Paid';
            } else {
                return 'Not Paid';
            }
        } catch (\Throwable $th) {
            throw $th;
        }
        return 'Not Paid';

        $payment = null;
        try {
            $payment = $stripe->paymentIntents->retrieve(
                $this->payment_reference,
                []
            );
        } catch (\Throwable $th) {
            throw $th;
        }
        if ($payment == null) {
            return 'Not Paid';
        }
        if ($payment->status == 'succeeded') {
            $this->payment_status = 'Paid';
            $this->payment_date = date('Y-m-d H:i:s');
            $this->save();
            return 'Paid';
        }
        return 'Not Paid';
    }

    //getter for driver_text attribute
    public function getDriverTextAttribute()
    {
        if ($this->driver_id == null) {
            return "Not Assigned";
        }
        $driver = User::find($this->driver_id);
        if ($driver == null) {
            return "Not Assigned";
        }
        return $driver->name;
    }
    //appends driver_text
    protected $appends = ['driver_text'];

    /* //status getter
    public function getStatusAttribute($value)
    {
        $accepted_tasks = [
            'BILLING',
            'READY FOR PAYMENT',
            'PICKUP',
            strtoupper('Picked Up'),
            strtoupper('Washing in Progress'),
            'ASSIGN WASHER',
            'READY FOR DELIVERY',
            'OUT FOR DELIVERY',
            'DELIVERED',
            'COMPLETED',
            'PENDING',
        ];
        //check if value is in accepted tasks
        if (in_array($value, $accepted_tasks)) {
            return $value;
        }
        $status = $value;
        if (!in_array($value, $accepted_tasks)) {
            $status = 'PENDING';
            $sql = "UPDATE laundry_orders SET status = 'PENDING' WHERE id = " . $this->id;
            DB::update($sql);
        }
        return $status;
    } */

    //getter for customer_photos
    public function getCustomerPhotosAttribute($value)
    {
        $images = Image::where('product_id', $this->local_id)->get();
        $items = [];
        foreach ($images as $key => $value) {
            $val['id'] = $value->id;
            $val['src'] = $value->src;
            $val['type'] = $value->type;
            $items[] = $val;
        }
        return json_encode($items);
    }
}
