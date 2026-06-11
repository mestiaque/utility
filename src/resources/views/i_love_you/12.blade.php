@php
    $lovePage = [
        'day' => 12,
        'theme' => 'চকলেট সকাল',
        'emoji' => '🍫',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমার সঙ্গে কথা হলেই সকালটা চকলেটের মতো মিষ্টি হয়ে যায়।',
        'interaction' => 'heart',
        'reveal' => 'zoom',
        'shape' => 'soft',
        'palette' => [
            '#2a140f',
            '#d47b4a',
            '#ffd6a5',
            '#fff3e5',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
