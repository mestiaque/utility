@php
    $lovePage = [
        'day' => 26,
        'theme' => 'মিষ্টি চিঠি',
        'emoji' => '💌',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'এই ছোট্ট চিঠির ভেতর যতটুকু লেখা যায়, তার চেয়ে অনেক বেশি ভালোবাসি।',
        'interaction' => 'photo',
        'reveal' => 'rise',
        'shape' => 'ticket',
        'palette' => [
            '#301724',
            '#fb7185',
            '#fde68a',
            '#fff7ed',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
