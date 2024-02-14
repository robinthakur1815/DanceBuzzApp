<?php

use Illuminate\Database\Seeder;

class UserRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->truncate();
        $roles = [
             ['name' => 'SuperAdmin', 'slug' => 'superadmin'],
             ['name' => 'Author', 'slug' => 'author'],
             ['name' => 'Blog Author', 'slug' => 'blog_author'],
            //  ['name' => 'Article Author' , 'slug' => 'article_author'],
             ['name' => 'News', 'slug' => 'news'],
             ['name' => 'Case Study', 'slug' => 'caseStudy_author'],
            //  ['name' => 'Videos Author' , 'slug' => 'videos_author'],
            //  ['name' => 'Galleries Author' , 'slug' => 'galleries_author'],
             ['name' => 'Testimonials Author', 'slug' => 'testimonials_author'],
             ['name' => 'Events Author', 'slug' => 'events_author'],
             ['name' => 'Partner Author', 'slug' => 'partners_author'],
             ['name' => 'Workshop Author', 'slug' => 'workshops_author'],
             ['name' => 'Classes Author', 'slug' => 'classes_author'],
             ['name' => 'Career Author', 'slug' => 'careers_author'],
             ['name' => 'People Author', 'slug' => 'people_author'],
             ['name' => 'Services Author', 'slug' => 'services_author'],
             ['name' => 'Panellist Author', 'slug' => 'panelList_author'],
             ['name' => 'Partner Gallery Author', 'slug' => 'partnerGalleries_author'],
             ['name' => 'Awards Author', 'slug' => 'awards_author'],
             ['name' => 'Carnival Activities Author', 'slug' => 'carnivalActivitites_author'],
             ['name' => 'Campaign Author', 'slug' => 'campaign_author'],
             ['name' => 'Approver', 'slug' => 'approver'],
             ['name' => 'Marketing', 'slug' => 'marketing'],
             ['name' => 'Products', 'slug' => 'products'],

            ];

        DB::table('roles')->insert($roles);
    }
}
