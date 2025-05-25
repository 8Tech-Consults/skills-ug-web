<?php

namespace App\Admin\Controllers;

use App\Models\JobCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class JobCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Job Categories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new JobCategory());
        $grid->model()->orderBy('id', 'desc');
        $grid->quickSearch('name')->placeholder('Search by name');

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'))->sortable();
        $grid->column('description', __('Description'))->hide();
        $grid->column('type', __('Type'));
        $grid->column('icon', __('Icon'))->image('', 100, 100);
        $grid->column('slug', __('Slug'))->hide();
        $grid->column('status', __('Status'))->hide();
        $grid->column('jobs_count', __('Jobs count'))->hide();
        $grid->column('tags', __('Tags'))->hide();
        $grid->column('category_type', __('Category Type'))
            ->filter([
                'Job' => 'Job',
                'Service' => 'Service',
            ])->sortable();

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
        $show = new Show(JobCategory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('name', __('Name'));
        $show->field('description', __('Description'));
        $show->field('type', __('Type'));
        $show->field('icon', __('Icon'));
        $show->field('slug', __('Slug'));
        $show->field('status', __('Status'));
        $show->field('jobs_count', __('Jobs count'));
        $show->field('tags', __('Tags'));
        $show->field('category_type', __('Category type'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new JobCategory());

        $form->text('name', __('Name'));
        $form->text('description', __('Description'));
        $form->radio('type', __('Type'))->default('Functional')
            ->options([
                'Functional' => 'Functional',
                'Industry' => 'Industry',
            ]);
        $form->radio('category_type', __('Category type'))
            ->default('Job')
            ->options([
                'Job' => 'Job',
                'Service' => 'Service',
            ]);
        $form->image('icon', __('Icon'));
        $form->textarea('slug', __('Slug'));
        $form->textarea('status', __('Status'));
        $form->number('jobs_count', __('Jobs count'));
        $form->textarea('tags', __('Tags'));


        return $form;
    }
}
