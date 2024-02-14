<body link="#00a5b5" vlink="#00a5b5" alink="#00a5b5">

<style>
    @media only screen and (max-width: 600px) {
    .main {
        width: 320px !important;
    }

    .top-image {
        width: 100% !important;
    }
    .inside-footer {
        width: 320px !important;
    }
    table[class="contenttable"] { 
        width: 320px !important;
        text-align: left !important;
    }
    td[class="force-col"] {
        display: block !important;
    }
     td[class="rm-col"] {
        display: none !important;
    }
    .mt {
        margin-top: 15px !important;
    }
    *[class].width300 {width: 255px !important;}
    *[class].block {display:block !important;}
    *[class].blockcol {display:none !important;}
    .emailButton{
        width: 100% !important;
    }
    .emailButton a {
        display:block !important;
        font-size:18px !important;
    }
    .content-table{padding: 0px !important; font-size: 15px; line-height: 23px;}
    .content-table strong{width: 150px !important;}
    .service-heading{padding: 0px !important;}
    .mktEditable{padding: 0px !important;}
    #main_title{font-size: 20px;}

}
</style>

@php 
        $contactUrl = config('app.client_url') . '/contact-us';
    @endphp

<table class=" main contenttable" align="center" style="font-weight: normal;border-collapse: collapse;border: 0;margin-left: auto;margin-right: auto;padding: 0;font-family: Arial, sans-serif;color: #555559;background-color: white;font-size: 16px;line-height: 26px;width: 600px;">
    <tr>
        <td class="border" style="border-collapse: collapse;border: 1px solid #eeeff0;margin: 0;padding: 0;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;">
            <table style="font-weight: normal;border-collapse: collapse;border: 0;margin: 0;padding: 0;font-family: Arial, sans-serif;">
                <tr>
                    <td colspan="4" valign="top" class="image-section" style="border-collapse: collapse;border: 0; text-align: center; margin: 0;padding: 0;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;background-color: #fff;border-bottom: 4px solid #00a5b5">
                        <a href="#"><img class="logo-image" src="https://bluelupin-mails.s3-us-west-2.amazonaws.com/DanceBuzz/logo.png" style="line-height: 1; width: 200px;" alt=""></a>
                    </td>
                </tr>
                <tr>
                    <td valign="top" class="side title" style="border-collapse: collapse;border: 0;margin: 0;padding: 5px 0 20px;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;vertical-align: top;background-color: white;border-top: none;">
                        <table style="font-weight: normal;border-collapse: collapse;border: 0;margin: 0; width:100%; padding: 0;font-family: Arial, sans-serif;">
                            <tr>
                                <td class="head-title" style="border-collapse: collapse;border: 0;margin: 0;padding: 0;-webkit-text-size-adjust: none;color: #333333;font-family: Arial, sans-serif;font-size: 25px;line-height: 34px;font-weight: bold; text-align: center;">
                                    <div class="mktEditable" id="main_title">
                                            {{$data['title'] ?? 'Payment done'}}				
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="top-padding" style="border-collapse: collapse;border: 0;margin: 0;padding: 5px;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;"></td>
                            </tr>
                            <tr>
                                <td class="grey-block" style="border-collapse: collapse;border: 0;margin: 0;-webkit-text-size-adjust: none;color: #333333;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;background-color: #fff; text-align:left;">
                                    <div class="mktEditable" id="cta" style="padding:0 20px;">
                                        <img class="top-image" src="https://dbemails.s3.ap-south-1.amazonaws.com/email-img2.png" width="560"/><br><br>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <table class="content-table" align="center" style="padding:0 20px; width:100%; line-height: 28px;">
                                        <tr>
                                            <td style="vertical-align: top !important;">
                                               @if($data['is_partner'])
                                                    <strong>
                                                        Hello , <br/>
                                                    </strong>
                                                @else
                                                    <strong>
                                                        Hello {{$data['purchaser'] ?? 'Participant'}}, <br/>
                                                    </strong>
                                                @endif
                                                <br/>
                                                    {{$data['description'] ?? ''}}
                                                <br/>
                      
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>


                           
                            <tr>
                                <td>
                                    <table class="content-table" style="padding:0 20px; width:100%; line-height: 28px;">

                                        @if($data['studentName'])
                                            <tr>
                                                <td style="vertical-align: top !important;">
                                                    <strong style="float:left; width:180px;">
                                                    Student name:
                                                    </strong>
                                                </td>
                                                <td>{{$data['studentName']}}</td>
                                            </tr>
                                        @endif

                                        @if($data['created_at'])
                                            <tr>
                                                <td  style="vertical-align: top !important;"><strong style="float:left; width:180px;">Payment date:</strong></td>
                                                <td>{{$data['created_at']}}</td>
                                            </tr>
                                        @endif
                                        
                                        
                                        @if($data['start_date'])
                                            <tr>
                                                <td  style="vertical-align: top !important;"><strong style="float:left; width:180px;">Start date:</strong></td>
                                                <td>{{$data['start_date']}}</td>
                                            </tr>
                                        @endif

                                        @if($data['start_time'])
                                            <tr>
                                                <td  style="vertical-align: top !important;"><strong style="float:left; width:180px;">Start Time:</strong></td>
                                                <td>{{$data['start_time']}}</td>
                                            </tr>
                                        @endif

                                        @if($data['end_date'])
                                            <tr>
                                                <td  style="vertical-align: top !important;"><strong style="float:left; width:180px;">End date:</strong></td>
                                                <td>{{$data['end_date']}}</td>
                                            </tr>
                                        @endif

                                        @if($data['end_time'])
                                            <tr>
                                                <td  style="vertical-align: top !important;"><strong style="float:left; width:180px;">End Time:</strong></td>
                                                <td>{{$data['end_time']}}</td>
                                            </tr>
                                        @endif

										
										@if($url)
                                            <tr>
                                                <td  style="vertical-align: top !important;"><strong style="float:left; width:180px;">View  {{isset($data['collectionTitle']) ? $data['collectionTitle'] : ''}} :</strong></td>
                                                <td><a href="{{$url}}" target="_blank" style="text-decoration:none;">View</a></td>
                                            </tr>
                                        @endif
										
                                    </table>
                                </td>
                            </tr>
                           	
                        </table>
                    </td>
                </tr>										
                <tr>
                    <td valign="top" align="center" style="border-collapse: collapse;border: 0;margin: 0;padding: 0;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;">
                        <table style="font-weight: normal;border-collapse: collapse;border: 0;margin: 0;padding: 0;font-family: Arial, sans-serif;">
                            <tr>
                                <td align="center" valign="middle" class="social" style="border-collapse: collapse;border: 0;margin: 0;padding: 10px;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;text-align: center;">
                                    <table style="font-weight: normal;border-collapse: collapse;border: 0;margin: 0;padding: 0;font-family: Arial, sans-serif;">
                                        <tr>
                                            <td style="border-collapse: collapse;border: 0;margin: 0;padding: 5px;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;">
                                                <a href="https://www.instagram.com/DanceBuzz/" target="blank"><img src="https://dbemails.s3.ap-south-1.amazonaws.com/instagram.png"></a>
                                            </td>
                                            <td style="border-collapse: collapse;border: 0;margin: 0;padding: 5px;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;">
                                                <a href="https://twitter.com/DanceBuzz" target="blank"><img src="https://dbemails.s3.ap-south-1.amazonaws.com/twitter.png"></a>
                                            </td>
                                            <td style="border-collapse: collapse;border: 0;margin: 0;padding: 5px;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;">
                                                <a href="https://www.facebook.com/DanceBuzz/" target="blank"><img src="https://dbemails.s3.ap-south-1.amazonaws.com/facebook.png"></a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr bgcolor="#fff" style="border-top: 4px solid #00a5b5;">
                    <td valign="top" class="footer" style="border-collapse: collapse;border: 0;margin: 0;padding: 0;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 16px;line-height: 26px;background: #fff;text-align: center;">
                        <table style="font-weight: normal;border-collapse: collapse;border: 0;margin: 0;padding: 0;font-family: Arial, sans-serif;">
                            <tr>
                                <td class="inside-footer" align="center" valign="middle" style="border-collapse: collapse;border: 0;margin: 0;padding: 20px;-webkit-text-size-adjust: none;color: #555559;font-family: Arial, sans-serif;font-size: 12px;line-height: 16px;vertical-align: middle;text-align: center;width: 580px;">
                                <div id="address" class="mktEditable">
                                    <b>Registered Address</b><br>
                                    Block A-1, Workhubz, Kamal Cinema Complex,<br> Safdarjung Enclave, New Delhi - 110029
                                    <a style="color: #00a5b5;" href="{{$contactUrl}}">Contact Us</a>
                                </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
