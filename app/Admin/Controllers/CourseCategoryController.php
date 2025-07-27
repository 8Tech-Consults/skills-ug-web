<?php

namespace App\Admin\Controllers;

use App\Models\CourseCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;

class CourseCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Course Categories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseCategory());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->disableColumnSelector();
        $grid->model()->orderBy('sort_order', 'asc')->orderBy('name', 'asc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('name', __('Category Name'))
            ->display(function ($name) {
                return "<strong>$name</strong>";
            })
            ->sortable();
            
        $grid->column('slug', __('Slug'))
            ->label('info')
            ->sortable();
            
        $grid->column('description', __('Description'))
            ->limit(50);
            
        $grid->column('icon', __('Icon'))
            ->display(function ($icon) {
                return $icon ? "<i class='fa {$icon}'></i> {$icon}" : '-';
            });
            
        $grid->column('color', __('Color'))
            ->display(function ($color) {
                return $color ? "<span style='background: {$color}; padding: 4px 8px; border-radius: 3px; color: white;'>{$color}</span>" : '-';
            });
            
        $grid->column('sort_order', __('Sort Order'))
            ->editable()
            ->sortable()
            ->help('Lower numbers appear first');
            
        $grid->column('is_featured', __('Featured'))
            ->switch([
                'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => 'No', 'color' => 'default'],
            ]);
            
        $grid->column('status', __('Status'))
            ->select([
                'active' => 'Active',
                'inactive' => 'Inactive'
            ], 'active')
            ->dot([
                'active' => 'success',
                'inactive' => 'danger',
            ]);
            
        $grid->column('course_count', __('Courses'))
            ->display(function () {
                return $this->course_count ?? 0;
            })
            ->badge('info')
            ->sortable();

        $grid->column('created_at', __('Created')) 
            ->sortable();

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('name', 'Category Name');
            $filter->like('slug', 'Slug');
            
            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'inactive' => 'Inactive'
            ]);
            
            $filter->equal('is_featured', 'Featured')->select([
                1 => 'Yes',
                0 => 'No'
            ]);
            
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
        $show = new Show(CourseCategory::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('name', __('Category Name'));
        $show->field('slug', __('Slug'));
        $show->field('description', __('Description'));
        $show->field('icon', __('Icon'))->as(function ($icon) {
            return $icon ? "<i class='fa {$icon}'></i> {$icon}" : '-';
        })->unescape();
        $show->field('color', __('Color'))->as(function ($color) {
            return $color ? "<span style='background: {$color}; padding: 4px 8px; border-radius: 3px; color: white;'>{$color}</span>" : '-';
        })->unescape();
        $show->field('sort_order', __('Sort Order'));
        $show->field('is_featured', __('Featured'))->using([1 => 'Yes', 0 => 'No'])->badge([
            1 => 'success',
            0 => 'default'
        ]);
        $show->field('status', __('Status'))->badge([
            'active' => 'success',
            'inactive' => 'danger'
        ]);
        $show->field('course_count', __('Total Courses'));
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        // Show related courses
        $show->courses('Related Courses', function ($courses) {
            $courses->disableCreateButton();
            $courses->disableActions();
            $courses->disablePagination();
            
            $courses->column('title', 'Course Title');
            $courses->column('instructor_name', 'Instructor');
            $courses->column('price', 'Price');
            $courses->column('status', 'Status');
        });

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CourseCategory());

        $form->tab('Basic Information', function ($form) {
            $form->text('name', __('Category Name'))
                ->rules('required|string|max:255')
                ->help('Enter the category name');
                
            $form->text('slug', __('Slug'))
                ->rules('required|string|max:255|unique:course_categories,slug,{{id}}')
                ->help('URL-friendly version (auto-generated if empty)');
                
            $form->textarea('description', __('Description'))
                ->rules('nullable|string')
                ->rows(4)
                ->help('Brief description of the category');
        });

        $form->tab('Appearance', function ($form) {
            $form->icon('icon', __('Icon'))
                ->default('fa-book')
                ->help('FontAwesome icon for the category');
                
            $form->color('color', __('Color'))
                ->default('#3c8dbc')
                ->help('Theme color for the category');
                
            $form->number('sort_order', __('Sort Order'))
                ->default(0)
                ->min(0)
                ->help('Lower numbers appear first');
        });

        $form->tab('Settings', function ($form) {
            $form->switch('is_featured', __('Featured'))
                ->default(0)
                ->help('Show in featured categories section');
                
            $form->select('status', __('Status'))
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive'
                ])
                ->default('active')
                ->rules('required');
        });

        // Form hooks
        $form->saving(function (Form $form) {
            // Auto-generate slug if empty
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->name);
            }
        });

        return $form;
    }
}
