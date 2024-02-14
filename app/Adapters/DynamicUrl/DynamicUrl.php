<?php


namespace App\Adapters\DynamicUrl;
use Illuminate\Support\Facades\Http;

class DynamicUrl
{
    private $baseUrl;
    private $query;
    private $socialMetaTagInfo;
    private $googlePlayAnalytics;
    private $itunesConnectAnalytics;

    private $androidPackageName = 'com.DanceBuzz.app';
    private $iosBundleId = 'com.DanceBuzz.DanceBuzz';
    private $iosAppStoreId = '18152914';
    private $payload = [];

    private $shortLink, $previewLink;


    /**
     * DynamicUrlBase constructor.
     * @param $baseUrl
     * @param $query
     */
    public function __construct($baseUrl, $query)
    {
        $this->baseUrl = $baseUrl;
        $this->query = $query;
    }

    /*
     * Sample Object
      "socialMetaTagInfo": {
          "socialTitle": string,
          "socialDescription": string,
          "socialImageLink": string
       }
     */
    public function setSocialMetaTagInfo($socialMetaTagInfo)
    {
        $this->socialMetaTagInfo = $socialMetaTagInfo;
        return $this;
    }

    /*
     * Sample Object
        "googlePlayAnalytics": {
            "utmSource": string,
            "utmMedium": string,
            "utmCampaign": string,
            "utmTerm": string,
            "utmContent": string,
            "gclid": string
      },
     */
    public function setGooglePlayAnalytics($googlePlayAnalytics)
    {
        $this->googlePlayAnalytics = $googlePlayAnalytics;
        return $this;
    }

    /*
     * Sample Object
        "itunesConnectAnalytics": {
        "at": string,
        "ct": string,
        "mt": string,
        "pt": string
      }
     */
    public function setItunesConnectAnalytics($itunesConnectAnalytics)
    {
        $this->itunesConnectAnalytics = $itunesConnectAnalytics;
        return $this;
    }

    public function build()

    {
        $this->payload = [
            'dynamicLinkInfo' => [
                'domainUriPrefix' => config('app.firebase.domain_url_prefix'),
                'link' => $this->baseUrl . "?" .$this->query,
                'androidInfo' => ['androidPackageName' => $this->androidPackageName],
                'iosInfo' => ['iosBundleId' => $this->iosBundleId,
                        'iosAppStoreId' => $this->iosAppStoreId]
            ]
        ];
        if ($this->socialMetaTagInfo) data_set($this->payload,
            'dynamicLinkInfo.socialMetaTagInfo', $this->socialMetaTagInfo);

        if ($this->googlePlayAnalytics) data_set($this->payload,
            'dynamicLinkInfo.analyticsInfo.googlePlayAnalytics', $this->googlePlayAnalytics);

        if ($this->itunesConnectAnalytics) data_set($this->payload,
            'dynamicLinkInfo.analyticsInfo.itunesConnectAnalytics', $this->itunesConnectAnalytics);

        return $this;

    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function getTargetUrl()
    {
        return data_get($this->payload, 'dynamicLinkInfo.link');
    }

    public function create()
    {
        $options = [ 'verify' => false];
        $response = Http::retry(3, 1000)
            ->withOptions($options)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->post("https://firebasedynamiclinks.googleapis.com/v1/shortLinks?key=" . config('app.firebase.web_api_key'),
                $this->payload
            );
        if ($response->successful()) {
            $jsonResponse = $response->json();
            $this->shortLink = $jsonResponse['shortLink'];
            $this->previewLink = $jsonResponse['previewLink'];

        }
        return $this->shortLink;
    }







}
