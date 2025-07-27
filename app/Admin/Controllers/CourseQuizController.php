<?php

namespace App\Admin\Controllers;

use App\Models\CourseQuiz;
use App\Models\CourseUnit;
use App\Models\Course;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CourseQuizController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Course Quizzes';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseQuiz());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->model()->with(['unit.course'])->orderBy('created_at', 'desc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();
        /* 
        $grid->column('unit.course.title', __('Course'))
            ->limit(25)
            ->sortable();
             */
        $grid->column('unit.title', __('Unit'))
            ->display(function ($title) {
                return "<strong>$title</strong>";
            })
            ->limit(25)
            ->sortable();

        $grid->column('title', __('Quiz Title'))
            ->limit(40)
            ->sortable();

        $grid->column('description', __('Description'))
            ->limit(50);

        $grid->column('quiz_type', __('Type'))
            ->label([
                'practice' => 'info',
                'graded' => 'warning',
                'final' => 'danger'
            ]);

        $grid->column('total_questions', __('Questions'))
            ->badge('primary')
            ->sortable();

        $grid->column('time_limit_minutes', __('Time Limit'))
            ->display(function ($minutes) {
                return $minutes ? $minutes . ' min' : 'No limit';
            })
            ->sortable();

        $grid->column('passing_score', __('Pass Score'))
            ->display(function ($score) {
                return $score . '%';
            })
            ->sortable();

        $grid->column('attempts_allowed', __('Attempts'))
            ->display(function ($attempts) {
                return $attempts == -1 ? 'Unlimited' : $attempts;
            })
            ->sortable();

        $grid->column('status', __('Status'))
            ->select([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'draft' => 'Draft'
            ])
            ->dot([
                'active' => 'success',
                'inactive' => 'danger',
                'draft' => 'warning'
            ]);

        $grid->column('created_at', __('Created'))

            ->sortable();

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('title', 'Quiz Title');

            $filter->equal('unit.course_id', 'Course')->select(
                Course::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );

            $filter->equal('unit_id', 'Unit')->select(
                CourseUnit::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );

            $filter->equal('quiz_type', 'Quiz Type')->select([
                'practice' => 'Practice',
                'graded' => 'Graded',
                'final' => 'Final Exam'
            ]);

            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'draft' => 'Draft'
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
        $show = new Show(CourseQuiz::findOrFail($id));

        $show->panel()
            ->style('primary')
            ->title('Quiz Details');

        $show->field('id', __('ID'));
        $show->field('unit.course.title', __('Course'));
        $show->field('unit.title', __('Unit'));
        $show->field('title', __('Quiz Title'));
        $show->field('description', __('Description'));

        $show->divider();

        $show->field('quiz_type', __('Quiz Type'))->label([
            'practice' => 'info',
            'graded' => 'warning',
            'final' => 'danger'
        ]);

        $show->field('total_questions', __('Total Questions'));
        $show->field('time_limit_minutes', __('Time Limit'))->as(function ($minutes) {
            return $minutes ? $minutes . ' minutes' : 'No time limit';
        });
        $show->field('passing_score', __('Passing Score'))->as(function ($score) {
            return $score . '%';
        });
        $show->field('attempts_allowed', __('Attempts Allowed'))->as(function ($attempts) {
            return $attempts == -1 ? 'Unlimited' : $attempts;
        });

        $show->field('instructions', __('Instructions'));

        $show->field('status', __('Status'))->badge([
            'active' => 'success',
            'inactive' => 'danger',
            'draft' => 'warning'
        ]);

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
        $form = new Form(new CourseQuiz());

        $form->tab('Basic Information', function ($form) {
            $form->select('unit_id', __('Course Unit'))
                ->options(function ($id) {
                    $unit = CourseUnit::with('course')->find($id);
                    if ($unit) {
                        return [$unit->id => $unit->course->title . ' - ' . $unit->title];
                    }
                })
                ->ajax('/admin/api/course-units')
                ->rules('required')
                ->help('Select course unit this quiz belongs to');

            $form->text('title', __('Quiz Title'))
                ->rules('required|string|max:255')
                ->help('Enter quiz title');

            $form->textarea('description', __('Description'))
                ->rows(4)
                ->help('Brief description of the quiz');

            $form->select('quiz_type', __('Quiz Type'))
                ->options([
                    'practice' => 'Practice Quiz',
                    'graded' => 'Graded Quiz',
                    'final' => 'Final Exam'
                ])
                ->rules('required')
                ->default('practice')
                ->help('Select quiz type');
        });

        $form->tab('Quiz Settings', function ($form) {
            $form->number('total_questions', __('Total Questions'))
                ->default(10)
                ->min(1)
                ->rules('required|integer|min:1')
                ->help('Number of questions in this quiz');

            $form->number('time_limit_minutes', __('Time Limit (Minutes)'))
                ->min(0)
                ->default(30)
                ->help('Time limit in minutes (0 for no limit)');

            $form->number('passing_score', __('Passing Score (%)'))
                ->default(70)
                ->min(0)
                ->max(100)
                ->rules('required|integer|min:0|max:100')
                ->help('Minimum percentage to pass');

            $form->number('attempts_allowed', __('Attempts Allowed'))
                ->default(3)
                ->min(-1)
                ->rules('required|integer|min:-1')
                ->help('Number of attempts allowed (-1 for unlimited)');
        });

        $form->tab('Instructions & Status', function ($form) {
            $form->quill('instructions', __('Quiz Instructions'))
                ->help('Instructions for students taking the quiz');

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
