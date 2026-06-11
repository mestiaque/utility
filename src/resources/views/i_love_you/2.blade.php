@php
    $lovePage = [
        'day' => 2,
        'theme' => 'চাঁদের আলো',
        'emoji' => '🌙',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'চাঁদের মতো নরম আলোয় তুমি আমার রাতগুলো শান্ত করে দাও।',
        'interaction' => 'moon',
        'reveal' => 'rise',
        'shape' => 'soft',
        'palette' => [
            '#101827',
            '#8bb7ff',
            '#ffe7a3',
            '#fff7df',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
