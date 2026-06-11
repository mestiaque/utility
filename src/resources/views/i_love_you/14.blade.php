@php
    $lovePage = [
        'day' => 14,
        'theme' => 'জোনাকির রাত',
        'emoji' => '✨',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'অন্ধকার যতই থাকুক, তোমার ভালোবাসা জোনাকির মতো পথ দেখায়।',
        'interaction' => 'stars',
        'reveal' => 'rise',
        'shape' => 'circle',
        'palette' => [
            '#071f1b',
            '#7cffcb',
            '#fff38a',
            '#effff8',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
