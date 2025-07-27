<?php

namespace App\Admin\Controllers;

use App\Models\CourseProgress;
use App\Models\Course;
use App\Models\User;
use App\Models\CourseUnit;
use App\Models\CourseMaterial;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CourseProgressController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Course Progress';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseProgress());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->disableCreateButton();
        $grid->model()->with(['user', 'course', 'unit', 'material'])->orderBy('updated_at', 'desc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('user.name', __('Student'))
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
            
        $grid->column('unit.title', __('Current Unit'))
            ->limit(25)
            ->sortable();
            
        $grid->column('material.title', __('Current Material'))
            ->limit(25);
            
        $grid->column('progress_percentage', __('Progress'))
            ->display(function ($progress) {
                $color = $progress >= 75 ? 'success' : ($progress >= 50 ? 'warning' : 'danger');
                return "<div class='progress'>
                    <div class='progress-bar progress-bar-{$color}' style='width: {$progress}%'>
                        {$progress}%
                    </div>
                </div>";
            })
            ->sortable();
            
        $grid->column('completion_status', __('Status'))
            ->label([
                'not_started' => 'default',
                'in_progress' => 'warning',
                'completed' => 'success',
                'paused' => 'info'
            ]);
            
        $grid->column('time_spent_minutes', __('Time Spent'))
            ->display(function ($minutes) {
                if ($minutes < 60) {
                    return $minutes . ' min';
                }
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
            })
            ->sortable();

        $grid->column('last_accessed_at', __('Last Access'))
            
            ->sortable();

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('user.name', 'Student Name');
            
            $filter->equal('course_id', 'Course')->select(
                Course::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );
            
            $filter->equal('completion_status', 'Status')->select([
                'not_started' => 'Not Started',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'paused' => 'Paused'
            ]);
            
            $filter->between('progress_percentage', 'Progress (%)');
            $filter->between('last_accessed_at', 'Last Access Date')->date();
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
        $show = new Show(CourseProgress::findOrFail($id));

        $show->panel()
            ->style('primary')
            ->title('Progress Details');

        $show->field('id', __('ID'));
        $show->field('user.name', __('Student'));
        $show->field('user.email', __('Student Email'));
        $show->field('course.title', __('Course'));
        $show->field('unit.title', __('Current Unit'));
        $show->field('material.title', __('Current Material'));
        
        $show->divider();
        
        $show->field('progress_percentage', __('Progress Percentage'))->as(function ($progress) {
            return $progress . '%';
        });
        
        $show->field('completion_status', __('Completion Status'))->label([
            'not_started' => 'default',
            'in_progress' => 'warning',
            'completed' => 'success',
            'paused' => 'info'
        ]);
        
        $show->field('time_spent_minutes', __('Time Spent'))->as(function ($minutes) {
            if ($minutes < 60) {
                return $minutes . ' minutes';
            }
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $hours . ' hour(s)' . ($mins > 0 ? ' ' . $mins . ' minute(s)' : '');
        });
        
        $show->field('last_accessed_at', __('Last Accessed At'));
        $show->field('notes', __('Notes'));
        
        $show->field('created_at', __('Started At'));
        $show->field('updated_at', __('Last Updated'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CourseProgress());

        $form->tab('Progress Information', function ($form) {
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
                
            $form->select('unit_id', __('Current Unit'))
                ->options(function ($id) {
                    $unit = CourseUnit::find($id);
                    if ($unit) {
                        return [$unit->id => $unit->title];
                    }
                })
                ->help('Current unit being studied');
                
            $form->select('material_id', __('Current Material'))
                ->options(function ($id) {
                    $material = CourseMaterial::find($id);
                    if ($material) {
                        return [$material->id => $material->title];
                    }
                })
                ->help('Current material being studied');
        });

        $form->tab('Progress Details', function ($form) {
            $form->number('progress_percentage', __('Progress Percentage'))
                ->default(0)
                ->min(0)
                ->max(100)
                ->rules('required|integer|min:0|max:100')
                ->help('Overall course completion percentage');
                
            $form->select('completion_status', __('Completion Status'))
                ->options([
                    'not_started' => 'Not Started',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'paused' => 'Paused'
                ])
                ->rules('required')
                ->default('not_started');
                
            $form->number('time_spent_minutes', __('Time Spent (Minutes)'))
                ->default(0)
                ->min(0)
                ->rules('required|integer|min:0')
                ->help('Total time spent on course');
                
            $form->datetime('last_accessed_at', __('Last Accessed'))
                ->help('When student last accessed the course');
                
            $form->textarea('notes', __('Notes'))
                ->rows(4)
                ->help('Additional notes about progress');
        });

        return $form;
    }
}
