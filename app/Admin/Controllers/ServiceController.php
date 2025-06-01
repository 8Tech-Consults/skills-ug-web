<?php

namespace App\Admin\Controllers;

use App\Models\Service;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ServiceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Service';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Service());

        $grid->column('id', __('Id'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));
        $grid->column('title', __('Title'));
        $grid->column('slug', __('Slug'));
        $grid->column('job_category_id', __('Job category id'));
        $grid->column('provider_id', __('Provider id'));
        $grid->column('status', __('Status'));
        $grid->column('tags', __('Tags'));
        $grid->column('description', __('Description'));
        $grid->column('details', __('Details'));
        $grid->column('price', __('Price'));
        $grid->column('price_description', __('Price description'));
        $grid->column('delivery_time', __('Delivery time'));
        $grid->column('delivery_time_description', __('Delivery time description'));
        $grid->column('client_requirements', __('Client requirements'));
        $grid->column('process_description', __('Process description'));
        $grid->column('cover_image', __('Cover image'));
        $grid->column('gallery', __('Gallery'));
        $grid->column('intro_video_url', __('Intro video url'));
        $grid->column('provider_name', __('Provider name'));
        $grid->column('provider_logo', __('Provider logo'));
        $grid->column('location', __('Location'));
        $grid->column('languages_spoken', __('Languages spoken'));
        $grid->column('experience_years', __('Experience years'));
        $grid->column('certifications', __('Certifications'));
        $grid->column('refund_policy', __('Refund policy'));
        $grid->column('promotional_badge', __('Promotional badge'));

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
        $show = new Show(Service::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('title', __('Title'));
        $show->field('slug', __('Slug'));
        $show->field('job_category_id', __('Job category id'));
        $show->field('provider_id', __('Provider id'));
        $show->field('status', __('Status'));
        $show->field('tags', __('Tags'));
        $show->field('description', __('Description'));
        $show->field('details', __('Details'));
        $show->field('price', __('Price'));
        $show->field('price_description', __('Price description'));
        $show->field('delivery_time', __('Delivery time'));
        $show->field('delivery_time_description', __('Delivery time description'));
        $show->field('client_requirements', __('Client requirements'));
        $show->field('process_description', __('Process description'));
        $show->field('cover_image', __('Cover image'));
        $show->field('gallery', __('Gallery'));
        $show->field('intro_video_url', __('Intro video url'));
        $show->field('provider_name', __('Provider name'));
        $show->field('provider_logo', __('Provider logo'));
        $show->field('location', __('Location'));
        $show->field('languages_spoken', __('Languages spoken'));
        $show->field('experience_years', __('Experience years'));
        $show->field('certifications', __('Certifications'));
        $show->field('refund_policy', __('Refund policy'));
        $show->field('promotional_badge', __('Promotional badge'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Service());

        $form->textarea('title', __('Title'));
        $form->textarea('slug', __('Slug'));
        $form->number('job_category_id', __('Job category id'));
        $form->number('provider_id', __('Provider id'));
        $form->text('status', __('Status'))->default('Active');
        $form->textarea('tags', __('Tags'));
        $form->textarea('description', __('Description'));
        $form->textarea('details', __('Details'));
        $form->decimal('price', __('Price'));
        $form->textarea('price_description', __('Price description'));
        $form->textarea('delivery_time', __('Delivery time'));
        $form->textarea('delivery_time_description', __('Delivery time description'));
        $form->textarea('client_requirements', __('Client requirements'));
        $form->textarea('process_description', __('Process description'));
        $form->textarea('cover_image', __('Cover image'));
        $form->textarea('gallery', __('Gallery'));
        $form->textarea('intro_video_url', __('Intro video url'));
        $form->textarea('provider_name', __('Provider name'));
        $form->textarea('provider_logo', __('Provider logo'));
        $form->textarea('location', __('Location'));
        $form->textarea('languages_spoken', __('Languages spoken'));
        $form->textarea('experience_years', __('Experience years'));
        $form->textarea('certifications', __('Certifications'));
        $form->textarea('refund_policy', __('Refund policy'));
        $form->textarea('promotional_badge', __('Promotional badge'));

        return $form;
    }
}
