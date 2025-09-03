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
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('Id'));
        $grid->column('max_page', __('Max Page'))->editable();
        $grid->column('name', __('Name'))->sortable()->editable();
        $grid->column('url', __('Url'))->sortable()->editable();
        $grid->column('about', __('About'))->sortable()->editable();
        $grid->column('priority', __('Priority'))->editable();
        $grid->column('last_fetched_at', __('Last fetched at'));
        $grid->column('page_number', __('Page number'))->editable();
        $grid->column('total_posts_found', __('Total posts found'))->editable();
        $grid->column('new_posts_found', __('New posts found'))->editable();
        $grid->column('status', __('Status'))->editable();
        $grid->column('fetch_status', __('Fetch status'))->editable();
        $grid->column('failed_message', __('Failed message'))->editable();
        $grid->column('response_data', __('Response data'))->editable()->hide();
        $grid->column('last_page_url', __('Last page URL'))->editable()->hide();

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
        $show->field('last_fetched_at', __('Last fetched at'));
        $show->field('page_number', __('Page number'));
        $show->field('total_posts_found', __('Total posts found'));
        $show->field('new_posts_found', __('New posts found'));
        $show->field('status', __('Status'));
        $show->field('fetch_status', __('Fetch status'));
        $show->field('failed_message', __('Failed message'));
        $show->field('response_data', __('Response data'));

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

        $form->date('last_fetched_at', __('Last fetched at'))->default(date('Y-m-d'));
        $form->decimal('page_number', __('Page number'))->default(1);
        $form->decimal('max_page', __('Max Page'))->default(20);
        $form->decimal('total_posts_found', __('Total posts found'));
        $form->decimal('new_posts_found', __('New posts found'));
        $form->text('status', __('Status'))->default('active');
        $form->text('slug', __('Slug'))->required();
        $form->textarea('failed_message', __('Failed message'));
        $form->textarea('response_data', __('Response data'));
        $form->radio('fetch_status', __('Fetch status'))
            ->options(['active' => 'Active', 'inactive' => 'Inactive']);
        return $form;
    }
}
