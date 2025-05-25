<?php

namespace App\Admin\Controllers;

use App\Models\District;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DistrictController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'District';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new District());

        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('jobs_count', __('Jobs count'))->sortable();
        $grid->column('photo', __('Photo'))->image('', 100, 100);
        $grid->quickSearch('name')->placeholder('Search by name'); 
        return $grid;
        $grid->column('district_status', __('District status'));
        $grid->column('region_id', __('Region id'));
        $grid->column('subregion_id', __('Subregion id'));
        $grid->column('map_id', __('Map id'));
        $grid->column('zone_id', __('Zone id'));
        $grid->column('land_type_id', __('Land type id'));
        $grid->column('user_id', __('User id'));



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
        $show = new Show(District::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('district_status', __('District status'));
        $show->field('region_id', __('Region id'));
        $show->field('subregion_id', __('Subregion id'));
        $show->field('map_id', __('Map id'));
        $show->field('zone_id', __('Zone id'));
        $show->field('land_type_id', __('Land type id'));
        $show->field('user_id', __('User id'));
        $show->field('photo', __('Photo'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new District());

        $form->text('name', __('Name'));
        $form->image('photo', __('Photo'));
        return $form;
        $form->number('district_status', __('District status'));
        $form->number('region_id', __('Region id'));
        $form->number('subregion_id', __('Subregion id'));
        $form->number('map_id', __('Map id'));
        $form->number('zone_id', __('Zone id'));
        $form->number('land_type_id', __('Land type id'));
        $form->number('user_id', __('User id'));
        $form->number('jobs_count', __('Jobs count'));
    }
}
