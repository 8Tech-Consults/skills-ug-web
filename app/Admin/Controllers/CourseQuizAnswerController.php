<?php

namespace App\Admin\Controllers;

use App\Models\CourseQuizAnswer;
use App\Models\CourseQuiz;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CourseQuizAnswerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Quiz Answers & Attempts';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseQuizAnswer());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->disableCreateButton();
        $grid->model()->with(['user', 'quiz.unit.course'])->orderBy('created_at', 'desc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('user.name', __('Student'))
            ->display(function ($name) {
                return "<strong>$name</strong>";
            })
            ->limit(25)
            ->sortable();
            
    /*     $grid->column('quiz.unit.course.title', __('Course'))
            ->limit(25)
            ->sortable(); */
            
        $grid->column('quiz.title', __('Quiz'))
            ->display(function ($title) {
                return "<span class='text-primary'>$title</span>";
            })
            ->limit(30)
            ->sortable();
            
        $grid->column('attempt_number', __('Attempt'))
            ->display(function ($attempt) {
                return "#{$attempt}";
            })
            ->sortable();
            
        $grid->column('score_percentage', __('Score'))
            ->display(function ($score) {
                $color = $score >= 90 ? 'success' : ($score >= 70 ? 'warning' : 'danger');
                return "<span class='label label-{$color}'>{$score}%</span>";
            })
            ->sortable();
            
        $grid->column('status', __('Status'))
            ->label([
                'in_progress' => 'warning',
                'completed' => 'success',
                'abandoned' => 'default',
                'time_expired' => 'danger'
            ]);
            
        $grid->column('time_taken_minutes', __('Time Taken'))
            ->display(function ($minutes) {
                if ($minutes < 60) {
                    return $minutes . ' min';
                }
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
            })
            ->sortable();
            
        $grid->column('is_passed', __('Passed'))
            ->switch([
                'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => 'No', 'color' => 'danger'],
            ]);

        $grid->column('started_at', __('Started'))
            
            ->sortable();
            
        $grid->column('completed_at', __('Completed'))
            
            ->sortable();

        // Actions
        $grid->actions(function ($actions) {
            $actions->disableEdit();
            $actions->disableDelete();
        });

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('user.name', 'Student Name');
            
            $filter->equal('quiz.unit.course_id', 'Course')->select(
                \App\Models\Course::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );
            
            $filter->equal('quiz_id', 'Quiz')->select(
                CourseQuiz::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );
            
            $filter->equal('status', 'Status')->select([
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'abandoned' => 'Abandoned',
                'time_expired' => 'Time Expired'
            ]);
            
            $filter->equal('is_passed', 'Passed')->select([
                1 => 'Yes',
                0 => 'No'
            ]);
            
            $filter->between('score_percentage', 'Score Range');
            $filter->between('started_at', 'Start Date')->datetime();
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
        $show = new Show(CourseQuizAnswer::findOrFail($id));

        $show->panel()
            ->style('primary')
            ->title('Quiz Attempt Details');

        $show->field('id', __('ID'));
        $show->field('user.name', __('Student'));
        $show->field('user.email', __('Student Email'));
        $show->field('quiz.unit.course.title', __('Course'));
        $show->field('quiz.title', __('Quiz'));
        
        $show->divider();
        
        $show->field('attempt_number', __('Attempt Number'));
        $show->field('score_percentage', __('Score'))->as(function ($score) {
            return $score . '%';
        });
        $show->field('total_questions', __('Total Questions'));
        $show->field('correct_answers', __('Correct Answers'));
        
        $show->field('status', __('Status'))->label([
            'in_progress' => 'warning',
            'completed' => 'success',
            'abandoned' => 'default',
            'time_expired' => 'danger'
        ]);
        
        $show->field('is_passed', __('Passed'))->using([1 => 'Yes', 0 => 'No'])->badge([
            1 => 'success',
            0 => 'danger'
        ]);
        
        $show->divider();
        
        $show->field('time_taken_minutes', __('Time Taken'))->as(function ($minutes) {
            if ($minutes < 60) {
                return $minutes . ' minutes';
            }
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $hours . ' hour(s)' . ($mins > 0 ? ' ' . $mins . ' minute(s)' : '');
        });
        
        $show->field('started_at', __('Started At'));
        $show->field('completed_at', __('Completed At'));
        
        $show->field('answers_data', __('Detailed Answers'))->json();
        $show->field('feedback', __('Feedback'));
        
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
        $form = new Form(new CourseQuizAnswer());

        $form->tab('Attempt Information', function ($form) {
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
                
            $form->select('quiz_id', __('Quiz'))
                ->options(CourseQuiz::where('status', 'active')
                    ->pluck('title', 'id'))
                ->rules('required')
                ->help('Select quiz');
                
            $form->number('attempt_number', __('Attempt Number'))
                ->default(1)
                ->min(1)
                ->rules('required|integer|min:1')
                ->help('Which attempt this is for the student');
        });

        $form->tab('Results', function ($form) {
            $form->number('score_percentage', __('Score Percentage'))
                ->default(0)
                ->min(0)
                ->max(100)
                ->rules('required|integer|min:0|max:100')
                ->help('Final score as percentage');
                
            $form->number('total_questions', __('Total Questions'))
                ->default(0)
                ->min(0)
                ->rules('required|integer|min:0')
                ->help('Total number of questions');
                
            $form->number('correct_answers', __('Correct Answers'))
                ->default(0)
                ->min(0)
                ->rules('required|integer|min:0')
                ->help('Number of correct answers');
                
            $form->switch('is_passed', __('Passed'))
                ->default(0)
                ->help('Whether the student passed the quiz');
        });

        $form->tab('Timing & Status', function ($form) {
            $form->select('status', __('Status'))
                ->options([
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'abandoned' => 'Abandoned',
                    'time_expired' => 'Time Expired'
                ])
                ->rules('required')
                ->default('completed');
                
            $form->number('time_taken_minutes', __('Time Taken (Minutes)'))
                ->default(0)
                ->min(0)
                ->rules('required|integer|min:0')
                ->help('Total time taken to complete');
                
            $form->datetime('started_at', __('Started At'))
                ->rules('required')
                ->help('When the quiz attempt started');
                
            $form->datetime('completed_at', __('Completed At'))
                ->help('When the quiz was completed');
        });

        $form->tab('Additional Data', function ($form) {
            $form->json('answers_data', __('Detailed Answers'))
                ->help('JSON data containing detailed answer information');
                
            $form->textarea('feedback', __('Feedback'))
                ->rows(4)
                ->help('Feedback for the student');
        });

        return $form;
    }
}
