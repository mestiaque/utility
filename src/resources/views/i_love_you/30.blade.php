@php
    $lovePage = [
        'day' => 30,
        'theme' => 'জবা রাঙা প্রতিশ্রুতি',
        'emoji' => '🌺',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'ত্রিশ দিনের শেষে বলি, ভালোবাসাটা এখানেই থামে না, এখান থেকেই আরও শুরু।',
        'interaction' => 'rose',
        'reveal' => 'soft',
        'shape' => 'soft',
        'palette' => [
            '#2a0712',
            '#e11d48',
            '#fb7185',
            '#fff1f2',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
