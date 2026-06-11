@php
    $lovePage = [
        'day' => 24,
        'theme' => 'নীল প্রজাপতি',
        'emoji' => '🦋',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তুমি আমার বুকের ভেতর নীল প্রজাপতির মতো নরম আলোড়ন।',
        'interaction' => 'butterfly',
        'reveal' => 'slide',
        'shape' => 'circle',
        'palette' => [
            '#092238',
            '#38bdf8',
            '#a7f3d0',
            '#effcff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
