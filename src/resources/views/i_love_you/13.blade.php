@php
    $lovePage = [
        'day' => 13,
        'theme' => 'কফির কাপ',
        'emoji' => '☕',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'এক কাপ কফি আর তোমার গল্প, আমার দিনের সবচেয়ে আরামদায়ক জায়গা।',
        'interaction' => 'photo',
        'reveal' => 'soft',
        'shape' => 'ticket',
        'palette' => [
            '#20120f',
            '#c97942',
            '#f3d2a2',
            '#fff4e6',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
