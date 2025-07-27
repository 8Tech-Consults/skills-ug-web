<?php

namespace App\Admin\Controllers;

use App\Models\CourseReview;
use App\Models\Course;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CourseReviewController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Course Reviews';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseReview());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->model()->with(['user', 'course'])->orderBy('created_at', 'desc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('user.name', __('Reviewer'))
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
            
        $grid->column('rating', __('Rating'))
            ->display(function ($rating) {
                $stars = '';
                for ($i = 1; $i <= 5; $i++) {
                    $stars .= $i <= $rating ? '★' : '☆';
                }
                return "<span style='color: #f39c12;'>$stars</span> ($rating/5)";
            })
            ->sortable();
            
        $grid->column('review_title', __('Title'))
            ->limit(30)
            ->sortable();
            
        $grid->column('review_text', __('Review'))
            ->limit(50);
            
        $grid->column('is_verified', __('Verified'))
            ->switch([
                'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => 'No', 'color' => 'default'],
            ]);
            
        $grid->column('status', __('Status'))
            ->select([
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected'
            ])
            ->dot([
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger'
            ]);

        $grid->column('helpful_count', __('Helpful'))
            ->badge('info')
            ->sortable();

        $grid->column('created_at', __('Reviewed'))
            
            ->sortable();

        // Quick actions
        $grid->actions(function ($actions) {
            $row = $actions->row;
            
            if ($row->status === 'pending') {
                $actions->append('<a href="' . admin_url('course-reviews/' . $row->id . '/approve') . '" class="btn btn-xs btn-success">Approve</a>');
                $actions->append('<a href="' . admin_url('course-reviews/' . $row->id . '/reject') . '" class="btn btn-xs btn-danger">Reject</a>');
            }
        });

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('user.name', 'Reviewer Name');
            $filter->like('review_title', 'Review Title');
            
            $filter->equal('course_id', 'Course')->select(
                Course::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );
            
            $filter->equal('rating', 'Rating')->select([
                5 => '5 Stars',
                4 => '4 Stars',
                3 => '3 Stars',
                2 => '2 Stars',
                1 => '1 Star'
            ]);
            
            $filter->equal('status', 'Status')->select([
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected'
            ]);
            
            $filter->equal('is_verified', 'Verified Purchase')->select([
                1 => 'Yes',
                0 => 'No'
            ]);
            
            $filter->between('created_at', 'Review Date')->date();
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
        $show = new Show(CourseReview::findOrFail($id));

        $show->panel()
            ->style('primary')
            ->title('Review Details');

        $show->field('id', __('ID'));
        $show->field('user.name', __('Reviewer'));
        $show->field('user.email', __('Reviewer Email'));
        $show->field('course.title', __('Course'));
        $show->field('course.instructor_name', __('Instructor'));
        
        $show->divider();
        
        $show->field('rating', __('Rating'))->as(function ($rating) {
            $stars = '';
            for ($i = 1; $i <= 5; $i++) {
                $stars .= $i <= $rating ? '★' : '☆';
            }
            return $stars . " ($rating/5)";
        });
        
        $show->field('review_title', __('Review Title'));
        $show->field('review_text', __('Review Text'));
        
        $show->divider();
        
        $show->field('is_verified', __('Verified Purchase'))->using([1 => 'Yes', 0 => 'No'])->badge([
            1 => 'success',
            0 => 'default'
        ]);
        
        $show->field('status', __('Status'))->badge([
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger'
        ]);
        
        $show->field('helpful_count', __('Helpful Count'));
        $show->field('admin_response', __('Admin Response'));
        
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
        $form = new Form(new CourseReview());

        $form->tab('Review Information', function ($form) {
            $form->select('user_id', __('Reviewer'))
                ->options(function ($id) {
                    $user = User::find($id);
                    if ($user) {
                        return [$user->id => $user->name . ' (' . $user->email . ')'];
                    }
                })
                ->ajax('/admin/api/users')
                ->rules('required')
                ->help('Search and select reviewer');
                
            $form->select('course_id', __('Course'))
                ->options(Course::where('status', 'active')
                    ->pluck('title', 'id'))
                ->rules('required')
                ->help('Select course');
                
            $form->select('rating', __('Rating'))
                ->options([
                    5 => '5 Stars - Excellent',
                    4 => '4 Stars - Very Good',
                    3 => '3 Stars - Good',
                    2 => '2 Stars - Fair',
                    1 => '1 Star - Poor'
                ])
                ->rules('required')
                ->help('Select rating');
        });

        $form->tab('Review Content', function ($form) {
            $form->text('review_title', __('Review Title'))
                ->rules('required|string|max:255')
                ->help('Brief title for the review');
                
            $form->textarea('review_text', __('Review Text'))
                ->rules('required|string')
                ->rows(6)
                ->help('Detailed review content');
        });

        $form->tab('Settings', function ($form) {
            $form->switch('is_verified', __('Verified Purchase'))
                ->default(0)
                ->help('Was this review from a verified course purchase?');
                
            $form->select('status', __('Status'))
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected'
                ])
                ->rules('required')
                ->default('pending');
                
            $form->number('helpful_count', __('Helpful Count'))
                ->default(0)
                ->min(0)
                ->help('Number of users who found this review helpful');
                
            $form->textarea('admin_response', __('Admin Response'))
                ->rows(3)
                ->help('Optional response from admin');
        });

        return $form;
    }
}
