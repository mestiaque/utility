@php
    $lovePage = [
        'day' => 8,
        'theme' => 'তারার চিঠি',
        'emoji' => '⭐',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'আকাশের প্রতিটা তারা যেন তোমার জন্য লেখা ছোট্ট চিঠি।',
        'interaction' => 'stars',
        'reveal' => 'zoom',
        'shape' => 'ticket',
        'palette' => [
            '#090d2c',
            '#a78bfa',
            '#fff176',
            '#f7f2ff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
