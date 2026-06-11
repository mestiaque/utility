@php
    $lovePage = [
        'day' => 3,
        'theme' => 'বৃষ্টির প্রেম',
        'emoji' => '☔',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'বৃষ্টি নামলেই মনে হয় আকাশও তোমার নাম ধরে নরম হয়ে যায়।',
        'interaction' => 'rain',
        'reveal' => 'slide',
        'shape' => 'ticket',
        'palette' => [
            '#0c2438',
            '#53c7ff',
            '#b7f7ff',
            '#f1fbff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
