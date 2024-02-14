<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */
// Forget PAssword Routes
Route::get('ping', function () {
    return response()->json("pong");
});

Route::post('/register/{token?}', 'AuthController@register');
Route::post('/token/validate', 'AuthController@validateToken');
Route::post('/password/send/token', 'AuthController@sendResetPasswordMail');
Route::post('/password/reset', 'AuthController@resetPassword');
Route::post('/subscribe', 'ContactController@saveSubscribers');
Route::get('/discussion/topics', 'ContactController@discussionTopics');
Route::post('/save/quote', 'ContactController@saveQuote');
Route::post('/submit/contact/details', 'ContactController@saveContact');
Route::get('/collection/editor/{id?}', 'Mobile\VendorCollectionController@editor');
Route::get('/mobile/feed/{title}', 'Mobile\VendorCollectionController@editor');

Route::post('/payment/notify', 'Mobile\ApiCashFreePaymentController@mobilePaymentNotify');

Route::post('/check/version', 'Api\ApiAppVersionController@appVersion');
Route::post('/action/version', 'Api\ApiAppVersionController@appUpdateAction')->middleware('auth.dbguest');

Route::post('/vendor/services', 'CollectionController@getVendorServices');

Route::middleware(['auth.db', 'active'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('test', 'MediaController@test');

Route::get('/collection/categories/{type?}', 'CategoryController@getAllCategories');
Route::post('/collection/categories/paginate', 'CategoryController@getPaginateCategories');
Route::get('/collection/categories/single/{id}', 'CategoryController@getSingleCategory');

//tags
Route::post('/collection/tags/paginate', 'TagController@getPaginateTags');

/*
 * Web CMS Collection Data Routes
 */
Route::group(['prefix' => 'collection', 'middleware' => ['auth.db', 'active']], function () {
    Route::post('/user/role/testing', 'CollectionController@userRoleTesting');
    Route::post('/all', 'CollectionController@index');
    Route::post('/campaigns/csv', 'CollectionController@CampaignsCsv');
    Route::post('/campaign/sponsarscsv', 'CollectionController@CampaignSponsarsCsv');

    Route::post('/studentstory', 'CollectionController@StudentStory');
    Route::post('/studentstory/csv', 'CollectionController@StudentStoryCsv');

    Route::get('/{id}/detail', 'CollectionController@getCollectionDetails');
    Route::post('/store', 'CollectionController@store');
    Route::patch('/update/{id}', 'CollectionController@update');
    Route::post('/delete', 'CollectionController@deleteCollection');
    Route::post('/restore', 'CollectionController@restoreCollection');
    Route::post('/feature', 'CollectionController@featureCollection');
    Route::post('/status/update', 'CollectionController@updateCollectionStatus');
    Route::post('/update/isprivate', 'CollectionController@changePrivateCollectionStatus');
    Route::post('/duplicate', 'CollectionController@duplicateCollection');

    Route::post('/category/save', 'CategoryController@saveCategory');
    Route::delete('/category/delete/{id}', 'CategoryController@deleteCategory');
    Route::patch('/category/restore/{id}', 'CategoryController@restoreCategory');
    Route::post('/delete/all', 'CategoryController@deleteAllCategory');
    Route::post('/restore/all', 'CategoryController@restoreAllCategory');

    Route::post('/tag/save', 'TagController@saveTags');
    Route::delete('/tag/delete/{id}', 'TagController@deleteTag');
    Route::get('/tags', 'TagController@getAllTags');

    // Reviews Routes
    Route::post('/review/delete', 'ReviewController@deleteMultipleReviews');
    Route::post('/review/restore', 'ReviewController@restoreMultipleReviews');
    Route::post('/review/status/update', 'ReviewController@updateCommentStatus');
    Route::post('/reviews/all', 'ReviewController@getallReviews');

    Route::post('/like', 'CollectionController@savelikeUnlikeComment');
    Route::post('/comment', 'CollectionController@saveCollectionComment');
    Route::post('/comment/delete', 'CollectionController@deleteComment');
    Route::post('/comments/listing', 'CollectionController@getAllCollectionComments');
    Route::post('/comment/report/spam', 'CollectionController@commentReportSpam');
    Route::post('/comment/delete', 'CollectionController@deletePartnerComment');

    //

});

Route::group(['prefix' => '/spam/report', 'middleware' => ['auth.db', 'active']], function () {
    Route::post('/all', 'SpamReportsController@getSpamListing');
    Route::post('/delete', 'SpamReportsController@deleteSpamReport');
    Route::post('/approve', 'SpamReportsController@approveSpamReport');
});

/*
 * Web CMS Pages and Section Data Routes
 */
Route::group(['prefix' => 'page', 'middleware' => ['auth.db', 'active']], function () {
    Route::post('/save', 'PageController@savePageDetails');
    Route::get('/{id}/detail', 'PageController@getPageDetails');
    Route::post('/delete', 'PageController@deleteWebPages');
    Route::post('/restore', 'PageController@restoreWebPages');
    Route::post('/sections', 'PageController@getAllSections');
    Route::post('/section/save', 'PageController@saveSectionDetails');
    Route::post('/section/delete', 'PageController@deleteWebSections');
    Route::post('/section/restore', 'PageController@restoreWebSections');
    Route::get('/section/{id}/detail', 'PageController@getSectionDetails');
});

Route::post('/page/slug/section/detail', 'PageController@SectionSlugDetail');

/*
 * CMS Products, Price and Discounts Data Routes.
 */
Route::group(['prefix' => 'product', 'middleware' => ['auth.db', 'active']], function () {
    Route::post('/all', 'ProductController@getAllProducts');
    Route::get('/{id}', 'ProductController@getProductDetails');
    Route::post('/save', 'ProductController@saveProductDetails');
    Route::post('/restore', 'ProductController@restoreProduct');
    Route::post('/delete', 'ProductController@deleteProduct');
    Route::post('/status/update/{id}', 'ProductController@updateProductStatus');

    Route::get('/{id}/prices/all', 'ProductController@getProductPrices');
    Route::post('/prices/all', 'ProductController@getAllProductPrices');
    Route::get('/prices/{id}', 'ProductController@getProductPriceDetails');
    Route::post('/prices/save', 'ProductController@saveProductPrice');
    Route::post('prices/status/update/{id}', 'ProductController@updatePriceStatus');
    Route::post('/prices/delete', 'ProductController@deletePrice');
    Route::post('/prices/restore', 'ProductController@restorePrice');

    Route::get('/{id}/discounts/all', 'ProductController@getProductDiscounts');
    Route::get('/discounts/{id}', 'ProductController@getProductDiscountDetails');
    Route::post('/discounts/all', 'ProductController@getAllProductDiscounts');
    Route::post('/discounts/save', 'ProductController@saveProductDiscount');
    Route::post('/discounts/restore', 'ProductController@restoreDiscount');
    Route::post('/discounts/delete', 'ProductController@deleteDiscount');
    Route::post('/discounts/status/update/{id}', 'ProductController@updateDiscountStatus');

    Route::get('/{id}/coupons/all', 'ProductController@getProductCoupons');
    Route::get('/coupons/{id}', 'ProductController@getProductCouponDetails');
    Route::post('/coupons/all', 'ProductController@getAllProductCoupons');
    Route::post('/coupons/save', 'ProductController@saveProductCoupon');
    Route::post('/coupons/restore', 'ProductController@restoreCoupon');
    Route::post('/coupons/delete', 'ProductController@deleteCoupon');
    Route::post('/coupons/status/update/{id}', 'ProductController@updateCouponStatus');

    Route::get('/{id}/orders/all', 'ProductController@getProductOrders');
    Route::get('/orders/{id}', 'ProductController@getProductOrderDetails');
    Route::post('/orders/all', 'ProductController@getAllProductOrders');

    Route::get('/{id}/reviews/all', 'ProductController@getProductReviews');
    // Route::post('/reviews/all', 'ProductController@getAllLatestProductReviews');
    Route::post('/price/{actionType}', 'ProductController@attachProductPrice');
    Route::post('/price/discount/{actionType}', 'ProductController@attachProductDiscount');
});

// Route::get('/product/{id}/reviews/all', 'ProductController@getProductReviews');

/*
 * Other CMS data routes
 */
Route::group(['middleware' => ['auth.db', 'active']], function () {
    // Route::post('/cms/media/save', 'MediaController@storeNewCropperMedia');

    Route::get('/partner/collection/categories/{type?}', 'CategoryController@getAllCategories');

    Route::post('/download/request', 'Api\ApiStoryController@downloadRequest');

    Route::post('/users/listing', 'UserController@getUsersListing');
    Route::post('/users/csv', 'UserController@getUsersListingCsv');

    Route::post('/pages', 'PageController@getAllPages');
    Route::get('/countries', 'MasterController@getAllCountries');

    Route::get('/files', 'MediaController@fileindex');
    Route::patch('/file/{id}/update', 'MediaController@updatefileData');
    Route::post('/file/save', 'MediaController@storeNewFile');

    Route::post('/media/save', 'MediaController@storeNewMedia');
    Route::get('/media', 'MediaController@index');
    Route::patch('/media/{id}/update', 'MediaController@updateMediaData');

    Route::post('/cms/media/save', 'MediaController@storeNewCropperMedia');
    // Route::get('/cropper/media', 'MediaController@getCropperMedias');
    Route::post('/cropper/media', 'MediaController@getCropperMedias');

    Route::post('/users', 'UserController@getAllUsers');
    Route::post('/user/save', 'UserController@saveUserDetails');
    Route::post('/user/delete', 'UserController@deleteUsers');
    Route::post('/user/restore', 'UserController@restoreUsers');
    Route::get('/user/{id}', 'UserController@show');
    Route::patch('/user/update/{id}', 'UserController@updateUserDetails');
    // Route::delete("/user/delete/{id}", 'UserController@destroy');

    Route::delete('/delete/media/{id}', 'MediaController@deleteMedia');

    Route::get('/user', 'AuthController@currentUser');
    Route::post('/partnerdashboard', 'AuthController@loginBySuperAdmin');
    Route::patch('/change/password', 'AuthController@changePassword');
    Route::post('/user/avatar/all', 'UserController@getUserAvatars');
    Route::post('/user/update/avatar', 'UserController@updateAvatar');
    Route::post('/user/update/status', 'UserController@updateUserStatus');

    Route::post('/subscribers', 'ContactController@getAllSubscribers');
    Route::post('/subscribers/delete', 'ContactController@deleteSubscribers');
    Route::post('/subscribers/restore', 'ContactController@restoreSubscribers');
    Route::post('/subscriber/update/status', 'ContactController@updateSubscriberStatus');
    Route::post('/subscribers/download/csv', 'ContactController@downloadSubscribers');

    Route::post('/contacts', 'ContactController@getAllContactQuiries');
    Route::post('/contact/delete', 'ContactController@deleteContact');
    Route::post('/contact/restore', 'ContactController@restoreContacts');
    Route::post('/contactus/download/csv', 'ContactController@downloadContactUs');

    Route::post('/quotes', 'ContactController@getAllQuoteDetails');
    Route::post('/quote/delete', 'ContactController@deleteQuote');
    Route::post('/quote/restore', 'ContactController@restoreQuote');

    // WebSetting page routes
    Route::get('/settings/data', 'MasterController@getSettingsData');
    Route::post('/settings/data/save', 'MasterController@saveSettingPageData');

    // Tag Group routes
    Route::post('/tag/group/save', 'TagController@saveTagGroup');
    Route::post('/tag/group/list', 'TagController@getTagGroupList');
    Route::post('/tag/group/delete', 'TagController@deleteTagGroup');

    // Category Group Routes
    Route::post('/category/group/save', 'CategoryController@saveCategoryGroup');
    Route::post('/category/group/list', 'CategoryController@getCategoryGroupList');
    Route::post('/category/group/delete', 'CategoryController@deleteCategoryGroup');

    // push  notification
    Route::get('/notification/sends', 'Api\ApiSendPushNotificationController@index');
    Route::post('/notification/listing', 'Api\ApiSendPushNotificationController@index');
    Route::post('/notification/send', 'Api\ApiSendPushNotificationController@send');
    Route::post('/notification/send/partner/{id}', 'Api\ApiSendPushNotificationController@partnerNotification'); // api for sending notification from partner app

    Route::post('/notification/send/vendor', 'Api\ApiSendPushNotificationController@vendorSend'); // api for sending notification from partner app

    Route::post('/notifications', 'Api\ApiSendPushNotificationController@getNotification');

    //my orders

    Route::post('/my/orders', 'Api\Mobile\ApiAuthUserController@myOrders');
    Route::post('/my/dashboard', 'Api\Mobile\ApiAuthUserController@dashboard');
    Route::get('/my/dashboard', 'Api\Mobile\ApiAuthUserController@myDashboard');
    Route::post('/my/class/sessions/history', 'Api\Mobile\ApiAuthUserController@liveClassHistorySessions');
    Route::post('/my/class/recordings', 'Api\Mobile\ApiAuthUserController@classRecordings');

    Route::post('/my/sessions/live', 'Api\Mobile\ApiAuthUserController@latestLiveClass');
});

Route::post('/viewcount/save', 'UserController@saveViewCount');
Route::get('/viewcount/show/{page_type}', 'UserController@ViewCount');

Route::group(['middleware' => ['auth.dbguest']], function () {
    Route::post('/my/class/sessions', 'Api\Mobile\ApiAuthUserController@liveClassSessions');
});
// Route::group(['prefix' => 'page'], function () {
// Route::get('/{slug}', 'WebController@getWebPageDetails');
// });

/*
 * Web Collections Routes
 */
Route::group(['prefix' => 'web', 'middleware' => ['auth.dbguest']], function () {
    Route::get('/page/{slug}', 'WebController@getWebPageDetails');
    Route::get('/collection/all/{type}/{count?}', 'WebController@getCollectionData');
    Route::post('/collection/all/{type}', 'WebController@getFilteredCollections');
    Route::post('/collection/filter/{type}', 'WebController@filterParticularCollections');
    Route::post('/mobile/collection/filter/{type}', 'WebController@filterParticularCollectionsMobile');

    Route::post('/collection/data/{type}', 'WebController@getCollectionDataList');

    // Route::get('/collection/recent/{type}/{count}', 'WebController@getCollectionData');
    Route::get('/collection/featured', 'WebController@getFeaturedCollection');
    Route::get('/collection/recommended/all/{count?}', 'WebController@getAllRecommendedCollection');
    Route::get('/collection/suggested/all/{count?}', 'WebController@getAllSuggestedCollection');
    Route::get('/collection/tag/{slug}', 'WebController@tag');

    // All Categories Listing of a collection type
    Route::post('/collection/categories', 'WebController@getCollectionTypeCategories');
    Route::post('/collection/tags/list', 'WebController@getCollectionTypeTags');

    Route::get('/collection/category/{type}/{catslug}', 'WebController@getCategoryCollections');

    Route::get('/collection/tag/{type}/{tagslug}/{count?}', 'WebController@collectionTypeTags');
    Route::get('/collection/{type}/{slug}', 'WebController@collectionTypeSlug');

    Route::get('/collectiondata/{type}/{id}', 'WebController@getSingleCollectionData');

    Route::get('/collection/featured/{type?}', 'WebController@getFeaturedCollection');
    // Route::get('/collection/recommended/{type?}/{count?}', 'WebController@getRecommendedCollection');
    Route::get('/recommended/{type?}/{count?}', 'WebController@getRecommendedCollection');

    // Route::post('/event/filtered', 'WebController@getFilteredEvents');
    Route::get('/sector/{tag}/{type?}', 'WebController@getSectorCollections');

    Route::post('/search/result', 'WebController@getSearchResults');
    Route::get('/settings', 'WebController@getSettingsData');
    Route::get('/tag/group/{slug}/{type?}', 'WebController@getTagGroupList');
    Route::get('/category/group/{slug}', 'WebController@getCategoryGroupList');
    Route::post('/event/filtered/all', 'WebController@getFilterCollectionEvents');
    Route::get('/download/file/{url}', 'MasterController@downloadFile');

    //Validating product coupon code

    Route::get('/mobileclient/check/payment/status/{orderId}', 'ProductController@paymentMobileClientStatus');

    Route::post('/validate/coupon/code', 'ProductController@validateCouponCode');
    // Route::post('/validate/payment', 'ProductController@validatePayment');
    Route::get('/payment/return/url', 'ProductController@returnURL');
    Route::get('/check/payment/status/{orderId}', 'ProductController@paymentStatus');
});

Route::group(['prefix' => 'web', 'middleware' => ['auth.db', 'active']], function () {
    Route::post('/validate/payment', 'ProductController@validatePayment');
    Route::post('/validate/free/payment', 'ProductController@validateFreePayment');
});

/*
 * Client Mobile App Api's
 */

Route::group(['prefix' => 'mobile'], function () {
    Route::group(['middleware' => ['auth.dbguest']], function () {
        Route::get('/event/featured/{city?}', 'Mobile\ApiClientWebController@eventListingPage');
        Route::get('/collection/{slug}', 'Mobile\ApiClientWebController@collectionDetailPage');
        Route::post('/collection/filtered/listing', 'Mobile\ApiClientWebController@collectionFilteredListing');
        Route::post('/collection/recommended/listing', 'Mobile\ApiClientWebController@recommendedLiveClasses');

        Route::get('/event/cities', 'Mobile\ApiClientWebController@getCities');
        Route::get('/event/categories', 'Mobile\ApiClientWebController@getCategories');
        Route::get('/event/services', 'Mobile\ApiClientWebController@getServices');

        Route::post('/faq/listing', 'CollectionController@getCollectionData');
    });

    //event create partner
    Route::group(['middleware' => ['auth.db']], function () {
        Route::post('/transaction/listing', 'WebOrderController@getAllUserTransactions');

        Route::get('/vendor/events/{vendorId}', 'Mobile\VendorCollectionController@index');
        Route::get('/vendor/event/{vendorId}/{id}/details', 'Mobile\VendorCollectionController@show');

        Route::post('/vendor/event/create/{vendorId}', 'Mobile\VendorCollectionController@store');
        Route::post('/vendor/event/update/{vendorId}', 'Mobile\VendorCollectionController@update');
        Route::post('/vendor/event/change-status/{vendorId}/{id}', 'Mobile\VendorCollectionController@changeStatus');
        Route::post('/vendor/event/delete/{vendorId}/{id}', 'Mobile\VendorCollectionController@delete');
    });

    //tag and category
    Route::group(['middleware' => ['auth.db']], function () {
        Route::post('/collection/category/save', 'CategoryController@saveCategory');
        Route::post('/collection/tag/save', 'TagController@saveTags');
        Route::get('/tags', 'TagController@getAllTags');
    });
});

/*
 * Reviews to be shown on web end, authenticated from vendor database.
 */
Route::group(['prefix' => 'review', 'middleware' => ['auth.dbguest']], function () {
    Route::post('/submit', 'ReviewController@authSubmitReview');
    Route::post('/product/all', 'ReviewController@getCollectionReviews');
    Route::post('/auth/delete', 'ReviewController@authDeleteReview');
});

// Route::post('/user/transactions', 'WebOrderController@getAllUserTransactions');

Route::group(['prefix' => 'seo'], function () {
    // Route::get('/blogs/{onlyPublished?}', 'SeoController@getAllBlogsListing');
    // Route::get('/young_xpert/{onlyPublished?}', 'SeoController@getAllCaseStudiesListing');
    // Route::get('/events/{onlyPublished?}', 'SeoController@getAllEventsListing');
    Route::get('/{collectionName}/{onlyPublished?}', 'SeoController@getAllCollectionSlugListing');
});

/*
 * Web CMS Feeds and Feed comments routes
 *
 *   'middleware' => ['auth:api', 'active']
 */

Route::post('/feed/likes', 'FeedController@getFeedLikeList');
// Route::post('/feed/likes', 'FeedController@getFeedList');

Route::group(['prefix' => 'feed'], function () {
    Route::group(['middleware' => ['auth.dbguest']], function () {
        Route::get('/all', 'FeedController@getAllFeeds');
        Route::get('/comments/listing', 'FeedController@getAllFeedComments');
       
        
    });

    Route::group(['middleware' => ['auth.db']], function () {
        Route::post('/save', 'FeedController@saveFeedData');
        Route::post('/listing', 'FeedController@getAllFeeds');
        Route::get('/{id}/details', 'FeedController@getFeedData');
        Route::post('/status/update', 'FeedController@updateFeedStatus');
        Route::post('/disable', 'FeedController@disableEnableFeed');

        Route::post('/like', 'FeedController@savelikeUnlikeFeed');
        Route::post('/comment', 'FeedController@saveFeedComment');
        Route::post('/master/comment', 'FeedController@saveSuperAdminFeedComment');
        Route::post('/master/comment/edit', 'FeedController@editSuperAdminFeedComment');
        Route::post('/comment/delete', 'FeedController@deleteComment');
        Route::post('/comment/report/spam', 'FeedController@commentReportSpam');
        Route::post('/comment/disable', 'FeedController@disableEnableComment');
        Route::post('admin/comment/delete', 'FeedController@deleteSuperAdminComment');
        // Route::post('/likes', 'FeedController@getFeedList');
    });
});

/*
 * Vendor Routes
 */
Route::group(['prefix' => 'vendor'], function () {
    Route::post('/all', 'VendorController@getAllVendors');
    Route::post('/download', 'VendorController@downloadCSV');
    Route::get('/categories', 'VendorController@getAllCategories');
    Route::get('/schools', 'VendorController@getAllSchools');
    Route::post('/download', 'VendorController@downloadCSV');
    Route::get('/all', 'VendorController@getVendorListing');
    Route::get('/{id}', 'VendorController@getSingleVendor');
    Route::get('/locations/{id}', 'VendorController@vendorLocations');

    // Route::get('/{id}', 'VendorController@getSingleVendor');
    Route::post('/status/update', 'VendorController@updateVendorStatus');
    // Route::get('/location/status/update/{id}/{status}', 'VendorController@approveLocationStatus');
    // Route::post('/delete', 'VendorController@deleteVendor');
    // Route::post('/restore', 'VendorController@restoreVendor');
});

Route::group(['prefix' => 'school', 'middleware' => ['auth.db', 'active']], function () {
    Route::post('/students/list', 'StudentController@getRegisteredDBKids');
    Route::post('/guardians/list', 'StudentController@getRegisteredGuardians');
    Route::post('/guardians/update', 'StudentController@activateDeactivateGuardians');

    Route::post('/students/update', 'StudentController@activateDeactivateStudents');

    Route::get('/student/{id}/detail', 'StudentController@getStudentDetails');
    Route::get('/guardian/{id}/detail', 'StudentController@getGuardianDetails');
    Route::get('/transaction/{id}/detail', 'StudentController@getStudentTransactionDetail');
    // Route::get('/guardian/{id}/detail', 'StudentController@getGuardianDetails');
});

Route::post('/uploadstory', 'Api\ApiStoryController@importBulkStory');
Route::group(['prefix' => 'story', 'middleware' => ['auth.db', 'active']], function () {
    Route::post('/submit', 'Api\ApiStoryController@create');
    Route::post('/validate/student', 'Api\ApiStoryController@validateStory');
    Route::post('/delete/{id}', 'Api\ApiStoryController@deleteStory');
    Route::post('/uploadstory', 'Api\ApiStoryController@importBulkStory');

    Route::post('/status/update', 'Api\ApiStoryController@updateStoryStatus');
    Route::post('/shoppable/update', 'Api\ApiStoryController@updateShoppable');

    Route::get('/students/all', 'Api\ApiStoryController@stories');
    Route::get('/students/{id}/details', 'Api\ApiStoryController@storyShow');
    Route::get('/getallcert/{id}', 'Api\ApiStoryController@getAllcertificatePDF');

    Route::get('/pdf', 'Api\ApiStoryController@createPDF');
    Route::post('/all', 'Api\ApiStoryController@cmsStories');
    Route::post('/download/csv', 'Api\ApiStoryController@downloadCSV');
    Route::get('/{id}/details', 'Api\ApiStoryController@cmsStoryShow');
    Route::post('/reject', 'Api\ApiStoryController@rejectedStories');
});
Route::group(['prefix' => 'school', 'middleware' => ['auth.db', 'active']], function () {

    Route::post('/story/all', 'Reports\RegistrationReportController@downloadReport');

});

Route::group(['prefix' => 'feedback', 'middleware' => ['auth.dbguest']], function () {
    Route::post('/submit', 'Api\ApiFeedbackController@create');
    Route::post('/all', 'Api\ApiFeedbackController@index');
    Route::get('/{id}/details', 'Api\ApiFeedbackController@show');
});

Route::get('/checkNotification', 'ProductController@checkNotification');
Route::post('/checkNotification', 'ProductController@checkNotification');
Route::post('/send-notification-to-user/{id}', 'ProductController@sendNotificationToUser');
Route::post('/send-notification/attached/user', 'ProductController@sendAttachedNotificationToUser');

Route::post('/student/uploadTest', 'Api\ApiStoryController@uploadTest');



Route::group(['prefix' => 'dynamic'], function () {
    Route::group(['middleware' => ['auth.dbguest']], function () {
        Route::post('/create/feed/url', 'DynamicUrlController@createDynamicUrlForFeed');
        Route::post('/create/campaign/url', 'DynamicUrlController@createDynamicUrlForCampaign');
        Route::post('/create/young-expert/url', 'DynamicUrlController@createDynamicUrlForYoungExperts');
        
    });
});