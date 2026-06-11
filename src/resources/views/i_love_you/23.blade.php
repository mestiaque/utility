@php
    $lovePage = [
        'day' => 23,
        'theme' => 'চুমুর ডাক',
        'emoji' => '💋',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'একটা চুমুর ডাক পাঠালাম, তুমি হাসলে সেটাই আমার উত্তর।',
        'interaction' => 'panda',
        'reveal' => 'zoom',
        'shape' => 'soft',
        'palette' => [
            '#320d18',
            '#ff3366',
            '#ffb3c1',
            '#fff0f3',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
