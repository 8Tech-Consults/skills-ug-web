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
}
