<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewRecord extends Model
{
    use HasFactory;

    //boot
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $accpeted_types = ['COMPANY', 'JOB', 'CV'];
            if (!in_array($model->type, $accpeted_types)) {
                throw new \Exception('View Type not supported');
            }
            $company = null;
            if ($model->type == 'CV') {
                $company = User::find($model->item_id);
            }else if ($model->type == 'JOB') {
                $job = Job::find($model->item_id);
                if ($job) {
                    $company = User::find($job->user_id);
                }
            }else if ($model->type == 'COMPANY') {
                $company = User::find($model->item_id); 
            }

            if ($company == null) {
                throw new \Exception('View Type not found');
            }
            $model->company_id = $company->id;
            return true;
        });
    }

    //append viewer_text and item_text
    protected $appends = ['viewer_text', 'item_text'];

    //getter for viewer_text
    public function getViewerTextAttribute()
    {
        $viewer = User::find($this->viewer_id);
        if ($viewer) {
            return $viewer->name;
        }
        return "";
    }

    //getter for item_text
    public function getItemTextAttribute()
    {
        $item = null;
        if ($this->type == 'COMPANY') {
            $item = User::find($this->item_id);
        } else if ($this->type == 'JOB') {
            $item = Job::find($this->item_id);
        } else if ($this->type == 'CV') {
            $item = User::find($this->item_id);
        }
        if ($item) {
            return $item->name;
        }
        return "";
    }
}
