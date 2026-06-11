@php
    $lovePage = [
        'day' => 5,
        'theme' => 'গোলাপ ফুটুক',
        'emoji' => '🌹',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমার জন্য হৃদয়ের বাগানে প্রতিদিন নতুন গোলাপ ফোটে।',
        'interaction' => 'rose',
        'reveal' => 'soft',
        'shape' => 'stamp',
        'palette' => [
            '#221018',
            '#e92f66',
            '#ffc0cb',
            '#fff4f7',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
