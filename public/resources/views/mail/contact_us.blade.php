@component('mail::message')
# Hi Admin

Its look like someone want hear from you.

# Here is Details
Name: {{$data['name']}} <br>
Email: {{$data['email']}}<br>
Message: {{$data['message']}}


Thanks,<br>
{{ config('app.name') }}
@endcomponent
