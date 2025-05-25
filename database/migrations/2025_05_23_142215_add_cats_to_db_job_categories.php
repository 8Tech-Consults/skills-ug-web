<?php

use App\Models\JobCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCatsToDbJobCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $cats = [
            'Web & Mobile Development',
            'Graphic & UI/UX Design',
            'Content Writing & Copywriting',
            'Digital Marketing & SEO',
            'Video Production & Editing',
            'IT Support & Cybersecurity',
            'Data Analysis & Visualization',
            'Virtual Assistance & Admin Support',
            'Accounting & Bookkeeping',
            'Translation & Language Tutoring',
            'Photography & Photo Editing',
            'Voiceover & Audio Production',
            'Social Media Management',
            'Project Management',
            'Legal Consulting',
            'Career Coaching & Resume Writing',
            'Interior Design & 3D Modeling',
            'Health & Wellness Coaching',
            'Event Planning & Coordination',
            'Building & Construction',
            'Plumbing & Electrical Services',
            'Landscaping & Gardening',
            'Home Cleaning & Maintenance',
            'Automotive Repair & Maintenance',
            'Personal Training & Fitness Coaching',
            'Academic Tutoring & Test Prep',
            'Financial Planning & Advisory',
            'HR Consulting & Recruitment',
            'Agriculture & Farming Services',
            'Livestock Management & Veterinary Support',
            'Tailoring & Textile Alterations',
            'Hairdressing & Beauty Services',
            'Moto-Taxi (Boda Boda) & Delivery Services',
            'Catering & Food Preparation',
            'Carpentry & Woodworking',
            'Painting & Decorating',
            'Roofing & Masonry',
            'Pest Control & Fumigation',
            'Solar Panel Installation & Maintenance',
        ];

        foreach ($cats as $name) {
            // Check if a category with this name already exists
            $exists = JobCategory::where('name', $name)->exists();

            if (! $exists) {
                $newCat = new JobCategory();
                $newCat->name = $name;
                $newCat->description = '';
                $newCat->type = 'Service';
                $newCat->category_type = 'Service';
                $newCat->icon = '';
                $newCat->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_categories', function (Blueprint $table) {
            //
        });
    }
}
