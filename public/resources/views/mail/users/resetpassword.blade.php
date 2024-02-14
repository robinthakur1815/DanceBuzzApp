@component('mail::message')
# Hi {{$user->name}}

Its Look like you requested for reset passsword, if you not requested please ignore this mail

Password link will expire after 24 hours

@component('mail::button', ['url' => $url])
    Reset Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
