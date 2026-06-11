@php
    $lovePage = [
        'day' => 11,
        'theme' => 'মিউজিক হার্ট',
        'emoji' => '🎵',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমার নামটাই আমার প্রিয় গান, বারবার শুনলেও পুরোনো লাগে না।',
        'interaction' => 'music',
        'reveal' => 'spin',
        'shape' => 'stamp',
        'palette' => [
            '#151027',
            '#00d1ff',
            '#ff6ad5',
            '#f9f6ff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
