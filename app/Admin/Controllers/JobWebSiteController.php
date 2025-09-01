<?php

namespace App\Admin\Controllers;

use App\Models\JobWebSite;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class JobWebSiteController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'JobWebSite';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new JobWebSite());

        $grid->column('name', __('Name'))->sortable();
        $grid->column('url', __('Url'))->sortable();
        $grid->column('about', __('About'))->sortable();
        $grid->column('priority', __('Priority'))->sortable();

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
        $show = new Show(JobWebSite::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('name', __('Name'));
        $show->field('url', __('Url'));
        $show->field('about', __('About'));
        $show->field('priority', __('Priority'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new JobWebSite());

        $form->text('name', __('Name'))->required();
        $form->text('url', __('Url'))->required();
        $form->text('about', __('About'))->required();
        $form->decimal('priority', __('Priority'))->required();

        return $form;
    }
}
