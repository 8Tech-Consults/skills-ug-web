<?php

namespace App\Admin\Controllers;

use App\Models\JobWebSitePage;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class JobWebSitePageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Job Web Site Page';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new JobWebSitePage());
        $grid->model()->orderBy('id', 'desc');
        $grid->quickSearch('title', 'content', 'url')->placeholder('Search...');

        $grid->column('id', __('Id'))->sortable();
        $grid->column('created_at', __('Created at'))->hide();
        $grid->column('updated_at', __('Updated at'))->hide();
        $grid->column('job_web_site_id', __('Job Web Site'))
            ->display(function ($job_web_site_id) {
                $jobWebSite = \App\Models\JobWebSite::find($job_web_site_id);
                return $jobWebSite ? $jobWebSite->name : 'N/A';
            })
            ->sortable();
        $grid->column('title', __('Title'));
        $grid->column('content', __('Content'))->hide();
        $grid->column('url', __('Url'));
        $grid->column('status', __('Status'))
            ->filter([
                'pending' => 'Pending',
                'fetched' => 'Fetched',
                'error' => 'Error',
            ]);
        $grid->column('post_id', __('Post id'))->hide();
        $grid->column('page_content', __('Page content'))->hide();
        $grid->column('error_message', __('Error message'))->hide();

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
        $show = new Show(JobWebSitePage::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('job_web_site_id', __('Job web site id'));
        $show->field('title', __('Title'));
        $show->field('content', __('Content'));
        $show->field('url', __('Url'));
        $show->field('status', __('Status'));
        $show->field('post_id', __('Post id'));
        $show->field('page_content', __('Page content'));
        $show->field('error_message', __('Error message'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new JobWebSitePage());

        $form->number('job_web_site_id', __('Job web site id'));
        $form->textarea('title', __('Title'));
        $form->textarea('content', __('Content'));
        $form->textarea('url', __('Url'));
        $form->text('status', __('Status'))->default('pending');
        $form->text('post_id', __('Post id'));
        $form->textarea('page_content', __('Page content'));
        $form->textarea('error_message', __('Error message'));

        return $form;
    }
}
