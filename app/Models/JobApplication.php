<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    //boot creating
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $applicant = User::find($model->applicant_id);
            if (!$applicant) {
                throw new \Exception('Applicant not found');
            }
            $job = Job::find($model->job_id);
            if (!$job) {
                throw new \Exception('Job not found');
            }
            $model->status = 'Pending';
            $model->employer_id = $job->posted_by_id;
            $posted_by = User::find($job->posted_by_id);
            if (!$posted_by) {
                throw new \Exception('Employer not found');
            }
        });
    }


    //appends applicant_text
    protected $appends = ['applicant_text', 'job_text', 'employer_text'];

    //get applicant_text
    public function getApplicantTextAttribute()
    {
        $applicant = User::find($this->applicant_id);
        if (!$applicant) {
            return '';
        }
        return $applicant->name;
    }

    //get job_text
    public function getJobTextAttribute()
    {
        $job = Job::find($this->job_id);
        if (!$job) {
            return '';
        }
        return $job->title;
    }

    //get employer_text
    public function getEmployerTextAttribute()
    {
        $employer = User::find($this->employer_id);
        if (!$employer) {
            return '';
        }
        return $employer->name;
    }
}

/* 

Full texts
id
created_at
updated_at

attachments
employer_message
applicant_message
decline_reason
status
interview_email_sent
hired_email_sent
declinded_email_sent
interview_scheduled_at
interview_location
interview_type
interview_result
interviewer_notes
interviewer_rating
interviewee_feedback
interviewee_notes
interviewee_rating
contract_url
onboarding_start_date
onboarding_notes
additional_info
*/