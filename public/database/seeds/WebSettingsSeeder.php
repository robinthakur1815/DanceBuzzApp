<?php

use Illuminate\Database\Seeder;

class WebSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('web_settings')->truncate();
        // DB::table('web_settings')->insert([
        //     'title' => 'Admin Email',
        //     'setting_key' => 'admin_email',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Admin Email BCC',
        //     'setting_key' => 'admin_email_bcc',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Admin Email CC',
        //     'setting_key' => 'admin_email_cc',
        // ]);

        $datas = [
            [
                'title' => 'Facebook Link',
                'setting_key' => 'facebook_link',
            ],
            [
                'title' => 'Twitter Link',
                'setting_key' => 'twitter_link',
            ],
            [
                'title' => 'Linkedin Link',
                'setting_key' => 'linkedin_link',
            ],
            [
                'title' => 'Instagram Link',
                'setting_key' => 'instagram_link',
            ],
            [
                'title' => 'Youtube Link',
                'setting_key' => 'youtube_link',
            ],
            [
                'title' => 'DanceBuzz Anthem',
                'setting_key' => 'anthem_youtube_link',
            ],
            [
                'title' => 'Registered Address',
                'setting_key' => 'registered_address',
            ],
            [
                'title' => 'Regional Address',
                'setting_key' => 'regional_address',
            ],
            [
                'title' => 'Development Center',
                'setting_key' => 'development_center',
            ],
            [
                'title' => 'Support Email',
                'setting_key' => 'support_email',
            ],
            [
                'title' => 'Marketing Email',
                'setting_key' => 'marketing_email',
            ],
            [
                'title' => 'General Email',
                'setting_key' => 'general_email',
            ],
            [
                'title' => 'Contact Location',
                'setting_key' => 'contact_location',
            ],

        ];
        DB::table('web_settings')->insert($datas);

        // DB::table('web_settings')->insert([
        //     'title' => 'Facebook Link',
        //     'setting_key' => 'facebook_link',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Twitter Link',
        //     'setting_key' => 'twitter_link',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Linkedin Link',
        //     'setting_key' => 'linkedin_link',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Address',
        //     'setting_key' => 'address',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Contact No',
        //     'setting_key' => 'contact_no',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Support Email',
        //     'setting_key' => 'support_email',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Contact Location',
        //     'setting_key' => 'contact_location',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Contact Address',
        //     'setting_key' => 'contact_address',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'Contact Map',
        //     'setting_key' => 'contact_map',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'CMS Title',
        //     'setting_key' => 'cms_title',
        // ]);
        // DB::table('web_settings')->insert([
        //     'title' => 'CMS Logo',
        //     'setting_key' => 'cms_logo',
        // ]);
    }
}
