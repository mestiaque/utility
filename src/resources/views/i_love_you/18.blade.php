@php
    $lovePage = [
        'day' => 18,
        'theme' => 'সাগর ঢেউ',
        'emoji' => '🌊',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমার প্রতি টানটা সাগরের ঢেউয়ের মতো, বারবার ফিরে আসে।',
        'interaction' => 'rain',
        'reveal' => 'rise',
        'shape' => 'ticket',
        'palette' => [
            '#05243a',
            '#00b4d8',
            '#90e0ef',
            '#edfaff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
