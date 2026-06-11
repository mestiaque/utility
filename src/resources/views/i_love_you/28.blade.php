@php
    $lovePage = [
        'day' => 28,
        'theme' => 'চাঁদ-সূর্য তুমি',
        'emoji' => '🌞',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'দিনে সূর্যের মতো, রাতে চাঁদের মতো তুমি আমার সব আলো।',
        'interaction' => 'heart',
        'reveal' => 'zoom',
        'shape' => 'circle',
        'palette' => [
            '#271a0c',
            '#f97316',
            '#fde047',
            '#fff7df',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
