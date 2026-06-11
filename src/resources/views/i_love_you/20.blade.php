@php
    $lovePage = [
        'day' => 20,
        'theme' => 'চেরি ব্লসম',
        'emoji' => '🌸',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তুমি পাশে থাকলে সময়ের ডালপালায় নরম ফুল ফুটে থাকে।',
        'interaction' => 'rose',
        'reveal' => 'soft',
        'shape' => 'soft',
        'palette' => [
            '#2b1221',
            '#ff8fab',
            '#ffc2d1',
            '#fff5f8',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
