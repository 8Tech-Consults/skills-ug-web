<?php

namespace App\Admin\Controllers;

use App\Models\CourseSubscription;
use App\Models\Course;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Carbon\Carbon;

class CourseSubscriptionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Course Subscriptions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseSubscription());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->model()->with(['user', 'course'])->orderBy('id', 'desc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('user.name', __('Student'))
            ->display(function ($name) {
                return "<strong>$name</strong>";
            })
            ->limit(25)
            ->sortable();
            
        $grid->column('user.email', __('Email'))
            ->limit(30);
            
        $grid->column('course.title', __('Course'))
            ->display(function ($title) {
                return "<span class='text-primary'>$title</span>";
            })
            ->limit(30)
            ->sortable();
            
        $grid->column('subscription_type', __('Type'))
            ->label([
                'free' => 'default',
                'paid' => 'primary',
                'premium' => 'warning'
            ]);
            
        $grid->column('payment_amount', __('Amount'))
            ->display(function ($amount) {
                if ($amount > 0) {
                    $currency = $this->getAttribute('payment_currency') ?: 'UGX';
                    return $currency . ' ' . number_format($amount, 0);
                }
                return 'Free';
            })
            ->sortable();
            
        $grid->column('payment_status', __('Payment'))
            ->label([
                'pending' => 'warning',
                'completed' => 'success',
                'failed' => 'danger',
                'refunded' => 'info'
            ]);
            
        $grid->column('status', __('Status'))
            ->select([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'expired' => 'Expired',
                'cancelled' => 'Cancelled'
            ])
            ->dot([
                'active' => 'success',
                'inactive' => 'warning',
                'expired' => 'danger',
                'cancelled' => 'default'
            ]);

        $grid->column('subscribed_at', __('Subscribed'))
            ->display(function ($value) {
                return $value ? Carbon::parse($value)->format('M d, Y') : '-';
            })
            ->sortable();
            
        $grid->column('expires_at', __('Expires'))
            ->display(function ($value) {
                if (!$value) return 'Never';
                
                $expires = Carbon::parse($value);
                $now = Carbon::now();
                
                if ($expires->isPast()) {
                    return "<span class='text-danger'>{$expires->format('M d, Y')} (Expired)</span>";
                } else {
                    $days = $now->diffInDays($expires);
                    $color = $days <= 7 ? 'text-warning' : 'text-success';
                    return "<span class='$color'>{$expires->format('M d, Y')} ({$days} days)</span>";
                }
            })
            ->sortable();

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('user.name', 'Student Name');
            $filter->like('user.email', 'Student Email');
            
            $filter->equal('course_id', 'Course')->select(
                Course::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );
            
            $filter->equal('subscription_type', 'Subscription Type')->select([
                'free' => 'Free',
                'paid' => 'Paid',
                'premium' => 'Premium'
            ]);
            
            $filter->equal('payment_status', 'Payment Status')->select([
                'pending' => 'Pending',
                'completed' => 'Completed',
                'failed' => 'Failed',
                'refunded' => 'Refunded'
            ]);
            
            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'expired' => 'Expired',
                'cancelled' => 'Cancelled'
            ]);
            
            $filter->between('payment_amount', 'Payment Amount');
            $filter->between('subscribed_at', 'Subscription Date')->date();
            $filter->between('expires_at', 'Expiry Date')->date();
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(CourseSubscription::findOrFail($id));

        $show->panel()
            ->style('primary')
            ->title('Subscription Details');

        $show->field('id', __('ID'));
        $show->field('user.name', __('Student Name'));
        $show->field('user.email', __('Student Email'));
        $show->field('course.title', __('Course Title'));
        $show->field('course.instructor_name', __('Instructor'));
        
        $show->divider();
        
        $show->field('subscription_type', __('Subscription Type'))->label([
            'free' => 'default',
            'paid' => 'primary',
            'premium' => 'warning'
        ]);
        $show->field('status', __('Status'))->badge([
            'active' => 'success',
            'inactive' => 'warning',
            'expired' => 'danger',
            'cancelled' => 'default'
        ]);
        
        $show->divider();
        
        $show->field('payment_amount', __('Payment Amount'))->as(function ($amount) {
            if ($amount > 0) {
                $currency = $this->getAttribute('payment_currency') ?: 'UGX';
                return $currency . ' ' . number_format($amount, 0);
            }
            return 'Free';
        });
        $show->field('payment_currency', __('Payment Currency'));
        $show->field('payment_status', __('Payment Status'))->label([
            'pending' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'refunded' => 'info'
        ]);
        
        $show->divider();
        
        $show->field('subscribed_at', __('Subscribed At'));
        $show->field('expires_at', __('Expires At'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CourseSubscription());

        $form->tab('Subscription Details', function ($form) {
            $form->select('user_id', __('Student'))
                ->options(function ($id) {
                    $user = User::find($id);
                    if ($user) {
                        return [$user->id => $user->name . ' (' . $user->email . ')'];
                    }
                })
                ->ajax('/admin/api/users')
                ->rules('required')
                ->help('Search and select student');
                
            $form->select('course_id', __('Course'))
                ->options(Course::where('status', 'active')
                    ->pluck('title', 'id'))
                ->rules('required')
                ->help('Select course');
                
            $form->select('subscription_type', __('Subscription Type'))
                ->options([
                    'free' => 'Free',
                    'paid' => 'Paid',
                    'premium' => 'Premium'
                ])
                ->rules('required')
                ->default('paid');
        });

        $form->tab('Payment Information', function ($form) {
            $form->currency('payment_amount', __('Payment Amount'))
                ->symbol('UGX')
                ->rules('required|numeric|min:0')
                ->help('Amount paid for subscription');
                
            $form->hidden('payment_currency')->default('UGX');
            
            $form->select('payment_status', __('Payment Status'))
                ->options([
                    'pending' => 'Pending',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'refunded' => 'Refunded'
                ])
                ->rules('required')
                ->default('pending');
        });

        $form->tab('Subscription Settings', function ($form) {
            $form->datetime('subscribed_at', __('Subscription Date'))
                ->default(date('Y-m-d H:i:s'))
                ->rules('required')
                ->help('When the subscription started');
                
            $form->datetime('expires_at', __('Expiry Date'))
                ->help('Leave empty for lifetime access');
                
            $form->select('status', __('Status'))
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'expired' => 'Expired',
                    'cancelled' => 'Cancelled'
                ])
                ->rules('required')
                ->default('active');
        });

        // Disable certain actions in form
        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
        });

        return $form;
    }
}
