@php
    $lovePage = [
        'day' => 10,
        'theme' => 'লাল বেলুন',
        'emoji' => '🎈',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'একটা বেলুনে যদি ভালোবাসা ভরা যেত, আমি আকাশটাই পাঠাতাম।',
        'interaction' => 'balloon',
        'reveal' => 'rise',
        'shape' => 'circle',
        'palette' => [
            '#2c1020',
            '#ff3d5a',
            '#ffd166',
            '#fff6e8',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
