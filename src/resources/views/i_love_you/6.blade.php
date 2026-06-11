@php
    $lovePage = [
        'day' => 6,
        'theme' => 'পান্ডার কিস',
        'emoji' => '🐼',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'একটা ছোট্ট কিউট কিস, শুধু তোমার হাসিটা দেখার জন্য।',
        'interaction' => 'panda',
        'reveal' => 'spin',
        'shape' => 'circle',
        'palette' => [
            '#161616',
            '#ff6f91',
            '#f8e9d2',
            '#ffffff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
