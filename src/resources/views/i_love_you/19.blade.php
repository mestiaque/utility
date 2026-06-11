@php
    $lovePage = [
        'day' => 19,
        'theme' => 'ক্যান্ডি লাভ',
        'emoji' => '🍬',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমার আদরে মনটা ক্যান্ডির মতো মিষ্টি আর রঙিন হয়ে যায়।',
        'interaction' => 'balloon',
        'reveal' => 'zoom',
        'shape' => 'circle',
        'palette' => [
            '#33152b',
            '#ff7ac8',
            '#9bf6ff',
            '#fff1fb',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
