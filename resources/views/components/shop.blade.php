@props(['title' => null, 'description' => null])
@include('layouts.shop', ['title' => $title, 'description' => $description, 'slot' => $slot])
