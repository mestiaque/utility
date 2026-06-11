@php
    $lovePage = [
        'day' => 15,
        'theme' => 'মেঘের নৌকা',
        'emoji' => '☁️',
        'tap' => 'ট্যাপ করলে সারপ্রাইজ খুলবে',
        'message' => 'তোমাকে নিয়ে মেঘের নৌকায় দূরে কোথাও হারিয়ে যেতে ইচ্ছে করে।',
        'interaction' => 'heart',
        'reveal' => 'slide',
        'shape' => 'soft',
        'palette' => [
            '#172238',
            '#8ecaff',
            '#ffffff',
            '#f3f9ff',
        ],
    ];
@endphp

@include('utility::i_love_you._romantic_template', ['lovePage' => $lovePage])
