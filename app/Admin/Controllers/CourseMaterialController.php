<?php

namespace App\Admin\Controllers;

use App\Models\CourseMaterial;
use App\Models\CourseUnit;
use App\Models\Course;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CourseMaterialController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Course Materials';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CourseMaterial());

        // Configure grid settings
        $grid->disableBatchActions();
        $grid->model()->with(['unit.course'])->orderBy('unit_id', 'asc')->orderBy('sort_order', 'asc');

        // Grid columns
        $grid->column('id', __('ID'))->sortable();

        /* $grid->column('unit.course.title', __('Course'))
            ->sortable();
                    return $grid;  */
        $grid->column('unit.title', __('Unit'))
            ->display(function ($title) {
                return "<strong>$title</strong>";
            })
            ->limit(50)
            ->sortable();

        $grid->column('title', __('Material Title'))
            ->limit(70)
            ->sortable();
 

        $grid->column('file_path', __('File'))
            ->display(function ($path) {
                if ($path) {
                    $filename = basename($path);
                    return "<a href='{$path}' target='_blank'><i class='fa fa-download'></i> {$filename}</a>";
                }
                return '-';
            });

        $grid->column('sort_order', __('Order'))
            ->editable()
            ->sortable();

        $grid->column('duration_minutes', __('Duration'))
            ->display(function ($minutes) {
                if (!$minutes) return '-';
                if ($minutes < 60) {
                    return $minutes . ' min';
                }
                $hours = floor($minutes / 60);
                $mins = $minutes % 60;
                return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
            })
            ->sortable();

        $grid->column('is_downloadable', __('Download'))
            ->switch([
                'on'  => ['value' => 1, 'text' => 'Yes', 'color' => 'success'],
                'off' => ['value' => 0, 'text' => 'No', 'color' => 'default'],
            ]);

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

            $filter->like('title', 'Material Title');

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
 

            $filter->equal('status', 'Status')->select([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'draft' => 'Draft'
            ]);

            $filter->equal('is_downloadable', 'Downloadable')->select([
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
        $show = new Show(CourseMaterial::findOrFail($id));

        $show->panel()
            ->style('primary')
            ->title('Material Details');

        $show->field('id', __('ID'));
        $show->field('unit.course.title', __('Course'));
        $show->field('unit.title', __('Unit'));
        $show->field('title', __('Material Title'));
        $show->field('description', __('Description'));

        $show->divider();

 

        $show->field('file_path', __('File Path'))->link();
        $show->field('external_url', __('External URL'))->link();
        $show->field('content', __('Text Content'));

        $show->divider();

        $show->field('sort_order', __('Sort Order'));
        $show->field('duration_minutes', __('Duration'))->as(function ($minutes) {
            if (!$minutes) return 'Not specified';
            if ($minutes < 60) {
                return $minutes . ' minutes';
            }
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $hours . ' hour(s)' . ($mins > 0 ? ' ' . $mins . ' minute(s)' : '');
        });

        $show->field('is_downloadable', __('Downloadable'))->using([1 => 'Yes', 0 => 'No'])->badge([
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

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CourseMaterial());

        $form->select('unit_id', __('Course Unit'))
            ->options(function ($id) {
                $unit = CourseUnit::with('course')->find($id);
                if ($unit) {
                    return [$unit->id => $unit->course->title . ' - ' . $unit->title];
                }
            })
            ->ajax('/admin/api/course-units')
            ->rules('required')
            ->help('Select course unit this material belongs to');

        $form->text('title', __('Material Title'))
            ->rules('required|string|max:255')
            ->help('Enter material title');

        $form->radio('type', __('Type'))
            ->options([
                'content' => 'Course Content',
                'quiz' => 'Quiz',
            ])
            ->rules('required')
            ->help('Select material type');


        $form->radio('content_source', __('Content Source'))
            ->options([
                'file' => 'File',
                'external' => 'External Link',
            ])
            ->when('file', function (Form $form) {
                $form->file('content_url', __('Content URL'))
                    ->help('URL to the content (file, video, or external link)');
            })
            ->when('external', function (Form $form) {
                $form->url('external_url', __('External URL'))
                    ->rules('nullable|url');
            }); 
        $form->quill('content_text', __('Content Text'))
            ->help('Text content for this material');

        $form->decimal('duration_seconds', __('Duration (Seconds)'))
            ->help('Duration of the material in seconds'); 

        $form->decimal('sort_order', __('Sort Order'))
            ->rules('required|integer|min:1')
            ->help('Order in which this material appears in the unit');

        $form->switch('is_downloadable', __('Allow Download'))
            ->default(1)
            ->help('Allow students to download this material');

        $form->radio('status', __('Status'))
            ->options([
                'draft' => 'Draft',
                'active' => 'Active',
                'inactive' => 'Inactive'
            ])
            ->default('draft')
            ->rules('required');

        return $form;
    }
}
