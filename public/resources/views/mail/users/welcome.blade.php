<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Welcome To Content Management System</title>
    </head>
    <body>
        <table cellpadding="10" align="center" style="width: 100%; background: #f3f4f5;">
                <tr>
                    <td align="center"><img src="https://kc-web-qa.s3.ap-south-1.amazonaws.com/cms/images/1561545119.png" alt="" style="height: 60px; "></td>
                </tr>
                <tr>
                    <td>
                    <table cellpadding="15" align="center" style="background: white; padding-top: 70px;padding-bottom: 70px; width:100%; ">
                        <tr>
                            <td align="center">
                                <h1 style="letter-spacing: 0.012em;font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;font-size: 25px; font-weight: 200;text-align: center; color: #17191d; width: 100%;">Welcome {{$user->name}} ! You have been registered on the content management portal with {{\App\Enums\UserRole::getKey($user->name)}} role.</h1>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            <p style="font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif; margin-bottom:1em;letter-spacing:0.012em;font-size:14px;font-weight:300;text-align:center;color:#4a4e57">Your username is: <b>"{{$user->email}}"</b></p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            <p style="font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif; margin-bottom:1em;letter-spacing:0.012em;font-size:14px;font-weight:300;text-align:center;color:#4a4e57">Your password is: <b>"{{$password}}"</b></p>
                            </td>
                        </tr>
                        {{-- <tr>
                            <td align="center">
                                <a href="{{url('/login')}}" style="font-family:'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;letter-spacing:0.012em;font-size:11px;color:#ff3366">
                                    Click Here to Get Started
                                </a>
                            </td>
                        </tr> --}}
                    </table>
            </td>
            </tr>
            <tr>
                <td align="center" style="padding-top:50px">
                    <a href="#">
                        <span style="font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif; font-size:11px;border-radius:50px;border:2px solid #ff3366;background-color:#ff3366;padding:16px 60px 16px 60px;color:#fff;display:inline-block;letter-spacing:0.1em;text-align:center;text-transform:uppercase;"> If you have any problems, please contact us at  01143618778</span>
                    </a>
                
            </td>
            </tr>
            <tr>
                <td>
                    <table align="center" width="100%">
                        <tr>
                            <td align="center" style="letter-spacing:0.012em;font-family:'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;font-size:22px;font-weight:200;color:#1f2532;margin:0;padding:0;"> 
                                The digital online platform for the cms related content.
                            </td>
                        </tr>
                    
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                    <table cellspacing="8" align="center">
                        <tr align="center">
                            <td> <a href="https://www.facebook.com/DanceBuzz/" target="blank" style="text-decoration:none;"><p style="color: black; width:30px; line-height:30px; height:30px; border: 1px solid #afb7be; border-radius: 1000px; padding: 10px;font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif; ">FB</p></a>  </td>
                            <td> <a href="https://in.linkedin.com/" target="blank"  style="text-decoration:none;"><p style="color: black; width:30px; line-height:30px; height:30px; border: 1px solid #afb7be; border-radius: 50%; padding: 10px; font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;">LN</p></a>  </td>
                            <td> <a href="https://twitter.com/DanceBuzz" target="blank" style="text-decoration:none;"><p style="color: black; width:30px; line-height:30px; height:30px; border: 1px solid #afb7be; border-radius: 50%; padding: 10px; font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;">TW</p></a> </td>
                            
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr>
                <td style="text-align:center;font-size:12px;color:#8a959e; margin: 0px; font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;">
                    Highway towers, sector 62, noida, UP - 201307
                </td>
            </tr>
            <tr>
                <td style="text-align:center;font-size:12px;color:#8a959e; margin: 0px; font-family: 'Poppins',BlinkMacSystemFont,Helvetica,Arial,sans-serif;">
                    If you have any problems, please contact us at tushar@bluelupin.com
                </td>
            </tr>
        </table>

    </body>
</html>
