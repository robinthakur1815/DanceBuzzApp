<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute स्वीकार किया जाना चाहिए।',
    'active_url' => ' :attribute एक मान्य URL नहीं है।',
    'after' => ' :attribute :date इस तारीख के बाद होना चाहिए।',
    'after_or_equal' => ':attribute :date के बाद या उसके बराबर की तारीख होनी चाहिए।',
    'alpha' => ':attribute ',
    'alpha_dash' => ':attribute केवल अक्षर, संख्या, डैश और अंडरस्कोर हो सकते हैं।',
    'alpha_num' => ':attribute केवल अक्षर और संख्याएँ हो सकती हैं।',
    'array' => ':attribute एक सरणी होनी चाहिए।',
    'before' => ':attribute :date पहले एक तारीख होनी चाहिए।',
    'before_or_equal' => ':attribute :date इससे पहले या उसके बराबर की तारीख होनी चाहिए।',
    'between' => [
        'numeric' => ':attribute :min तथा :max के बीच होना चाहिए।',
        'file' => ':attribute :min तथा :max kilobytes के बीच होना चाहिए।',
        'string' => ':attribute :min तथा :max characters के बीच होना चाहिए।',
        'array' => ':attribute :min तथा :max items.',
    ],
    'boolean' => ':attribute क्षेत्र सही या गलत होना चाहिए।',
    'confirmed' => ':attribute पुष्टि मेल नहीं खाती।',
    'date' => ':attribute मान्य तिथि नहीं है।',
    'date_equals' => ':attribute :date के बराबर तारीख होनी चाहिए।',
    'date_format' => ':attribute :format प्रारूप से मेल नहीं खाता।',
    'different' => ':attribute तथा :other अलग होना चाहिए।',
    'digits' => ':attribute :digits अंक होना चाहिए।',
    'digits_between' => ':attribute :min तथा :max अंक के बीच होना चाहिए।',
    'dimensions' => ':attribute अमान्य छवि आयाम हैं।',
    'distinct' => ':attribute फ़ील्ड का डुप्लिकेट मान है।',
    'email' => ':attribute एक वैध ई - मेल पता होना चाहिए।',
    'ends_with' => ':attribute उत्तरगामी में से किसी एक के साथ समाप्त होना चाहिए: :values',
    'exists' => 'चुना हुआ :attribute अमान्य है।',
    'file' => ':attribute एक फ़ाइल होनी चाहिए।',
    'filled' => ':attribute फ़ील्ड का मान होना चाहिए।',
    'gt' => [
        'numeric' => ':attribute :value से अधिक होना चाहिए।',
        'file' => ':attribute :value किलोबाइट से अधिक होना चाहिए।',
        'string' => ':attribute :value अक्षर से अधिक होना चाहिए।',
        'array' => ':attribute :value आइटम अक्षर से अधिक होना चाहिए।',
    ],
    'gte' => [
        'numeric' => ':attribute :value से अधिक या बराबर होना चाहिए।',
        'file' => ':attribute :value किलोबाइट से अधिक या बराबर होना चाहिए।',
        'string' => ':attribute :value अक्षर से अधिक या बराबर होना चाहिए।',
        'array' => ':attribute :value आइटम या अधिक होना आवश्यक है।',
    ],
    'image' => ':attribute एक छवि होनी चाहिए।',
    'in' => 'चुना हुआ :attribute अमान्य है।',
    'in_array' => ':attribute :other फ़ील्ड में मौजूद नहीं है।',
    'integer' => ':attribute पूर्णांक होना चाहिए।',
    'ip' => ':attribute एक मान्य IP पता होना चाहिए।',
    'ipv4' => ':attribute एक मान्य IPv4 पता होना चाहिए।',
    'ipv6' => ':attribute एक मान्य IPv6 पता होना चाहिए।',
    'json' => ':attribute एक वैध JSON स्ट्रिंग होना चाहिए।',
    'lt' => [
        'numeric' => ':attribute :value से कम होना चाहिए।',
        'file' => ':attribute :value किलोबाइट से कम होना चाहिए।',
        'string' => ':attribute :value अक्षर से कम होना चाहिए।',
        'array' => ':attribute :value आइटम से कम होनी चाहिए।',
    ],
    'lte' => [
        'numeric' => ':attribute :value. से कम या बराबर होना चाहिए।',
        'file' => ':attribute :value किलोबाइट से कम या बराबर होना चाहिए।',
        'string' => ':attribute :value अक्षर से कम या बराबर होना चाहिए।',
        'array' => ':attribute :value आइटम से अधिक नहीं होनी चाहिए।',
    ],
    'max' => [
        'numeric' => ':attribute :max से अधिक नहीं हो सकता है।',
        'file' => ':attribute :max किलोबाइट से अधिक नहीं हो सकता है।',
        'string' => ':attribute :max अक्षर से अधिक नहीं हो सकता है।',
        'array' => ':attribute :max आइटम से अधिक नहीं हो सकता है।',
    ],
    'mimes' => ':attribute :values एक प्रकार की फ़ाइल होनी चाहिए।',
    'mimetypes' => ':attribute :values एक प्रकार की फ़ाइल होनी चाहिए।',
    'min' => [
        'numeric' => ':attribute कम से कम होना चाहिए :min।',
        'file' => ':attribute कम से कम होना चाहिए :min किलोबाइट।',
        'string' => ':attribute कम से कम होना चाहिए :min अक्षर।',
        'array' => ':attribute कम से कम होना ही चाहिए अधिकतम :min ।',
    ],
    'not_in' => 'चुना हुआ :attribute अमान्य है।',
    'not_regex' => ':attribute प्रारूप अमान्य है।',
    'numeric' => ':attribute एक संख्या होनी चाहिए।',
    'present' => ':attribute फ़ील्ड मौजूद होना चाहिए।',
    'regex' => ':attribute प्रारूप अमान्य है।',
    'required' => ':attribute फ़ील्ड आवश्यक है।',
    'required_if' => ':attribute फ़ील्ड आवश्यक हो जब :other :value है। ',
    'required_unless' => ':attribute  फ़ील्ड आवश्यक है  जब :other :values. में है। ',
    'required_with' => ':attribute फ़ील्ड आवश्यक है जब :values उपस्थित है।',
    'required_with_all' => ':attribute फ़ील्ड आवश्यक है जब :values मौजूद हैं।',
    'required_without' => ':attribute फ़ील्ड आवश्यक है जब :values मौजूद नहीं है।',
    'required_without_all' => ':attribute फ़ील्ड की आवश्यकता तब होती है जब कोई भी :values मौजूद नहीं होता हैं।',
    'same' => ':attribute तथा :other मेल खाना चाहिए।',
    'size' => [
        'numeric' => ':attribute :size होना चाहिए।',
        'file' => ':attribute :size किलोबाइट होना चाहिए।',
        'string' => ':attribute :size अक्षर होना चाहिए।',
        'array' => ':attribute :size  आइटम शामिल होना चाहिए।',
    ],
    'starts_with' => ':attribute :values निम्न में से एक के साथ शुरू होना चाहिए:',
    'string' => ':attribute एक तार होना चाहिए।',
    'timezone' => ':attribute एक वैध क्षेत्र होना चाहिए।',
    'unique' => ':attribute पहले से ही लिया जा चुका है।',
    'uploaded' => ':attribute अपलोड करने में विफल।',
    'url' => ':attribute प्रारूप अमान्य है।',
    'uuid' => ':attribute एक वैध यूयूआईडी होना चाहिए।',

     /* Feed controller */
    'desc_image' => 'कृपया विवरण दर्ज करें या पहले किसी छवि का चयन करें।',
    'success' => 'सफलता',
    'server_err' => 'सर्वर त्रुटि',
    'feed_not_exist' => 'चारा नहीं मिला',
    'feed_updated' => 'फ़ीड स्थिति पहले ही अपडेट की जा चुकी है',
    'post_updated' => 'पोस्ट सफलतापूर्वक अपडेट की गई',
    'comment_delete' => 'टिप्पणी नहीं मिली या पहले से हटा दी गई है',

    /* Web controller */
    'no_page' => 'पृष्ठ नहीं मिला',
    'no_tag' => 'टैग नहीं मिला',
    'no_data' => 'डेटा नहीं मिला',
    'no_sector' => 'सेक्टर नहीं मिला',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

    'invalid_email_phone' => 'कृपया मान्य प्रारूप में ईमेल या फोन दर्ज करें।',
    'referred_partner_mail_title' => 'आपको किड्सचुपल में एक भागीदार के रूप में संदर्भित किया गया है।',
    'referred_guardian_mail_title' => 'आपको किड्सचुपल में अभिभावक के रूप में संदर्भित किया गया है।',

];
