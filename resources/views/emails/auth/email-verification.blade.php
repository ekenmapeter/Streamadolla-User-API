<x-mail::message>
# Welcome, {{ $user->name }}!

Your AudioReach verification code is:

# {{ $code }}

Enter this code in the app to verify your email address. It expires in 10 minutes.

If you did not create an account, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>