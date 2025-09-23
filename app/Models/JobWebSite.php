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
    const JOBS_IN_UGANDA = 'jobs-in-uganda';

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
        } else if ($this->slug == self::JOBS_IN_UGANDA) {
            $page_number = (int)$this->page_number + 1;
            if ($page_number > $this->max_page) {
                $page_number = 0;
            }
            $this->page_number = $page_number;
            $this->last_page_url = $this->url  . $page_number;
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
                    $page->title = html_entity_decode(trim($a->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $page->status = 'pending';
                    $page->save();
                    $jobLinksNew[] = $a->href;
                }
            }
        } else if ($this->slug == self::JOBS_IN_UGANDA) {
            foreach ($html->find('a') as $a) {
                // Check if the link is a job posting link
                // Job links typically follow pattern: /2025/09/job-title-company.html
                // or contain specific job-related keywords in the path
                if (
                    preg_match('/\/\d{4}\/\d{2}\/.*\.html$/', $a->href) ||
                    strpos($a->href, '/job/') !== false ||
                    strpos($a->href, '/vacancy/') !== false ||
                    strpos($a->href, '/career/') !== false
                ) {

                    // Exclude navigation links, categories, and admin pages
                    if (
                        strpos($a->href, '/category/') === false &&
                        strpos($a->href, '/tag/') === false &&
                        strpos($a->href, '/author/') === false &&
                        strpos($a->href, '/page/') === false &&
                        strpos($a->href, '/wp-admin/') === false &&
                        strpos($a->href, '/wp-content/') === false &&
                        strpos($a->href, '/feed/') === false &&
                        strpos($a->href, '#') === false &&
                        !empty(trim($a->plaintext))
                    ) {

                        // Make URL absolute if it's relative
                        $fullUrl = $a->href;
                        if (strpos($a->href, 'http') === false) {
                            $fullUrl = 'https://theugandanjobline.com' . $a->href;
                        }

                        $jobLinks[] = trim($fullUrl);

                        // Check if page already exists
                        $page = JobWebSitePage::where('url', $fullUrl)->first();
                        if ($page != null) {
                            continue;
                        }
                        // Create new job page record
                        $page = new JobWebSitePage();
                        $page->url = $fullUrl;
                        $page->job_web_site_id = $this->id;
                        $page->title = html_entity_decode(trim($a->plaintext), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $page->status = 'pending';
                        $page->save();
                        $jobLinksNew[] = $fullUrl;
                    }
                }
            }
        } else {
            throw new \Exception('Invalid slug');
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
