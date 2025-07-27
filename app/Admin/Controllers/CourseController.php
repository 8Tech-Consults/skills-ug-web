<?php

namespace App\Admin\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;

class CourseController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Courses';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Course());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->model()->with(['category'])->orderBy('created_at', 'desc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();

        $grid->column('cover_image', __('Cover'))
            ->lightbox(['zooming' => true, 'width' => 50, 'height' => 50]);

        $grid->column('title', __('Course Title'))
            ->display(function ($title) {
                return "<strong>$title</strong>";
            })
            ->limit(30)
            ->sortable();

        $grid->column('category.name', __('Category'))
            ->badge('info');

        $grid->column('instructor_name', __('Instructor'))
            ->limit(20)
            ->sortable();

        $grid->column('price', __('Price'))
            ->display(function ($price) {
                $currency = $this->getAttribute('currency') ?: 'UGX';
                return $currency . ' ' . number_format($price, 0);
            })
            ->sortable();

        $grid->column('duration_hours', __('Duration'))
            ->display(function ($hours) {
                return $hours . ' hrs';
            })
            ->sortable();

        $grid->column('difficulty_level', __('Level'))
            ->label([
                'beginner' => 'success',
                'intermediate' => 'warning',
                'advanced' => 'danger'
            ]);

        $grid->column('rating_average', __('Rating'))
            ->display(function ($rating) {
                $stars = '';
                for ($i = 1; $i <= 5; $i++) {
                    $stars .= $i <= $rating ? '★' : '☆';
                }
                return $stars . ' (' . $rating . ')';
            })
            ->sortable();

        $grid->column('enrollment_count', __('Enrollments'))
            ->badge('primary')
            ->sortable();

        $grid->column('featured', __('Featured'))
            ->switch([
                'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => 'No', 'color' => 'default'],
            ]);

        $grid->column('status', __('Status'))
            ->select([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'draft' => 'Draft'
            ], 'active')
            ->dot([
                'active' => 'success',
                'inactive' => 'danger',
                'draft' => 'warning'
            ]);

        $grid->column('created_at', __('Created'))

            ->sortable();

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('title', 'Course Title');
            $filter->like('instructor_name', 'Instructor Name');

            $filter->equal('category_id', 'Category')->select(
                CourseCategory::where('status', 'active')
                    ->pluck('name', 'id')
                    ->toArray()
            );

            $filter->equal('difficulty_level', 'Difficulty Level')->select([
                'beginner' => 'Beginner',
                'intermediate' => 'Intermediate',
                'advanced' => 'Advanced'
            ]);

            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'draft' => 'Draft'
            ]);

            $filter->equal('featured', 'Featured')->select([
                1 => 'Yes',
                0 => 'No'
            ]);

            $filter->between('price', 'Price Range');
            $filter->between('created_at', 'Created Date')->date();
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
        $show = new Show(Course::findOrFail($id));

        $show->panel()
            ->style('primary')
            ->title('Course Details');

        $show->field('id', __('ID'));
        $show->field('title', __('Course Title'));
        $show->field('slug', __('Slug'));
        $show->field('category.name', __('Category'));
        $show->field('description', __('Description'));
        $show->field('detailed_description', __('Detailed Description'));

        $show->divider();

        $show->field('instructor_name', __('Instructor Name'));
        $show->field('instructor_bio', __('Instructor Bio'));
        $show->field('instructor_avatar', __('Instructor Avatar'))->image();

        $show->divider();

        $show->field('cover_image', __('Cover Image'))->image();
        $show->field('preview_video', __('Preview Video'))->link();

        $show->divider();

        $show->field('price', __('Price'))->as(function ($price) {
            $currency = $this->getAttribute('currency') ?: 'UGX';
            return $currency . ' ' . number_format($price, 0);
        });
        $show->field('currency', __('Currency'));
        $show->field('duration_hours', __('Duration'))->as(function ($hours) {
            return $hours . ' hours';
        });
        $show->field('difficulty_level', __('Difficulty Level'))->badge([
            'beginner' => 'success',
            'intermediate' => 'warning',
            'advanced' => 'danger'
        ]);
        $show->field('language', __('Language'));

        $show->divider();

        $show->field('requirements', __('Requirements'))->json();
        $show->field('what_you_learn', __('What You Learn'))->json();
        $show->field('tags', __('Tags'))->json();

        $show->divider();

        $show->field('status', __('Status'))->badge([
            'active' => 'success',
            'inactive' => 'danger',
            'draft' => 'warning'
        ]);
        $show->field('featured', __('Featured'))->using([1 => 'Yes', 0 => 'No']);
        $show->field('rating_average', __('Rating Average'));
        $show->field('rating_count', __('Rating Count'));
        $show->field('enrollment_count', __('Enrollment Count'));

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
        $form = new Form(new Course());

        $form->tab('Basic Information', function ($form) {
            $form->select('category_id', __('Category'))
                ->options(CourseCategory::where('status', 'active')
                    ->pluck('name', 'id'))
                ->help('Select course category');

            $form->text('title', __('Course Title'))
                ->rules('required|string|max:255')
                ->help('Enter the course title');

            $form->text('slug', __('Slug'))
                ->rules('required|string|max:255|unique:courses,slug,{{id}}')
                ->help('URL-friendly version (auto-generated if empty)');

            $form->textarea('description', __('Short Description'))
                ->rules('required|string')
                ->rows(4)
                ->help('Brief description of the course');

            $form->quill('detailed_description', __('Detailed Description'))
                ->help('Comprehensive course description');
        });

        $form->tab('Instructor Information', function ($form) {
            $form->text('instructor_name', __('Instructor Name'))
                ->rules('required|string|max:255')
                ->help('Course instructor name');

            $form->textarea('instructor_bio', __('Instructor Bio'))
                ->rows(4)
                ->help('Brief biography of the instructor');

            $form->image('instructor_avatar', __('Instructor Avatar'))
                ->help('Instructor profile picture');
        });

        $form->tab('Media & Content', function ($form) {
            $form->image('cover_image', __('Cover Image'))
                ->help('Course cover image (recommended: 1280x720px)');

            $form->text('preview_video', __('Preview Video URL'))
                ->help('YouTube, Vimeo or direct video URL');
        });

        $form->tab('Pricing & Details', function ($form) {
            $form->currency('price', __('Price'))
                ->symbol('UGX')
                ->help('Course price in UGX');

            $form->hidden('currency')->default('UGX');

            $form->number('duration_hours', __('Duration (Hours)'))
                ->rules('required|integer|min:1')
                ->help('Total course duration in hours');

            $form->select('difficulty_level', __('Difficulty Level'))
                ->options([
                    'beginner' => 'Beginner',
                    'intermediate' => 'Intermediate',
                    'advanced' => 'Advanced'
                ])
                ->rules('required')
                ->default('beginner');

            $form->text('language', __('Language'))
                ->default('English')
                ->rules('required|string|max:100');
        });

        $form->tab('Learning Outcomes', function ($form) {
            $form->list('requirements', __('Requirements'))
                ->help('Prerequisites for taking this course');

            $form->list('what_you_learn', __('What You\'ll Learn'))
                ->help('Key learning outcomes and skills');

            $form->tags('tags', __('Tags'))
                ->help('Course tags for better discoverability');
        });

        $form->tab('Settings', function ($form) {
            $form->switch('featured', __('Featured Course'))
                ->default(0)
                ->help('Show in featured courses section');

            $form->select('status', __('Status'))
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'inactive' => 'Inactive'
                ])
                ->default('draft')
                ->rules('required');
        });

        // Form hooks
        $form->saving(function (Form $form) {
            // Auto-generate slug if empty
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->title);
            }
        });



        return $form;
    }
}
