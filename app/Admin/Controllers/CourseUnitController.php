<?php

namespace App\Admin\Controllers;

use App\Models\CourseUnit;
use App\Models\Course;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CourseUnitController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Course Units';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseUnit());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->model()->with(['course'])->orderBy('course_id', 'asc')->orderBy('sort_order', 'asc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('course.title', __('Course'))
            ->display(function ($title) {
                return "<strong>$title</strong>";
            })
            ->limit(30)
            ->sortable();
            
        $grid->column('title', __('Unit Title'))
            ->display(function ($title) {
                $isPreview = $this->getAttribute('is_preview');
                return $isPreview ? "🎥 $title" : $title;
            })
            ->limit(40)
            ->sortable();
            
        $grid->column('description', __('Description'))
            ->limit(50);
            
        $grid->column('sort_order', __('Order'))
            ->editable()
            ->sortable()
            ->help('Lower numbers appear first');
            
        $grid->column('duration_minutes', __('Duration'))
            ->display(function ($minutes) {
                if ($minutes < 60) {
                    return $minutes . ' min';
                }
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
            })
            ->sortable();
            
        $grid->column('is_preview', __('Preview'))
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

        $grid->column('materials_count', __('Materials'))
            ->display(function () {
                return $this->materials()->count();
            })
            ->badge('info');

        $grid->column('created_at', __('Created'))
            
            ->sortable();

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('title', 'Unit Title');
            
            $filter->equal('course_id', 'Course')->select(
                Course::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );
            
            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'draft' => 'Draft'
            ]);
            
            $filter->equal('is_preview', 'Is Preview')->select([
                1 => 'Yes',
                0 => 'No'
            ]);
            
            $filter->between('duration_minutes', 'Duration (minutes)');
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
        $show = new Show(CourseUnit::findOrFail($id));

        $show->field('id', __('ID'));
        $show->field('course.title', __('Course'));
        $show->field('title', __('Unit Title'));
        $show->field('description', __('Description'));
        $show->field('sort_order', __('Sort Order'));
        $show->field('duration_minutes', __('Duration'))->as(function ($minutes) {
            if ($minutes < 60) {
                return $minutes . ' minutes';
            }
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $hours . ' hour(s)' . ($mins > 0 ? ' ' . $mins . ' minute(s)' : '');
        });
        $show->field('is_preview', __('Is Preview'))->using([1 => 'Yes', 0 => 'No'])->badge([
            1 => 'success',
            0 => 'default'
        ]);
        $show->field('status', __('Status'))->badge([
            'active' => 'success',
            'inactive' => 'danger',
            'draft' => 'warning'
        ]);
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        // Show related materials
        $show->materials('Unit Materials', function ($materials) {
            $materials->disableCreateButton();
            $materials->disableActions();
            $materials->disablePagination();
            
            $materials->column('title', 'Material Title');
            $materials->column('material_type', 'Type');
            $materials->column('sort_order', 'Order');
            $materials->column('status', 'Status');
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
        $form = new Form(new CourseUnit());

        $form->tab('Basic Information', function ($form) {
            $form->select('course_id', __('Course'))
                ->options(Course::where('status', '!=', 'inactive')
                    ->pluck('title', 'id'))
                ->rules('required')
                ->help('Select the course this unit belongs to');
                
            $form->text('title', __('Unit Title'))
                ->rules('required|string|max:255')
                ->help('Enter the unit title');
                
            $form->textarea('description', __('Description'))
                ->rows(4)
                ->help('Brief description of what this unit covers');
        });

        $form->tab('Settings', function ($form) {
            $form->number('sort_order', __('Sort Order'))
                ->default(1)
                ->min(1)
                ->rules('required|integer|min:1')
                ->help('Order in which this unit appears (lower numbers first)');
                
            $form->number('duration_minutes', __('Duration (Minutes)'))
                ->default(30)
                ->min(1)
                ->rules('required|integer|min:1')
                ->help('Estimated time to complete this unit');
                
            $form->switch('is_preview', __('Is Preview Unit'))
                ->default(0)
                ->help('Preview units can be accessed without enrollment');
                
            $form->select('status', __('Status'))
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'inactive' => 'Inactive'
                ])
                ->default('draft')
                ->rules('required');
        });

        return $form;
    }
}
