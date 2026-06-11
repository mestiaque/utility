@php
    $lovePage = [
        'day' => 21,
        'theme' => 'রাজকীয় মুকুট',
        'emoji' => '👑',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'আমার পৃথিবীর রানী তুমি, এই মুকুটটা তোমার নামেই মানায়।',
        'interaction' => 'heart',
        'reveal' => 'spin',
        'shape' => 'stamp',
        'palette' => [
            '#1e1530',
            '#d4af37',
            '#ffef9f',
            '#fff9df',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
