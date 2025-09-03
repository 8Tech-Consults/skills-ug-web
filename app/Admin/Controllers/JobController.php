<?php

namespace App\Admin\Controllers;

use App\Models\Job;
use App\Models\District;
use App\Models\JobCategory;
use App\Models\SubCounty;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class JobController extends AdminController
{
    protected $title = 'Job';

    protected function grid()
    {
        $grid = new Grid(new Job());
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', __('Id'))->sortable();
        $grid->column('title', __('Title'))->limit(30);
        $grid->column('company_name', __('Company name'))->limit(30);
        $grid->column('status', __('Status'))->label();
        $grid->column('deadline', __('Deadline'))->date();
        $grid->column('category_id', __('Category'))->display(function ($id) {
            return optional(JobCategory::find($id))->name;
        });
        $grid->column('district_id', __('District'))->display(function ($id) {
            return optional(District::find($id))->name;
        });
        $grid->column('vacancies_count', __('Vacancies'));
        $grid->column('employment_status', __('Employment status'));
        $grid->column('minimum_salary', __('Min Salary'));
        $grid->column('maximum_salary', __('Max Salary'));
        $grid->column('created_at', __('Created at'))->sortable();

        $grid->filter(function ($filter) {
            $filter->like('title', 'Title');
            $filter->equal('status', 'Status')->select(['Pending' => 'Pending', 'Open' => 'Open', 'Closed' => 'Closed']);
            $filter->equal('category_id', 'Category')->select(JobCategory::pluck('name', 'id'));
            $filter->equal('district_id', 'District')->select(District::pluck('name', 'id'));
        });

        return $grid;
    }

    protected function detail($id)
    {
        $show = new Show(Job::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('company_name', __('Company name'));
        $show->field('status', __('Status'));
        $show->field('deadline', __('Deadline'));
        $show->field('category_id', __('Category'))->as(function ($id) {
            return optional(JobCategory::find($id))->name;
        });
        $show->field('district_id', __('District'))->as(function ($id) {
            return optional(District::find($id))->name;
        });
        $show->field('vacancies_count', __('Vacancies'));
        $show->field('employment_status', __('Employment status'));
        $show->field('minimum_salary', __('Min Salary'));
        $show->field('maximum_salary', __('Max Salary'));
        $show->field('details', __('Details'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    protected function form()
    {
        $form = new Form(new Job());

        $form->text('title', __('Title'))->rules('required|max:255');
        $form->select('category_id', __('Category'))->options(JobCategory::pluck('name', 'id'))->rules('required');
        $form->select('district_id', __('District'))->options(District::pluck('name', 'id'))->rules('required');
        // $form->select('sub_county_id', __('Sub County'))->options(SubCounty::pluck('name', 'id'));
        $form->text('company_name', __('Company name'))->rules('required|max:255');
        $form->text('status', __('Status'))->default('Pending')->rules('required');
        $form->date('deadline', __('Deadline'))->rules('required|date');
        $form->number('vacancies_count', __('Vacancies'))->min(1)->default(1);
        $form->text('employment_status', __('Employment status'))->default('Full Time');
        $form->number('minimum_salary', __('Min Salary'))->min(0);
        $form->number('maximum_salary', __('Max Salary'))->min(0);
        $form->textarea('details', __('Details'))->rules('max:2000');
        $form->text('company_logo', __('Company logo'));
        $form->text('slug', __('Slug'));
        $form->url('external_url', __('External url'));
        $form->switch('is_imported', __('Is imported'))->states([
            'on'  => ['value' => 1, 'text' => 'Yes'],
            'off' => ['value' => 0, 'text' => 'No'],
        ])->default(0);

        return $form;
    }
}
