<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyFollow extends Model
{
    use HasFactory;

    //boot
    

    //appends company_text and user_text
    protected $appends = ['company_text', 'user_text'];

    //getter for company_text
    public function getCompanyTextAttribute()
    {
        $comp = User::find($this->company_id);
        if ($comp) {
            return $comp->name;
        }
        return "";
    }

    //getter for user_text
    public function getUserTextAttribute()
    {
        $user = User::find($this->user_id);
        if ($user) {
            return $user->name;
        }
        return "";
    } 
}
