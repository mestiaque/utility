@php
    $lovePage = [
        'day' => 9,
        'theme' => 'প্রজাপতির ছোঁয়া',
        'emoji' => '🦋',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমার কাছে এলেই মনটা রঙিন প্রজাপতির মতো উড়তে থাকে।',
        'interaction' => 'butterfly',
        'reveal' => 'slide',
        'shape' => 'soft',
        'palette' => [
            '#17302c',
            '#28d2bd',
            '#ffc857',
            '#f1fffb',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
