<?php

namespace App\Admin\Controllers;

use App\Models\CourseNotification;
use App\Models\Course;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CourseNotificationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Course Notifications';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseNotification());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->model()->with(['user', 'course'])->orderBy('created_at', 'desc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('user.name', __('Recipient'))
            ->display(function ($name) {
                return "<strong>$name</strong>";
            })
            ->limit(25)
            ->sortable();
            
        $grid->column('course.title', __('Course'))
            ->display(function ($title) {
                return "<span class='text-primary'>$title</span>";
            })
            ->limit(30)
            ->sortable();
            
        $grid->column('notification_type', __('Type'))
            ->label([
                'enrollment' => 'primary',
                'completion' => 'success',
                'reminder' => 'warning',
                'announcement' => 'info',
                'certificate' => 'default'
            ]);
            
        $grid->column('title', __('Title'))
            ->limit(40)
            ->sortable();
            
        $grid->column('message', __('Message'))
            ->limit(50);
            
        $grid->column('is_read', __('Read'))
            ->switch([
                'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => 'No', 'color' => 'warning'],
            ]);
            
        $grid->column('priority', __('Priority'))
            ->label([
                'low' => 'default',
                'normal' => 'primary',
                'high' => 'warning',
                'urgent' => 'danger'
            ]);

        $grid->column('sent_at', __('Sent'))
            
            ->sortable();
            
        $grid->column('read_at', __('Read At'))
            
            ->sortable();

        // Quick actions
        $grid->actions(function ($actions) {
            $row = $actions->row;
            
            if (!$row->is_read) {
                $actions->append('<a href="' . admin_url('course-notifications/' . $row->id . '/mark-read') . '" class="btn btn-xs btn-success">Mark Read</a>');
            }
        });

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('user.name', 'Recipient Name');
            $filter->like('title', 'Notification Title');
            
            $filter->equal('course_id', 'Course')->select(
                Course::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );
            
            $filter->equal('notification_type', 'Type')->select([
                'enrollment' => 'Enrollment',
                'completion' => 'Completion',
                'reminder' => 'Reminder',
                'announcement' => 'Announcement',
                'certificate' => 'Certificate'
            ]);
            
            $filter->equal('priority', 'Priority')->select([
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'High',
                'urgent' => 'Urgent'
            ]);
            
            $filter->equal('is_read', 'Read Status')->select([
                1 => 'Read',
                0 => 'Unread'
            ]);
            
            $filter->between('sent_at', 'Sent Date')->datetime();
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
        $show = new Show(CourseNotification::findOrFail($id));

        $show->panel()
            ->style('primary')
            ->title('Notification Details');

        $show->field('id', __('ID'));
        $show->field('user.name', __('Recipient'));
        $show->field('user.email', __('Recipient Email'));
        $show->field('course.title', __('Course'));
        
        $show->divider();
        
        $show->field('notification_type', __('Notification Type'))->label([
            'enrollment' => 'primary',
            'completion' => 'success',
            'reminder' => 'warning',
            'announcement' => 'info',
            'certificate' => 'default'
        ]);
        
        $show->field('priority', __('Priority'))->label([
            'low' => 'default',
            'normal' => 'primary',
            'high' => 'warning',
            'urgent' => 'danger'
        ]);
        
        $show->field('title', __('Title'));
        $show->field('message', __('Message'));
        
        $show->divider();
        
        $show->field('is_read', __('Is Read'))->using([1 => 'Yes', 0 => 'No'])->badge([
            1 => 'success',
            0 => 'warning'
        ]);
        
        $show->field('sent_at', __('Sent At'));
        $show->field('read_at', __('Read At'));
        
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
        $form = new Form(new CourseNotification());

        $form->tab('Notification Details', function ($form) {
            $form->select('user_id', __('Recipient'))
                ->options(function ($id) {
                    $user = User::find($id);
                    if ($user) {
                        return [$user->id => $user->name . ' (' . $user->email . ')'];
                    }
                })
                ->ajax('/admin/api/users')
                ->rules('required')
                ->help('Search and select recipient');
                
            $form->select('course_id', __('Course'))
                ->options(Course::where('status', 'active')
                    ->pluck('title', 'id'))
                ->help('Select related course (optional)');
                
            $form->select('notification_type', __('Notification Type'))
                ->options([
                    'enrollment' => 'Enrollment',
                    'completion' => 'Course Completion',
                    'reminder' => 'Reminder',
                    'announcement' => 'Announcement',
                    'certificate' => 'Certificate'
                ])
                ->rules('required')
                ->default('announcement');
        });

        $form->tab('Message Content', function ($form) {
            $form->text('title', __('Notification Title'))
                ->rules('required|string|max:255')
                ->help('Brief title for the notification');
                
            $form->textarea('message', __('Message'))
                ->rules('required|string')
                ->rows(6)
                ->help('Detailed notification message');
                
            $form->select('priority', __('Priority'))
                ->options([
                    'low' => 'Low',
                    'normal' => 'Normal',
                    'high' => 'High',
                    'urgent' => 'Urgent'
                ])
                ->rules('required')
                ->default('normal');
        });

        $form->tab('Status & Timing', function ($form) {
            $form->switch('is_read', __('Mark as Read'))
                ->default(0)
                ->help('Whether this notification has been read');
                
            $form->datetime('sent_at', __('Send Date/Time'))
                ->default(date('Y-m-d H:i:s'))
                ->rules('required')
                ->help('When to send this notification');
                
            $form->datetime('read_at', __('Read Date/Time'))
                ->help('When the notification was read (auto-filled)');
        });

        return $form;
    }
}
