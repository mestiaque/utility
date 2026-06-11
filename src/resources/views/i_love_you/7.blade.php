@php
    $lovePage = [
        'day' => 7,
        'theme' => 'গাছ থেকে ভালোবাসা',
        'emoji' => '🌳',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'আমাদের ভালোবাসা গাছের মতো, যত্ন পেলে আরও গভীর শেকড় গড়ে।',
        'interaction' => 'tree',
        'reveal' => 'rise',
        'shape' => 'soft',
        'palette' => [
            '#10251a',
            '#35c06e',
            '#ffd86b',
            '#f4fff1',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
