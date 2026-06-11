@php
    $lovePage = [
        'day' => 16,
        'theme' => 'সূর্যমুখী হাসি',
        'emoji' => '🌻',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমার হাসি দেখলেই সূর্যমুখীর মতো আমার মন আলো খুঁজে পায়।',
        'interaction' => 'rose',
        'reveal' => 'zoom',
        'shape' => 'stamp',
        'palette' => [
            '#1f2611',
            '#f9b233',
            '#fff176',
            '#fffbea',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
