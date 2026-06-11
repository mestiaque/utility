@php
    $lovePage = [
        'day' => 27,
        'theme' => 'ম্যাজিক দরজা',
        'emoji' => '🔮',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমাকে ভাবলেই বাস্তবটাও একটু ম্যাজিকের মতো ঝলমল করে।',
        'interaction' => 'stars',
        'reveal' => 'spin',
        'shape' => 'soft',
        'palette' => [
            '#140f2e',
            '#7c3aed',
            '#67e8f9',
            '#faf5ff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
