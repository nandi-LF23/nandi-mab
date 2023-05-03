@component('mail::message')
# {{ $details['title'] }}

{{ $details['content'] }}

{{ config('app.name') }}
@endcomponent