@php
    $lovePage = [
        'day' => 22,
        'theme' => 'লাভ ল্যাম্প',
        'emoji' => '🪔',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমার ভালোবাসা ছোট্ট প্রদীপের মতো, নিঃশব্দে সবকিছু আলোকিত করে।',
        'interaction' => 'stars',
        'reveal' => 'rise',
        'shape' => 'ticket',
        'palette' => [
            '#25140b',
            '#ff9f1c',
            '#ffe066',
            '#fff6df',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
