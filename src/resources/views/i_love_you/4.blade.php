@php
    $lovePage = [
        'day' => 4,
        'theme' => 'Yes না No',
        'emoji' => '💘',
        'tap' => 'No ধরতে পারো কিনা দেখি',
        'message' => 'আমি জানি উত্তরটা Yes, তবু তোমার মুখে শুনতে ইচ্ছে করে।',
        'interaction' => 'yesno',
        'reveal' => 'zoom',
        'shape' => 'soft',
        'palette' => [
            '#351122',
            '#ff4f8a',
            '#ffd1dc',
            '#fff5f8',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
