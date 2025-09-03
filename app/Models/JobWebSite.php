<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

include_once('simple_html_dom.php');

class JobWebSite extends Model
{
    use HasFactory;

    //constant for fetch status
    const BRIGHTER_MONDAY = 'brighter-monday';

    public function get_next_page_link()
    {
        if ($this->slug == self::BRIGHTER_MONDAY) {
            $page_number = (int)$this->page_number + 1;
            if ($page_number > $this->max_page) {
                $page_number = 0;
            }
            $this->page_number = $page_number;
            $this->last_page_url = $this->url . '?page=' . $page_number;
            return str_replace('{page_number}', $this->page_number, $this->url);
        } else {
            throw new \Exception('Invalid slug');
        }
    }

    public function process_pages()
    {
        $html = str_get_html($this->response_data);

        $jobLinks = [];
        $jobLinksNew = [];
        if ($this->slug == self::BRIGHTER_MONDAY) {
            foreach ($html->find('a') as $a) {
                if (strpos($a->href, 'brightermonday.co.ug/listings') !== false) {
                    $jobLinks[] = trim($a->href);
                    $page = JobWebSitePage::where('url', $a->href)->first();
                    if ($page != null) {
                        continue;
                    }
                    $page = new JobWebSitePage();
                    $page->url = $a->href;
                    $page->job_web_site_id = $this->id;
                    $page->title = trim($a->plaintext);
                    $page->status = 'pending';
                    $page->save();
                    $jobLinksNew[] = $a->href;
                }
            }
        }
        $this->last_fetched_at = Carbon::now();
        $this->total_posts_found = count($jobLinks);
        $this->new_posts_found = count($jobLinksNew);
        $this->fetch_status = "success";
        $this->failed_message = null;
        try {
            $this->save();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    public function get_next_page_content()
    {


        $this->fetch_status = 'in_progress';
        $this->failed_message = null;
        $this->fetch_status = 'in_progress';
        $this->last_fetched_at = Carbon::now();
        $this->get_next_page_link();



        try {
            $my_html = Utils::get_url($this->last_page_url);
        } catch (\Throwable $th) {
            $this->status = 'failed';
            $this->failed_message = $th->getMessage();
            throw $th;
        }
        $this->fetch_status = 'success';
        $this->last_fetched_at = Carbon::now();
        $this->response_data = $my_html;
        $this->save();
        $this->process_pages();
    }


  

    //boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->last_fetched_at = Carbon::now()->subDays(30);
            $model->page_number = 0;
        });

        static::updating(function ($model) {});
    }
}
