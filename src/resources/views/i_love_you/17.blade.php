@php
    $lovePage = [
        'day' => 17,
        'theme' => 'রংধনু কথা',
        'emoji' => '🌈',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তুমি থাকলে সাধারণ কথাও রংধনুর মতো সাত রঙে ভরে ওঠে।',
        'interaction' => 'butterfly',
        'reveal' => 'spin',
        'shape' => 'soft',
        'palette' => [
            '#18173a',
            '#ff6ec7',
            '#66f0ff',
            '#fff7ff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
