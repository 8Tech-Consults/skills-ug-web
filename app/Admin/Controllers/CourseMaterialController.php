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
        $grid->model()->with(['unit.course'])->orderBy('id', 'desc');

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
            ->sortable()
            ->editable();


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
            ->sortable()
            ->hide();

        // Filters
        $grid->filter(function ($filter) {
            $filter->disableIdFilter();

            $filter->like('title', 'Material Title');

            $filter->equal('unit.course_id', 'Course')->select(function ($id) {
                $course = Course::find($id);
                return $course ? [$course->id => $course->title] : [];
            })->ajax(admin_url('api/courses'));

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

        // Course and Unit Selection Section
        $form->divider('Course & Unit Selection');

        /*   // Virtual course selector for easier unit selection
        $form->select('_course_selector', __('Course (Helper)'))
            ->options(function ($id) {
                // When editing, get course from unit
                if (request()->route('course_material')) {
                    $material = CourseMaterial::with('unit.course')->find(request()->route('course_material'));
                    if ($material && $material->unit && $material->unit->course) {
                        return [$material->unit->course->id => $material->unit->course->title];
                    }
                }
                return [];
            })
            ->ajax('/admin/api/courses')
            ->help('First select a course to filter the units below')
            ->load('unit_id', '/admin/api/courses/$val/units'); // This prevents the field from being saved to database
 */
        $form->select('unit_id', __('Course Unit'))
            ->options(function ($id) {
                $unit = CourseUnit::with('course')->find($id);
                if ($unit) {
                    return [$unit->id => $unit->course->title . ' - ' . $unit->title];
                }
                return [];
            })
            ->ajax(admin_url('api/course-units'))
            ->rules('required')
            ->help('Select the specific unit for this material');

        // Basic Information Section
        $form->divider('Basic Information');

        $form->text('title', __('Material Title'))
            ->rules('required|string|max:255')
            ->help('Enter a descriptive title for this material');

        // Note: description field removed as it doesn't exist in database

        $form->radio('type', __('Material Type'))
            ->options([
                'text' => 'Text Content',
                'video' => 'Video',
                'audio' => 'Audio',
                'pdf' => 'PDF Document',
                'image' => 'Image'
            ])
            ->default('text')
            ->help('Select the type of material');

        // Content Configuration Section
        $form->divider('Content Configuration');

        $form->radio('content_source', __('Content Source'))
            ->options([
                'text' => 'Text Content',
                'file' => 'File Upload',
                'external' => 'External URL',
            ])
            ->default('text')
            ->help('Choose how you want to provide the content')
            ->when('text', function (Form $form) {
                $form->quill('content_text', __('Text Content'))
                    ->rules('required_if:content_source,text')
                    ->help('Rich text content for this material');
            })
            ->when('file', function (Form $form) {
                $form->file('content_url', __('Upload File'))
                    ->rules('required_if:content_source,file|mimes:pdf,doc,docx,ppt,pptx,mp4,mp3,jpg,jpeg,png,gif|max:102400')
                    ->help('Upload content file (PDF, DOC, PPT, MP4, MP3, Images - Max: 100MB)')
                    ->move('course-materials')
                    ->name(function ($file) {
                        return 'material_' . time() . '_' . $file->getClientOriginalName();
                    });
            })
            ->when('external', function (Form $form) {
                $form->url('external_url', __('External URL'))
                    ->rules('required_if:content_source,external|url')
                    ->help('Enter the external URL (YouTube, Vimeo, or direct file links)')
                    ->placeholder('https://example.com/video.mp4');
            });

        // Material Settings Section
        $form->divider('Material Settings');

        $form->number('duration_seconds', __('Duration (Seconds)'))
            ->default(0)
            ->help('Duration in seconds for video/audio content')
            ->placeholder('e.g., 900 (15 minutes)');

        $form->number('sort_order', __('Sort Order'))
            ->default(function () {
                // Auto-calculate next sort order for the unit
                if (request('unit_id')) {
                    $lastMaterial = CourseMaterial::where('unit_id', request('unit_id'))
                        ->orderBy('sort_order', 'desc')->first();
                    return $lastMaterial ? $lastMaterial->sort_order + 1 : 1;
                }
                return 1;
            })
            ->help('Order in which this material appears in the unit (auto-calculated)');

        $form->select('is_downloadable', __('Allow Download'))
            ->options([
                'No' => 'No - Cannot download',
                'Yes' => 'Yes - Can download'
            ])
            ->default('No')
            ->help('Allow students to download this material file');

        // Publishing Section
        $form->divider('Publishing Settings');

        $form->radio('status', __('Status'))
            ->options([
                'draft' => 'Draft - Not visible to students',
                'active' => 'Active - Visible to students',
                'inactive' => 'Inactive - Hidden from students'
            ])
            ->default('active')
            ->help('Control visibility of this material');



        return $form;
    }
}
