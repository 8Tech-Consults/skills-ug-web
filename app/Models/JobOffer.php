<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    use HasFactory;

    //appends company_text 
    protected $appends = ['company_text', 'candidate_text'];


    public function getCandidateTextAttribute()
    {
        $cand = User::find($this->candidate_id);
        if (!$cand) {
            return '';
        }
        return $cand->name;
    }

    public function getCompanyTextAttribute()
    {
        $comp = User::find($this->company_id);
        if (!$comp) {
            return '';
        }
        return $comp->name;
    }
}
