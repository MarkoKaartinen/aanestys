{{-- Renders an information page in the active language: content/{page}/{locale}.blade.php --}}
@include("content.$page.".app()->getLocale())
