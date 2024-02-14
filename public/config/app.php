<?php

return [

    'case_study_top'    => 'tisya-singh', // slug for user on first posititon
    'case_study_id'     => env('CASE_STUDY_ID', 73),
    'temp_folder_path'  => '/images/',
    'from_email'        => 'support@dancebuzz.com',
    'cms_media_path'    => 'cms/images',
    'cms_files_path'    => 'cms/files',
    'cms_files_type'    => 'application/pdf',
    'user_profile_path' => 'user/%d',
    'admin_email'       => 'support@dancebuzz.com',
    'talentbox_email'   => 'tushar@bluelupin.com',

    'user_model'        => App\User::class,

    'client_url' => env('DB_WEB_URL', 'https://www.dancebuzz.com'),

    'creative_corner_enthu_point' => 50,

    'backend_api' => [
        'base_url' => env('BACKEND_API_URL', 'https://vendorapi.dancebuzz.com'),
    ],
    'firebase' => [
        'web_api_key' => env('FIREBASE_WEB_API_KEY'),
        'domain_url_prefix' => 'https://dancebuzz.page.link'
    ],
    'client_url' => env('DB_WEB_URL', 'https://kcweb.bluelup.in/'),
    'client__backend_url' => env('DB_CLIENT__BACKEND_URL', ''),


    'oauth' => [
        'auth_url'        =>  env('AUTH_URL', 'http://127.0.0.1:8000'),
        'client_id'       =>  env('CLIENT_ID', ''),
        'client_secret'   =>  env('CLIENT_SECRET', ''),
        'auth_server'     =>  env('AUTH_SERVER', 'remote')
    ],

    'liveclass' => [
        'api'                  => env('LIVE_CLASS_URL', ''),
        'secret'               => env('LIVE_CLASS_SECRET', ''),
        'live_class_recording' => env('LIVE_CLASS_RECORDING', ''),

    ],

    'colorthon_campaign_link' => [

        'art_treat'              => "https://dancebuzz.page.link/mXi3",
        'all_about_shades'       => "https://dancebuzz.page.link/UQmb",
        'creative_streak'        => "https://dancebuzz.page.link/dL7x",
    ],

    'colorthon_campaign_type' => [

        'art_treat'              => "1236",
        'all_about_shades'       => "1237",
        'creative_streak'        => "1238",
    ],

    'grade' => [

        'second' => "2",
        'seventh' => "7",
        'eight' => "8",
    ],

    'category_id_colorthon' => "596",
   

    'cash_free' => [
        'app_id'                        => env('CASH_FREE_APP_ID', '730443367355fda000d2f27d4037'),
        'secret_key'                    => env('CASH_FREE_SECRET_KEY', 'cc766b81b4ed38e9bdf9d250649009fa6617d56d'),
        'url'                           => env('CASH_FREE_URL', 'https://test.cashfree.com/billpay/checkout/post/submit'),
        'CURLOPT_URL'                   => env('CASH_FREE_STATUS_API_URL', 'https://test.cashfree.com/api/v1/order/info/status'),
        'mode'                          => env('CASH_FREE_MODE', 'TEST'),
        'CURLOPT_RETURNTRANSFER'        => env('CURLOPT_RETURNTRANSFER', true),
        'CURLOPT_MAXREDIRS'             => env('CURLOPT_MAXREDIRS', 10),
        'CURLOPT_TIMEOUT'               => env('CURLOPT_TIMEOUT', 30),
        'CURLOPT_HTTP_VERSION'          => env('CURLOPT_HTTP_VERSION', CURL_HTTP_VERSION_1_1),
        'CURLOPT_POSTFIELDS'            => env('CURLOPT_POSTFIELDS', 'appId=%s&secretKey=%s&orderId=%d'),

        'notifyUrl'                     => env('CASH_FREE_NOTIFY_URL', '/'), //'http://localhost:3000/payment/notify'
        'returnUrl'                     => env('CASH_FREE_RETURN_URL', '/'), //http://localhost:3000/event/payment-status,
        'returnUrlLiveClasses'          => env('CASH_FREE_RETURN_LIVE_CLASSES_URL', '/'), //http://localhost:3000/event/payment-status,
        'returnUrlClasses'              => env('CASH_FREE_RETURN_CLASSES_URL', '/'), //http://localhost:3000/event/payment-status

    ],

    'pages' => [
        '/about-us',
        // '/abt-us',
        '/awards',
        '/blogs',
        '/career-detail',
        '/careers',
        '/carnival',
        '/carrierme',
        '/case-studies',
        '/case-study-detail',
        '/contact-us',
        '/copy-right',
        '/copyright',
        '/creative-corner',
        '/earned-points',
        '/event-detail',
        '/events',
        '/forgot-kidID',
        '/forgot-password',
        '/guardian-registration',
        '/how-it-works',
        '/login',
        '/media-gallery',
        '/my-transactions',
        '/news',
        '/our-story',
        '/our-team',
        '/p-policy',
        '/panellist',
        '/partner-register',
        '/partners',
        '/paymentstatus',
        '/privacy-policy',
        '/profile',
        '/scholarship-kids',
        '/scholarship-teachers',
        '/search',
        '/signup',
        '/submit-story',
        '/support',
        '/terms-service',
        '/vendor-register',
        // '/event/booking',
        // '/event/payment-status',
        // '/mobile/partner-register',
        '/school/student/list',
        '/school/student/register',
        '/school/student/update',
        '/blog/:slug?',
        '/career/:slug?',
        '/case_study/:slug?',
        '/class/:slug?',
        '/event/:slug?',
        '/news/:slug',
        '/panellist/:slug',
        '/partner/:slug?',
        '/reset_password/:token?',
        '/workshop/:slug?',
        '/',
    ],

    'slug_pages' => [
        '/blog/:slug?',
        '/career/:slug?',
        '/case_study/:slug?',
        '/class/:slug?',
        '/event/:slug?',
        '/news/:slug',
        '/panellist/:slug',
        '/partner/:slug?',
        '/reset_password/:token?',
        '/workshop/:slug?',
    ],

    'feed_model'        => App\Feed::class,
    'custom_feed_model' => App\CustomFeed::class,
    'collection_model' => App\Collection::class,
    'user_model_type'   => App\User::class,
    's3url'             => env('PARTNER_AWS_URL', ''),
    'class_model_type' => 'App\VendorClass',
    'csv_report_path' => '/csv',

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'Asia/Kolkata',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        Alaouy\Youtube\YoutubeServiceProvider::class,

        /*
         * Package Service Providers...
         */
        Barryvdh\DomPDF\ServiceProvider::class,
        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\TelescopeServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        //Barryvdh\DomPDF\ServiceProvider::class,

        // Intervention\Image\ImageServiceProvider::class

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Youtube' => Alaouy\Youtube\Facades\Youtube::class,
        // 'Image' => Intervention\Image\Facades\Image::class
        'PDF' => Barryvdh\DomPDF\Facade::class,
        

    ],

];
