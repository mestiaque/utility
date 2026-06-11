@php
    $lovePage = [
        'day' => 29,
        'theme' => 'শেষ রাতের তারা',
        'emoji' => '🌟',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'সবশেষ রাতেও তুমি থাকলে মনে হয় সকাল আসবেই।',
        'interaction' => 'stars',
        'reveal' => 'slide',
        'shape' => 'ticket',
        'palette' => [
            '#080b22',
            '#60a5fa',
            '#facc15',
            '#f8fafc',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
