<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <title>Content Management System</title>
</head>
<body>

    <div align="center">
        <img src="https://dancebuzz-mails.s3-us-west-2.amazonaws.com/zircon/zircon+logo.png" alt="" style="height: 86px;" class="">
        <table cellpadding="12" style="color:#9ca6af;padding: 1%;font-size: 13px;background: #9013fe05; width:600px;    ">
            <tbody>
                <tr style=" background: #FF4401">
                    <td colspan="3" style="color: #ffffff;font-size: 20px;font-weight: 500;padding-bottom: 2%;font-family: Helvetica Neue,Helvetica,Arial,sans-serif;">
                        Requested Quote</td>

                </tr>
              
                <tr>
                    <td style="font-family: Helvetica Neue,Helvetica,Arial,sans-serif;">Full
                        Name</td>
                    <td colspan="2" style="color:black; padding-left:0px; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;">
                        {{$quote->name}}</td>
                </tr>
                <tr>
                    <td style="font-family: Helvetica Neue,Helvetica,Arial,sans-serif;">
                        Contact Number</td>
                    <td colspan="2" style="color:black; padding-left:0px; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;">
                        {{$quote->phone}}</td>
                </tr>
                <tr>
                    <td style="font-family: Helvetica Neue,Helvetica,Arial,sans-serif;">Email
                        Address</td>
                    {{-- <td style="color:#fff;line-height:15px; color:#fff; width:14px;background-color:#e362e3;border-radius:50%; padding:11px;">
                        <span style="color:#fff;font-size:14px;font-weight:bold;font-family:Helvetica,arial,sans-serif;text-decoration:none;text-align:center;">
                            <strong style="white-space:nowrap;font-weight:600">TG</strong>
                        </span>
                    </td> --}}
                    <td style="color:black; padding:0px; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;">
                        <b>
                            <p>{{$quote->email}}</p>
                        </b>
                    </td>
                </tr>
                <tr>
                    <td style="font-family: Helvetica Neue,Helvetica,Arial,sans-serif;">
                        Discussion Topic</td>
                    <td colspan="2" style="color:black;padding-left:0px; font-family: Helvetica Neue,Helvetica,Arial,sans-serif;">
                        {{str_replace('_',' ',\App\Enums\DiscussionTopicEnum::getKey($quote->topic_id))}}</td>
                </tr>
               
            </tbody>
        </table>
        <table cellspacing="8" align="center">
            <tbody>
                <tr align="center">
                    <td width="80px" height="50px"> <a href="https://www.facebook.com/dancebuzz/" target="blank" style="text-decoration:none;">
                            <p style="background: #9013fe;color: #FFFFFF;width:20px;height:20px;/* border: 1px solid #afb7be; */border-radius: 1000px;padding: 15px;font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;">
                                fb </p>
                        </a> </td>
                    <td width="80px" height="50px"> <a href="https://in.linkedin.com/"  target="blank" style="text-decoration:none;">
                            <p style="color: #ffffff;background: #9013fe;width:20px;height:20px;/* border: 1px solid #afb7be; */border-radius: 50%;padding: 15px;font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;">
                                ln </p>
                        </a> </td>
                    <td width="80px" height="50px"> <a href="https://www.instagram.com/dancebuzz/"  target="blank" style="text-decoration:none;">
                            <p style="color: #ffffff;background: #9013fe;width:20px;height:20px;/* border: 1px solid #afb7be; */border-radius: 50%;padding: 15px;font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;">
                                is</p>
                        </a> </td>

                </tr>
            </tbody>
        </table>
        <p style="text-align:center;font-size:12px;color:#8a959e;margin: 0px;font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;padding-bottom: 60px;">
            Highway towers, Sector 62, Noida, UP - 201307
        </p>
    </div>




</body>

</html>