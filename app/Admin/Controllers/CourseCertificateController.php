<?php

namespace App\Admin\Controllers;

use App\Models\CourseCertificate;
use App\Models\Course;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CourseCertificateController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Course Certificates';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseCertificate());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->model()->with(['user', 'course'])->orderBy('id', 'desc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();
        
        $grid->column('certificate_number', __('Certificate #'))
            ->display(function ($number) {
                return "<strong class='text-info'>$number</strong>";
            })
            ->sortable();
            
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
            
        $grid->column('completion_score', __('Score'))
            ->display(function ($score) {
                $color = $score >= 90 ? 'success' : ($score >= 80 ? 'warning' : 'info');
                return "<span class='label label-{$color}'>{$score}%</span>";
            })
            ->sortable();
            
        $grid->column('certificate_type', __('Type'))
            ->label([
                'completion' => 'primary',
                'achievement' => 'success',
                'participation' => 'info'
            ]);
            
        $grid->column('status', __('Status'))
            ->select([
                'active' => 'Active',
                'revoked' => 'Revoked',
                'expired' => 'Expired'
            ])
            ->dot([
                'active' => 'success',
                'revoked' => 'danger',
                'expired' => 'warning'
            ]);
            
        $grid->column('certificate_file_path', __('Certificate'))
            ->display(function ($path) {
                if ($path) {
                    return "<a href='{$path}' target='_blank'><i class='fa fa-download'></i> Download</a>";
                }
                return '-';
            });

        $grid->column('issued_at', __('Issued'))
            
            ->sortable();
            
        $grid->column('expires_at', __('Expires'))
            ->display(function ($value) {
                if (!$value) return 'Never';
                
                $expires = \Carbon\Carbon::parse($value);
                $now = \Carbon\Carbon::now();
                
                if ($expires->isPast()) {
                    return "<span class='text-danger'>{$expires->format('M d, Y')} (Expired)</span>";
                } else {
                    $days = $now->diffInDays($expires);
                    $color = $days <= 30 ? 'text-warning' : 'text-success';
                    return "<span class='$color'>{$expires->format('M d, Y')}</span>";
                }
            })
            ->sortable();

        // Filters
        $grid->filter(function($filter) {
            $filter->disableIdFilter();
            
            $filter->like('certificate_number', 'Certificate Number');
            $filter->like('user.name', 'Student Name');
            
            $filter->equal('course_id', 'Course')->select(
                Course::where('status', 'active')
                    ->pluck('title', 'id')
                    ->toArray()
            );
            
            $filter->equal('certificate_type', 'Certificate Type')->select([
                'completion' => 'Completion',
                'achievement' => 'Achievement',
                'participation' => 'Participation'
            ]);
            
            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'revoked' => 'Revoked',
                'expired' => 'Expired'
            ]);
            
            $filter->between('completion_score', 'Score Range');
            $filter->between('issued_at', 'Issue Date')->date();
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
        $show = new Show(CourseCertificate::findOrFail($id));

        $show->panel()
            ->style('primary')
            ->title('Certificate Details');

        $show->field('id', __('ID'));
        $show->field('certificate_number', __('Certificate Number'));
        $show->field('user.name', __('Student Name'));
        $show->field('user.email', __('Student Email'));
        $show->field('course.title', __('Course Title'));
        $show->field('course.instructor_name', __('Instructor'));
        
        $show->divider();
        
        $show->field('completion_score', __('Completion Score'))->as(function ($score) {
            return $score . '%';
        });
        
        $show->field('certificate_type', __('Certificate Type'))->label([
            'completion' => 'primary',
            'achievement' => 'success',
            'participation' => 'info'
        ]);
        
        $show->field('status', __('Status'))->badge([
            'active' => 'success',
            'revoked' => 'danger',
            'expired' => 'warning'
        ]);
        
        $show->field('certificate_file_path', __('Certificate File'))->link();
        
        $show->divider();
        
        $show->field('issued_at', __('Issued At'));
        $show->field('expires_at', __('Expires At'));
        $show->field('revoked_at', __('Revoked At'));
        $show->field('revoked_reason', __('Revocation Reason'));
        
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
        $form = new Form(new CourseCertificate());

        $form->tab('Certificate Information', function ($form) {
            $form->text('certificate_number', __('Certificate Number'))
                ->rules('required|string|max:100|unique:course_certificates,certificate_number,{{id}}')
                ->help('Unique certificate number');
                
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
        });

        $form->tab('Certificate Details', function ($form) {
            $form->number('completion_score', __('Completion Score (%)'))
                ->default(80)
                ->min(0)
                ->max(100)
                ->rules('required|integer|min:0|max:100')
                ->help('Student\'s completion score');
                
            $form->select('certificate_type', __('Certificate Type'))
                ->options([
                    'completion' => 'Course Completion',
                    'achievement' => 'Achievement Certificate',
                    'participation' => 'Participation Certificate'
                ])
                ->rules('required')
                ->default('completion');
                
            $form->file('certificate_file_path', __('Certificate File'))
                ->help('Upload the certificate PDF file');
        });

        $form->tab('Status & Dates', function ($form) {
            $form->select('status', __('Status'))
                ->options([
                    'active' => 'Active',
                    'revoked' => 'Revoked',
                    'expired' => 'Expired'
                ])
                ->rules('required')
                ->default('active');
                
      
                
            $form->datetime('expires_at', __('Expiry Date'))
                ->help('Leave empty for no expiration');
                
            $form->datetime('revoked_at', __('Revoked Date'))
                ->help('When the certificate was revoked (if applicable)');
                
            $form->textarea('revoked_reason', __('Revocation Reason'))
                ->rows(3)
                ->help('Reason for revoking the certificate');
        });

        // Form hooks
        $form->saving(function (Form $form) {
            // Auto-generate certificate number if empty
            if (empty($form->certificate_number)) {
                $form->certificate_number = 'CERT-' . strtoupper(uniqid());
            }
        });

        return $form;
    }
}
