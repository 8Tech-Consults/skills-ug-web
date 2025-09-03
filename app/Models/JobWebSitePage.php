<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

include_once('simple_html_dom.php');

class JobWebSitePage extends Model
{
    use HasFactory;

    //boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $page = JobWebSitePage::where('url', $model->url)->first();
            if ($page != null) {
                return false;
            }
        });
    }

    //fetch_page_content
    public function fetch_page_content()
    {
        // dd($this->page_content);
        $data = null;
        $this->status = 'pending';
        try {
            $data = Utils::get_url($this->url);
        } catch (\Throwable $th) {
            $this->error_message = $th->getMessage();
            $this->status = 'error';
            $this->save();
            return;
        }
        $this->page_content = $data;
        $this->save();
        $this->process_page_content();
    }

    public function process_page_content()
    {
        if ($this->job_web_site == null) {
            $this->status = 'error';
            $this->error_message = "Job web site not found";
            $this->save();
            return;
        }
        if ($this->page_content == null) {
            $this->status = 'error';
            $this->error_message = "Page content is empty";
            $this->save();
            return;
        }
        if ($this->job_web_site->slug == JobWebSite::BRIGHTER_MONDAY) {
            $existing_post = Job::where('external_url', $this->url)->first();
            if ($existing_post != null) {
                return;
            }

            $html = str_get_html($this->page_content);
            if ($html == null) {
                $this->status = 'error';
                $this->error_message = "Failed to parse HTML content";
                $this->save();
                return;
            }

            $existing_post = new Job();
            $existing_post->is_imported = 'yes';
            $existing_post->external_url = $this->url;
            $existing_post->page_id = $this->id;
            $existing_post->posted_by_id = 1; //admin

            try {
                // === JOB TITLE EXTRACTION ===
                $titleElement = $html->find('h1', 0) ?:
                    $html->find('.job-title', 0) ?:
                    $html->find('[class*="title"]', 0) ?:
                    $html->find('title', 0);

                $existing_post->title = $titleElement ?
                    trim(strip_tags($titleElement->innertext)) :
                    'Untitled Job Position';

                // === SMART CONTENT EXTRACTION FOR RESPONSIBILITIES ===
                $jobOverview = '';
                $keyResponsibilities = [];
                $qualifications = [];
                $benefits = [];
                $additionalInfo = [];

                // Extract all meaningful paragraphs and list items first
                $allElements = [];
                $contentSelectors = [
                    'p',
                    'li',
                    'div[class*="content"]',
                    'div[class*="description"]',
                    'div[class*="requirement"]',
                    'div[class*="responsibility"]',
                    'div[class*="qualification"]',
                    'div[class*="benefit"]'
                ];

                foreach ($contentSelectors as $selector) {
                    $elements = $html->find($selector);
                    foreach ($elements as $element) {
                        $text = trim(strip_tags($element->innertext));
                        // Skip short, navigation, and repetitive content
                        if (
                            strlen($text) < 20 ||
                            stripos($text, 'homepage') !== false ||
                            stripos($text, 'brightermonday') !== false ||
                            stripos($text, 'customer support') !== false ||
                            stripos($text, 'report job') !== false ||
                            stripos($text, 'recruitment firm') !== false ||
                            preg_match('/^(full time|part time|contract|kampala|uganda)$/i', $text)
                        ) {
                            continue;
                        }

                        $allElements[] = [
                            'text' => $text,
                            'html' => trim($element->innertext)
                        ];
                    }
                }

                // Categorize content intelligently without repetitive headers
                $processedContent = [];
                foreach ($allElements as $element) {
                    $text = strtolower($element['text']);
                    $content = $element['text'];

                    // Skip if already processed (avoid duplicates)
                    if (in_array($content, $processedContent)) {
                        continue;
                    }
                    $processedContent[] = $content;

                    // Categorize based on content type
                    if (
                        strpos($text, 'responsible') !== false ||
                        strpos($text, 'duties') !== false ||
                        strpos($text, 'manage') !== false ||
                        strpos($text, 'ensure') !== false ||
                        strpos($text, 'develop') !== false ||
                        strpos($text, 'maintain') !== false ||
                        strpos($text, 'coordinate') !== false ||
                        strpos($text, 'supervise') !== false ||
                        strpos($text, 'monitor') !== false
                    ) {
                        if (strlen($content) > 30) {
                            $keyResponsibilities[] = $content;
                        }
                    } elseif (
                        strpos($text, 'qualification') !== false ||
                        strpos($text, 'diploma') !== false ||
                        strpos($text, 'degree') !== false ||
                        strpos($text, 'certificate') !== false ||
                        strpos($text, 'experience') !== false ||
                        strpos($text, 'skill') !== false ||
                        strpos($text, 'knowledge') !== false ||
                        strpos($text, 'ability') !== false ||
                        strpos($text, 'required') !== false
                    ) {
                        if (strlen($content) > 20) {
                            $qualifications[] = $content;
                        }
                    } elseif (
                        strpos($text, 'benefit') !== false ||
                        strpos($text, 'package') !== false ||
                        strpos($text, 'offer') !== false ||
                        strpos($text, 'salary') !== false ||
                        strpos($text, 'compensation') !== false
                    ) {
                        if (strlen($content) > 20) {
                            $benefits[] = $content;
                        }
                    } elseif (strlen($content) > 50 && strlen($content) < 500) {
                        // General job information
                        if (
                            empty($jobOverview) &&
                            (strpos($text, 'position') !== false ||
                                strpos($text, 'role') !== false ||
                                strpos($text, 'job') !== false ||
                                strpos($text, 'seeking') !== false ||
                                strpos($text, 'looking') !== false)
                        ) {
                            $jobOverview = $content;
                        } else {
                            $additionalInfo[] = $content;
                        }
                    }
                }

                // Build clean, organized HTML content
                $htmlSections = [];

                if (!empty($jobOverview)) {
                    $htmlSections[] = "<div class='job-overview'><h4>Job Overview</h4><p>" . htmlspecialchars($jobOverview) . "</p></div>";
                }

                if (!empty($keyResponsibilities)) {
                    $htmlSections[] = "<div class='key-responsibilities'><h4>Key Responsibilities</h4><ul>";
                    foreach (array_slice($keyResponsibilities, 0, 10) as $resp) { // Limit to 10 items
                        $htmlSections[] = "<li>" . htmlspecialchars($resp) . "</li>";
                    }
                    $htmlSections[] = "</ul></div>";
                }

                if (!empty($qualifications)) {
                    $htmlSections[] = "<div class='qualifications'><h4>Required Qualifications</h4><ul>";
                    foreach (array_slice($qualifications, 0, 8) as $qual) { // Limit to 8 items
                        $htmlSections[] = "<li>" . htmlspecialchars($qual) . "</li>";
                    }
                    $htmlSections[] = "</ul></div>";
                }

                if (!empty($benefits)) {
                    $htmlSections[] = "<div class='benefits'><h4>Benefits & Compensation</h4><ul>";
                    foreach ($benefits as $benefit) {
                        $htmlSections[] = "<li>" . htmlspecialchars($benefit) . "</li>";
                    }
                    $htmlSections[] = "</ul></div>";
                }

                if (!empty($additionalInfo)) {
                    $htmlSections[] = "<div class='additional-info'><h4>Additional Information</h4>";
                    foreach (array_slice($additionalInfo, 0, 3) as $info) { // Limit to 3 items
                        $htmlSections[] = "<p>" . htmlspecialchars($info) . "</p>";
                    }
                    $htmlSections[] = "</div>";
                }

                // Combine all sections into final responsibilities field
                if (!empty($htmlSections)) {
                    $existing_post->responsibilities = "<div class='job-description'>" . implode("", $htmlSections) . "</div>";
                } else {
                    // Fallback: extract the main content area more carefully
                    $mainContent = $html->find('main, .main-content, .content, [role="main"]', 0);
                    if ($mainContent) {
                        $text = trim(strip_tags($mainContent->innertext));
                        // Clean up the text
                        $lines = explode("\n", $text);
                        $cleanLines = [];
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (
                                strlen($line) > 20 &&
                                !preg_match('/(homepage|brightermonday|customer support|report job)/i', $line)
                            ) {
                                $cleanLines[] = $line;
                            }
                        }
                        $cleanText = implode("\n", array_slice($cleanLines, 0, 20)); // Limit lines
                        $existing_post->responsibilities = "<div class='job-description'><p>" . nl2br(htmlspecialchars($cleanText)) . "</p></div>";
                    }
                }

                // Set benefits field separately if found
                if (!empty($benefits)) {
                    $existing_post->benefits = implode("; ", $benefits);
                }

                // === EXTRACT ADDITIONAL DETAILS ===
                $detailsElements = [];
                
                // Look for specific detail selectors
                $detailSelectors = [
                    '.job-details', '[class*="detail"]', '[class*="info"]',
                    '.job-summary', '.job-meta', '[class*="meta"]',
                    '.job-specification', '[class*="spec"]',
                    '[class*="employment"]', '[class*="contract"]'
                ];
                
                foreach ($detailSelectors as $selector) {
                    $elements = $html->find($selector);
                    foreach ($elements as $element) {
                        $text = trim(strip_tags($element->innertext));
                        if (strlen($text) > 20 && strlen($text) < 500) {
                            // Skip if it's already in responsibilities or contains navigation content
                            if (stripos($existing_post->responsibilities ?? '', $text) === false &&
                                stripos($text, 'brightermonday') === false &&
                                stripos($text, 'homepage') === false &&
                                stripos($text, 'report job') === false) {
                                $detailsElements[] = $text;
                            }
                        }
                    }
                }
                
                // Extract employment details from spans and divs
                $allSpans = $html->find('span, div');
                foreach ($allSpans as $span) {
                    $text = trim(strip_tags($span->innertext));
                    
                    // Look for employment-related details
                    if (preg_match('/(employment type|job type|contract type|experience level|minimum qualification|deadline|posted|location)[\s:]*([^\n\r]{10,100})/i', $text, $matches)) {
                        $detailLabel = trim($matches[1]);
                        $detailValue = trim($matches[2]);
                        if (!empty($detailValue) && strlen($detailValue) > 5) {
                            $detailsElements[] = ucfirst($detailLabel) . ': ' . $detailValue;
                        }
                    }
                    
                    // Look for salary information text
                    elseif (preg_match('/(salary range|compensation|remuneration)[\s:]*([^\n\r]{10,100})/i', $text, $matches)) {
                        $detailLabel = trim($matches[1]);
                        $detailValue = trim($matches[2]);
                        if (!empty($detailValue)) {
                            $detailsElements[] = ucfirst($detailLabel) . ': ' . $detailValue;
                        }
                    }
                    
                    // Look for application deadline
                    elseif (preg_match('/(deadline|closing date|apply by)[\s:]*([^\n\r]{5,50})/i', $text, $matches)) {
                        $detailLabel = trim($matches[1]);
                        $detailValue = trim($matches[2]);
                        if (!empty($detailValue)) {
                            $detailsElements[] = ucfirst($detailLabel) . ': ' . $detailValue;
                        }
                    }
                }
                
                // Look for structured data in the page
                $structuredData = [];
                if ($existing_post->employment_status) {
                    $structuredData[] = 'Employment Type: ' . $existing_post->employment_status;
                }
                if ($existing_post->workplace) {
                    $structuredData[] = 'Work Arrangement: ' . $existing_post->workplace;
                }
                if ($existing_post->experience_period) {
                    $structuredData[] = 'Experience Required: ' . $existing_post->experience_period;
                }
                if ($existing_post->minimum_salary && $existing_post->maximum_salary) {
                    $structuredData[] = 'Salary Range: UGX ' . number_format($existing_post->minimum_salary) . ' - ' . number_format($existing_post->maximum_salary);
                } elseif ($existing_post->minimum_salary) {
                    $structuredData[] = 'Salary: UGX ' . number_format($existing_post->minimum_salary);
                }
                
                // Combine all details
                $allDetails = array_merge($detailsElements, $structuredData);
                $allDetails = array_unique($allDetails); // Remove duplicates
                
                if (!empty($allDetails)) {
                    $existing_post->details = implode(' | ', array_slice($allDetails, 0, 10)); // Limit to 10 details
                }

                // === ENHANCED COMPANY NAME & LOGO EXTRACTION ===
                $companySelectors = [
                    '.company-name',
                    '[class*="company"]',
                    '.employer',
                    '[class*="employer"]',
                    '[id*="company"]',
                    'h2',
                    'h3',
                    '.org-name',
                    '[class*="organization"]'
                ];

                foreach ($companySelectors as $selector) {
                    $element = $html->find($selector, 0);
                    if ($element) {
                        $companyText = trim(strip_tags($element->innertext));
                        if (strlen($companyText) > 2 && strlen($companyText) < 100) {
                            $existing_post->company_name = $companyText;
                            $existing_post->address = $companyText; // Keep legacy field populated
                            break;
                        }
                    }
                }

                // === COMPANY LOGO EXTRACTION ===
                $logoSelectors = [
                    '.company-logo img',
                    '.employer-logo img', 
                    '[class*="company"] img',
                    '[class*="employer"] img',
                    '[class*="logo"] img',
                    'img[alt*="logo"]',
                    'img[alt*="company"]',
                    'img[src*="logo"]',
                    'img[src*="company"]'
                ];

                foreach ($logoSelectors as $selector) {
                    $logoElement = $html->find($selector, 0);
                    if ($logoElement) {
                        $logoSrc = $logoElement->src;
                        if (!empty($logoSrc)) {
                            // Handle relative URLs
                            if (strpos($logoSrc, 'http') !== 0) {
                                if (strpos($logoSrc, '//') === 0) {
                                    $logoSrc = 'https:' . $logoSrc;
                                } elseif (strpos($logoSrc, '/') === 0) {
                                    $logoSrc = 'https://www.brightermonday.co.ug' . $logoSrc;
                                } else {
                                    $logoSrc = 'https://www.brightermonday.co.ug/' . $logoSrc;
                                }
                            }
                            // Validate it's an image URL
                            if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)(\?|$)/i', $logoSrc)) {
                                $existing_post->company_logo = $logoSrc;
                                break;
                            }
                        }
                    }
                }

                // Fallback: Look for any image near company name
                if (!$existing_post->company_logo && $existing_post->company_name) {
                    $allImages = $html->find('img');
                    foreach ($allImages as $img) {
                        $imgSrc = $img->src;
                        $imgAlt = $img->alt ?? '';
                        
                        // Check if image is likely a company logo
                        if (stripos($imgAlt, $existing_post->company_name) !== false ||
                            stripos($imgSrc, 'logo') !== false ||
                            stripos($imgAlt, 'logo') !== false) {
                            
                            if (!empty($imgSrc)) {
                                // Handle relative URLs
                                if (strpos($imgSrc, 'http') !== 0) {
                                    if (strpos($imgSrc, '//') === 0) {
                                        $imgSrc = 'https:' . $imgSrc;
                                    } elseif (strpos($imgSrc, '/') === 0) {
                                        $imgSrc = 'https://www.brightermonday.co.ug' . $imgSrc;
                                    } else {
                                        $imgSrc = 'https://www.brightermonday.co.ug/' . $imgSrc;
                                    }
                                }
                                
                                if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)(\?|$)/i', $imgSrc)) {
                                    $existing_post->company_logo = $imgSrc;
                                    break;
                                }
                            }
                        }
                    }
                }

                $existing_post->job_icon = $existing_post->company_logo;

                // === ENHANCED LOCATION/DISTRICT EXTRACTION ===
                $locationSelectors = [
                    '.location',
                    '[class*="location"]',
                    '.address',
                    '[class*="address"]',
                    '[class*="city"]',
                    '[class*="region"]',
                    '[id*="location"]'
                ];

                foreach ($locationSelectors as $selector) {
                    $element = $html->find($selector, 0);
                    if ($element) {
                        $location = trim(strip_tags($element->innertext));

                        // Enhanced district matching
                        $ugandaDistricts = [
                            'Kampala',
                            'Wakiso',
                            'Mukono',
                            'Entebbe',
                            'Jinja',
                            'Mbale',
                            'Gulu',
                            'Lira',
                            'Mbarara',
                            'Fort Portal',
                            'Kasese',
                            'Kabale',
                            'Masaka',
                            'Soroti',
                            'Arua',
                            'Kitgum',
                            'Moroto',
                            'Hoima'
                        ];

                        $foundDistrict = null;
                        foreach ($ugandaDistricts as $districtName) {
                            if (stripos($location, $districtName) !== false) {
                                $foundDistrict = District::where('name', 'LIKE', '%' . $districtName . '%')->first();
                                if ($foundDistrict) {
                                    $existing_post->district_id = $foundDistrict->id;
                                    break;
                                }
                            }
                        }

                        if (!$foundDistrict) {
                            // Default to Kampala
                            $kampala = District::where('name', 'LIKE', '%Kampala%')->first();
                            $existing_post->district_id = $kampala ? $kampala->id : 1;
                        }
                        break;
                    }
                }

                // === ENHANCED EMPLOYMENT TYPE EXTRACTION ===
                $empTypeSelectors = [
                    '.employment-type',
                    '[class*="type"]',
                    '[class*="employment"]',
                    '[class*="contract"]',
                    '[id*="type"]',
                    'span',
                    'div'
                ];

                foreach ($empTypeSelectors as $selector) {
                    $elements = $html->find($selector);
                    foreach ($elements as $element) {
                        $empTypeText = strtolower(trim(strip_tags($element->innertext)));

                        if (strpos($empTypeText, 'part-time') !== false || strpos($empTypeText, 'part time') !== false) {
                            $existing_post->employment_status = 'Part Time';
                            break 2;
                        } elseif (strpos($empTypeText, 'contract') !== false || strpos($empTypeText, 'temporary') !== false) {
                            $existing_post->employment_status = 'Contract';
                            break 2;
                        } elseif (strpos($empTypeText, 'intern') !== false || strpos($empTypeText, 'internship') !== false) {
                            $existing_post->employment_status = 'Internship';
                            break 2;
                        } elseif (strpos($empTypeText, 'full-time') !== false || strpos($empTypeText, 'full time') !== false || strpos($empTypeText, 'permanent') !== false) {
                            $existing_post->employment_status = 'Full Time';
                            break 2;
                        }
                    }
                }

                // === ENHANCED DEADLINE EXTRACTION ===
                $deadlineSelectors = [
                    '.deadline',
                    '[class*="deadline"]',
                    '[class*="expir"]',
                    '[class*="close"]',
                    '[class*="due"]',
                    '[id*="deadline"]',
                    'time',
                    '[datetime]'
                ];

                $deadline = null;
                foreach ($deadlineSelectors as $selector) {
                    $element = $html->find($selector, 0);
                    if ($element) {
                        $deadlineText = trim(strip_tags($element->innertext));

                        // Try multiple date parsing approaches
                        $datePatterns = [
                            '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/',
                            '/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/',
                            '/(\d{1,2})\s+(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})/i',
                            '/(\d{1,2})\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{4})/i'
                        ];

                        foreach ($datePatterns as $pattern) {
                            if (preg_match($pattern, $deadlineText, $matches)) {
                                try {
                                    $deadline = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($deadlineText)->format('Y-m-d H:i:s'));
                                    break 2;
                                } catch (\Exception $e) {
                                    continue;
                                }
                            }
                        }
                    }
                }

                // Set deadline (datetime) with proper Carbon instance
                $existing_post->deadline = $deadline ?: Carbon::now()->addDays(30);

                // === ENHANCED SALARY EXTRACTION WITH PROPER FLOAT VALUES ===
                $salarySelectors = [
                    '.salary',
                    '[class*="salary"]',
                    '[class*="pay"]',
                    '[class*="wage"]',
                    '[class*="compensation"]',
                    '[id*="salary"]',
                    '[class*="remuneration"]',
                    'span'  // Add span to catch Brighter Monday salary spans
                ];

                $minSalary = null;
                $maxSalary = null;
                $salaryFound = false;

                foreach ($salarySelectors as $selector) {
                    if ($salaryFound) break;

                    $elements = $html->find($selector);
                    foreach ($elements as $element) {
                        if ($salaryFound) break;

                        $salaryText = trim(strip_tags($element->innertext));

                        // SPECIFIC BRIGHTER MONDAY PATTERN: "USh" followed by range like "300,000 - 500,000"
                        if (preg_match('/USh\s*([0-9,]+)\s*[-–—]\s*([0-9,]+)/i', $salaryText, $matches)) {
                            $min = (float)str_replace([',', ' '], ['', ''], $matches[1]);
                            $max = (float)str_replace([',', ' '], ['', ''], $matches[2]);
                            $minSalary = min($min, $max);
                            $maxSalary = max($min, $max);
                            $existing_post->show_salary = 'Yes';
                            $salaryFound = true;
                            break;
                        }

                        // Enhanced salary parsing with multiple currency support
                        $salaryPatterns = [
                            // UGX/USh ranges with various formats
                            '/(?:UGX|USH|Shs?\.?)\s*([0-9,]+(?:\.[0-9]+)?)\s*(?:to|[-–—])\s*(?:UGX|USH|Shs?\.?)\s*([0-9,]+(?:\.[0-9]+)?)/i',
                            '/([0-9,]+(?:\.[0-9]+)?)\s*(?:to|[-–—])\s*([0-9,]+(?:\.[0-9]+)?)\s*(?:UGX|USH|Shs?\.?)/i',
                            '/(?:UGX|USH|Shs?\.?)\s*([0-9,]+(?:\.[0-9]+)?)/i',
                            '/([0-9,]+(?:\.[0-9]+)?)\s*(?:UGX|USH|Shs?\.?)/i',
                            // USD patterns
                            '/\$\s*([0-9,]+(?:\.[0-9]+)?)\s*(?:to|[-–—])\s*\$\s*([0-9,]+(?:\.[0-9]+)?)/i',
                            '/\$\s*([0-9,]+(?:\.[0-9]+)?)/i',
                            // General number ranges without currency symbols
                            '/([0-9,]+(?:\.[0-9]+)?)\s*[-–—]\s*([0-9,]+(?:\.[0-9]+)?)/i'
                        ];

                        foreach ($salaryPatterns as $pattern) {
                            if (preg_match($pattern, $salaryText, $matches)) {
                                if (isset($matches[2])) {
                                    // Range found
                                    $min = (float)str_replace([',', ' '], ['', ''], $matches[1]);
                                    $max = (float)str_replace([',', ' '], ['', ''], $matches[2]);
                                    $minSalary = min($min, $max);
                                    $maxSalary = max($min, $max);
                                } else {
                                    // Single amount
                                    $amount = (float)str_replace([',', ' '], ['', ''], $matches[1]);
                                    $minSalary = $amount;
                                    $maxSalary = $amount;
                                }
                                $existing_post->show_salary = 'Yes';
                                $salaryFound = true;
                                break;
                            }
                        }
                    }
                }

                // ADDITIONAL CHECK: Look specifically for Brighter Monday salary structure in HTML
                if (!$salaryFound && !$minSalary && !$maxSalary) {
                    // Find spans containing "USh" and extract adjacent number spans
                    $ushSpans = $html->find('span');
                    foreach ($ushSpans as $span) {
                        if ($salaryFound) break;

                        $spanText = trim(strip_tags($span->innertext));
                        if (stripos($spanText, 'USh') !== false || stripos($spanText, 'UGX') !== false) {
                            // Look for next sibling or nested span with numbers
                            $parent = $span->parent();
                            if ($parent) {
                                $allSpansInParent = $parent->find('span');
                                foreach ($allSpansInParent as $childSpan) {
                                    if ($salaryFound) break;

                                    $childText = trim(strip_tags($childSpan->innertext));
                                    // Look for pattern like "300,000 - 500,000"
                                    if (preg_match('/([0-9,]+)\s*[-–—]\s*([0-9,]+)/', $childText, $matches)) {
                                        $min = (float)str_replace([',', ' '], ['', ''], $matches[1]);
                                        $max = (float)str_replace([',', ' '], ['', ''], $matches[2]);
                                        $minSalary = min($min, $max);
                                        $maxSalary = max($min, $max);
                                        $existing_post->show_salary = 'Yes';
                                        $salaryFound = true;
                                        break;
                                    }
                                    // Single salary amount
                                    elseif (preg_match('/^([0-9,]+)$/', $childText, $matches)) {
                                        $amount = (float)str_replace([',', ' '], ['', ''], $matches[1]);
                                        if ($amount > 1000) { // Reasonable salary threshold
                                            $minSalary = $amount;
                                            $maxSalary = $amount;
                                            $existing_post->show_salary = 'Yes';
                                            $salaryFound = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Set salary values as float
                $existing_post->minimum_salary = $minSalary;
                $existing_post->maximum_salary = $maxSalary;

                // === EXTRACT EXPERIENCE REQUIREMENTS ===
                $expSelectors = [
                    '[class*="experience"]',
                    '[class*="year"]',
                    '[id*="experience"]'
                ];

                foreach ($expSelectors as $selector) {
                    $elements = $html->find($selector);
                    foreach ($elements as $element) {
                        $expText = trim(strip_tags($element->innertext));
                        if (preg_match('/(\d+)[\s\-]*(?:year|yr)/i', $expText, $matches)) {
                            $existing_post->experience_period = $matches[1] . ' years';
                            break 2;
                        }
                    }
                }

                // === EXTRACT GENDER REQUIREMENTS ===
                $genderText = strtolower($existing_post->responsibilities ?? '');
                if (strpos($genderText, 'female') !== false && strpos($genderText, 'male') === false) {
                    $existing_post->gender = 'Female';
                } elseif (strpos($genderText, 'male') !== false && strpos($genderText, 'female') === false) {
                    $existing_post->gender = 'Male';
                } else {
                    $existing_post->gender = 'Both';
                }

                // === EXTRACT AGE REQUIREMENTS AS INTEGERS ===
                $minAge = null;
                $maxAge = null;
                $responsibilitiesText = strip_tags($existing_post->responsibilities ?? '');

                if (preg_match('/age[s]?\s*:?\s*(\d+)[\s\-]*(?:to|[-–—])\s*(\d+)|(\d+)[\s\-]*(?:to|[-–—])\s*(\d+)\s*years?\s*old/i', $responsibilitiesText, $matches)) {
                    if (isset($matches[1]) && isset($matches[2])) {
                        $minAge = (int)min($matches[1], $matches[2]);
                        $maxAge = (int)max($matches[1], $matches[2]);
                    } elseif (isset($matches[3]) && isset($matches[4])) {
                        $minAge = (int)min($matches[3], $matches[4]);
                        $maxAge = (int)max($matches[3], $matches[4]);
                    }
                }

                $existing_post->min_age = $minAge;
                $existing_post->max_age = $maxAge;

                // === EXTRACT ACADEMIC QUALIFICATIONS ===
                $qualificationKeywords = ['degree', 'diploma', 'certificate', 'bachelor', 'master', 'phd', 'doctorate'];
                $qualificationText = strtolower(strip_tags($existing_post->responsibilities ?? ''));

                foreach ($qualificationKeywords as $keyword) {
                    if (strpos($qualificationText, $keyword) !== false) {
                        $existing_post->minimum_academic_qualification = ucfirst($keyword);
                        break;
                    }
                }

                // === WORKPLACE TYPE EXTRACTION ===
                $workplaceText = strtolower(strip_tags($existing_post->responsibilities ?? ''));
                if (strpos($workplaceText, 'remote') !== false || strpos($workplaceText, 'work from home') !== false) {
                    $existing_post->workplace = 'Remote';
                } elseif (strpos($workplaceText, 'hybrid') !== false) {
                    $existing_post->workplace = 'Hybrid';
                } else {
                    $existing_post->workplace = 'Onsite';
                }

                // === EXTRACT VACANCY COUNT AS INTEGER ===
                $vacancyCount = 1; // default
                $responsibilitiesText = strip_tags($existing_post->responsibilities ?? '');

                if (preg_match('/(\d+)\s*(?:position|vacancy|vacancies|opening|post)/i', $responsibilitiesText, $matches)) {
                    $vacancyCount = (int)$matches[1];
                } elseif (preg_match('/(?:position|vacancy|opening|post)[s]?\s*[:\-]?\s*(\d+)/i', $responsibilitiesText, $matches)) {
                    $vacancyCount = (int)$matches[1];
                }

                $existing_post->vacancies_count = $vacancyCount;

                // === EXTRACT VIDEO CV REQUIREMENT AS BOOLEAN ===
                $videoCvRequired = false;
                $responsibilitiesText = strtolower(strip_tags($existing_post->responsibilities ?? ''));

                $videoCvKeywords = ['video cv', 'video resume', 'video interview', 'record video', 'video submission'];
                foreach ($videoCvKeywords as $keyword) {
                    if (strpos($responsibilitiesText, $keyword) !== false) {
                        $videoCvRequired = true;
                        break;
                    }
                }

                $existing_post->required_video_cv = $videoCvRequired ? 'Yes' : 'No';

                // === INTELLIGENT CATEGORY DETECTION WITH CATEGORY TEXT ===
                $jobTitle = strtolower($existing_post->title ?? '');
                $jobDescription = strtolower(strip_tags($existing_post->responsibilities ?? ''));
                $combinedText = $jobTitle . ' ' . $jobDescription;
                
                $categoryTextFound = null;

                // FIRST: Check for Brighter Monday specific category links and extract category text
                $brighterMondayCategoryFound = false;
                $categoryLinks = $html->find('a[href*="brightermonday.co.ug/jobs/"]');

                foreach ($categoryLinks as $link) {
                    $categoryText = trim(strip_tags($link->innertext));
                    if (strlen($categoryText) > 3) {
                        // Store the original category text from Brighter Monday
                        if (!$categoryTextFound) {
                            $categoryTextFound = $categoryText;
                        }
                        
                        // Try to find matching category in database
                        $category = JobCategory::where('name', 'LIKE', '%' . $categoryText . '%')->first();
                        if ($category) {
                            $existing_post->category_id = $category->id;
                            $brighterMondayCategoryFound = true;
                            break;
                        }

                        // Try partial matches for common categories
                        $categoryMappings = [
                            'Banking' => ['Banking', 'Finance', 'Financial'],
                            'Finance' => ['Banking', 'Finance', 'Financial'],
                            'Insurance' => ['Insurance'],
                            'Engineering' => ['Engineering', 'Engineer'],
                            'Information Technology' => ['Technology', 'IT', 'Software', 'Computer'],
                            'Healthcare' => ['Health', 'Medical', 'Clinical'],
                            'Education' => ['Education', 'Teaching', 'Academic'],
                            'Marketing' => ['Marketing', 'Sales', 'Advertisement'],
                            'Human Resources' => ['Human Resource', 'HR', 'Personnel'],
                            'Administration' => ['Administration', 'Admin', 'Office'],
                            'Customer Service' => ['Customer', 'Service', 'Support'],
                            'Legal' => ['Legal', 'Law', 'Attorney']
                        ];

                        foreach ($categoryMappings as $dbCategoryName => $keywords) {
                            if ($brighterMondayCategoryFound) break;
                            foreach ($keywords as $keyword) {
                                if (stripos($categoryText, $keyword) !== false) {
                                    $category = JobCategory::where('name', 'LIKE', '%' . $dbCategoryName . '%')->first();
                                    if ($category) {
                                        $existing_post->category_id = $category->id;
                                        $brighterMondayCategoryFound = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }

                // Set the category_text field with the original Brighter Monday category
                $existing_post->category_text = $categoryTextFound;

                // FALLBACK: Use keyword-based category detection if no specific category link found
                if (!$brighterMondayCategoryFound) {
                    $categoryKeywordMappings = [
                        'Information Technology' => ['software', 'developer', 'programmer', 'it ', 'tech', 'system', 'database', 'web', 'mobile', 'computer', 'network', 'cyber', 'data', 'analyst'],
                        'Healthcare' => ['medical', 'doctor', 'nurse', 'health', 'hospital', 'clinical', 'pharmacy', 'medicine', 'patient'],
                        'Finance' => ['finance', 'accounting', 'bank', 'financial', 'audit', 'bookkeeping', 'treasury', 'investment'],
                        'Engineering' => ['engineer', 'engineering', 'technical', 'mechanical', 'electrical', 'civil', 'construction'],
                        'Education' => ['teacher', 'lecturer', 'professor', 'education', 'academic', 'school', 'university', 'tutor'],
                        'Marketing' => ['marketing', 'sales', 'advertisement', 'promotion', 'brand', 'digital marketing', 'social media'],
                        'Human Resources' => ['hr ', 'human resource', 'recruitment', 'personnel', 'talent', 'employee'],
                        'Customer Service' => ['customer', 'client', 'service', 'support', 'help desk', 'call center'],
                        'Administration' => ['admin', 'secretary', 'assistant', 'office', 'clerk', 'receptionist'],
                        'Legal' => ['legal', 'lawyer', 'attorney', 'law', 'paralegal', 'compliance'],
                    ];

                    $selectedCategory = null;
                    $maxMatches = 0;

                    foreach ($categoryKeywordMappings as $categoryName => $keywords) {
                        $matches = 0;
                        foreach ($keywords as $keyword) {
                            if (strpos($combinedText, $keyword) !== false) {
                                $matches++;
                            }
                        }
                        if ($matches > $maxMatches) {
                            $maxMatches = $matches;
                            $selectedCategory = $categoryName;
                        }
                    }

                    // Try to find the category in database
                    if ($selectedCategory) {
                        $category = JobCategory::where('name', 'LIKE', '%' . $selectedCategory . '%')->first();
                        if ($category) {
                            $existing_post->category_id = $category->id;
                            // Set category_text to the detected category if none was found from links
                            if (!$existing_post->category_text) {
                                $existing_post->category_text = $selectedCategory;
                            }
                        }
                    }
                }

                // Fallback category selection
                if (!$existing_post->category_id) {
                    $defaultCategory = JobCategory::where('name', 'LIKE', '%General%')
                        ->orWhere('name', 'LIKE', '%Other%')
                        ->orWhere('name', 'LIKE', '%Miscellaneous%')
                        ->first();
                    if (!$defaultCategory) {
                        $defaultCategory = JobCategory::first();
                    }
                    $existing_post->category_id = $defaultCategory ? $defaultCategory->id : 1;
                    
                    // Set generic category text if nothing else was found
                    if (!$existing_post->category_text) {
                        $existing_post->category_text = $defaultCategory ? $defaultCategory->name : 'General';
                    }
                }

                // Set default values for required fields with enhanced logic
                $existing_post->status = 'Active';
                $existing_post->show_salary = $existing_post->show_salary ?? 'No';
                $existing_post->required_video_cv = 'No';
                $existing_post->application_method = 'External Link';
                $existing_post->application_method_details = $this->url;

                // Set default district if not already set
                if (!$existing_post->district_id) {
                    $kampala = District::where('name', 'LIKE', '%Kampala%')->first();
                    $existing_post->district_id = $kampala ? $kampala->id : 1;
                }

                // Set employment status default if not found
                if (!$existing_post->employment_status) {
                    $existing_post->employment_status = 'Full Time';
                }

                // Generate meaningful slug
                if (!empty($existing_post->title)) {
                    $existing_post->slug = Str::slug($existing_post->title . '-' . time());
                }
                $existing_post->save();
                $this->status = 'completed';
                $this->error_message = null;
                $this->save();
            } catch (\Exception $e) {
                $this->status = 'error';
                $this->error_message = 'Failed to process job data: ' . $e->getMessage();
                $this->save();
                return;
            }
        } else {
            $this->status = 'error';
            $this->error_message = "Slug not found";
            $this->save();
        }
    }

    //belongs to job_web_site_id
    public function job_web_site()
    {
        return $this->belongsTo(JobWebSite::class);
    }
}
