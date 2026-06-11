@php
    $lovePage = [
        'day' => 25,
        'theme' => 'হার্ট পাজল',
        'emoji' => '🧩',
        'tap' => 'No ধরতে পারো কিনা দেখি',
        'message' => 'আমার পাজলের সবচেয়ে দরকারি টুকরাটা তুমি।',
        'interaction' => 'yesno',
        'reveal' => 'soft',
        'shape' => 'stamp',
        'palette' => [
            '#21133a',
            '#c084fc',
            '#f0abfc',
            '#fbf5ff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
