<?php

namespace App\Admin\Controllers;

use App\Models\BlogPost;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BlogPostController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Blog Posts';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BlogPost());

        $grid->column('id', __('ID'))->sortable();
        $grid->column('title', __('Title'))->limit(50);
        $grid->column('slug', __('Slug'))->limit(30);
        $grid->column('excerpt', __('Excerpt'))->limit(80);
        $grid->column('author_name', __('Author'));
        $grid->column('category', __('Category'));
        $grid->column('status', __('Status'))->badge([
            'draft' => 'warning',
            'published' => 'success',
            'archived' => 'danger',
        ]);
        $grid->column('featured', __('Featured'))->bool();
        $grid->column('views_count', __('Views'))->sortable();
        $grid->column('likes_count', __('Likes'))->sortable();
        $grid->column('published_at', __('Published'))->date();
        $grid->column('created_at', __('Created'))->date();

        $grid->filter(function($filter) {
            $filter->like('title', 'Title');
            $filter->like('author_name', 'Author');
            $filter->equal('category', 'Category');
            $filter->equal('status', 'Status')->select([
                'draft' => 'Draft',
                'published' => 'Published',
                'archived' => 'Archived',
            ]);
            $filter->equal('featured', 'Featured')->select([
                1 => 'Yes',
                0 => 'No',
            ]);
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
        $show = new Show(BlogPost::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('slug', __('Slug'));
        $show->field('excerpt', __('Excerpt'));
        $show->field('content', __('Content'));
        $show->field('featured_image', __('Featured image'));
        $show->field('author_name', __('Author name'));
        $show->field('author_email', __('Author email'));
        $show->field('status', __('Status'));
        $show->field('category', __('Category'));
        $show->field('tags', __('Tags'));
        $show->field('views_count', __('Views count'));
        $show->field('likes_count', __('Likes count'));
        $show->field('featured', __('Featured'));
        $show->field('published_at', __('Published at'));
        $show->field('meta_description', __('Meta description'));
        $show->field('meta_keywords', __('Meta keywords'));
        $show->field('reading_time_minutes', __('Reading time minutes'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BlogPost());

        $form->text('title', 'Title')->required();
        $form->text('slug', 'Slug')->help('Leave empty to auto-generate from title');
        $form->textarea('excerpt', 'Excerpt')->rows(3);
        $form->ckeditor('content', 'Content');
        $form->image('featured_image', 'Featured Image')->uniqueName();
        $form->text('author_name', 'Author Name')->required();
        $form->email('author_email', 'Author Email')->required();
        
        $form->select('status', 'Status')->options([
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ])->default('draft');
        
        $form->text('category', 'Category');
        $form->tags('tags', 'Tags');
        $form->switch('featured', 'Featured');
        
        $form->divider('SEO Settings');
        $form->textarea('meta_description', 'Meta Description')->rows(2);
        $form->text('meta_keywords', 'Meta Keywords');
        
        $form->datetime('published_at', 'Published At')->default(date('Y-m-d H:i:s'));

        // Auto-generate slug from title if not provided
        $form->saving(function (Form $form) {
            if (empty($form->slug)) {
                $form->slug = \Str::slug($form->title);
            }
            
            // Ensure slug is unique
            $slug = $form->slug;
            $count = 1;
            while (BlogPost::where('slug', $slug)->where('id', '!=', $form->model()->id ?? 0)->exists()) {
                $slug = $form->slug . '-' . $count;
                $count++;
            }
            $form->slug = $slug;
        });

        return $form;
    }
}
