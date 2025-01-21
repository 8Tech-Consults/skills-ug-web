<?php

namespace App\Admin\Controllers;

use App\Models\LaundryOrderItemType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LaundryOrderItemTypeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Laundry Order Item Types';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LaundryOrderItemType());
        $grid->model()->orderBy('id', 'desc');
        $grid->column('name', __('Name'))->sortable();
        $grid->column('description', __('Description'))->hide();
        $grid->column('price', __('Price'))->sortable();
        $grid->column('status', __('Status'))->hide();
        $grid->disableBatchActions(); 

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
        $show = new Show(LaundryOrderItemType::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('name', __('Name'));
        $show->field('description', __('Description'));
        $show->field('price', __('Price'));
        $show->field('status', __('Status'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new LaundryOrderItemType());

        $form->text('name', __('Name'))->required();
        $form->decimal('price', __('Price'))->required();
        $form->textarea('description', __('Description'));
        $form->radio('status', __('Status'))
            ->options([
                'Active' => 'Active',
                'Inactive' => 'Inactive',
            ])
            ->default('Active')
            ->required();

        return $form;
    }
}
