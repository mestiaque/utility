<?php

return [
    [
        'title' => 'Package Generator',
        'icon'  => 'fas fa-box',
        'route' => 'ut.package-generator',
        'for_active' => 'ut.package-generator',
        'icon_color' => 'icc-1',
        'permit' => 'me.dashboard',
        'sl' => 10
    ],

    [
        'title' => 'Feature Generator',
        'icon'  => 'fas fa-cogs',
        'route' => 'ut.laravelFeatureGenerate',
        'for_active' => 'ut.laravelFeatureGenerate',
        'icon_color' => 'icc-2',
        'permit' => 'me.dashboard',
        'sl' => 11
    ],

    // [
    //     'title' => 'Wedding Card Generator',
    //     'icon'  => 'fas fa-heart',
    //     'route' => 'ut.wedding-card',
    //     'for_active' => 'ut.wedding-card',
    //     'icon_color' => 'icc-3',
    //     'permit' => 'me.dashboard',
    //     'sl' => 12
    // ],
    [
        'title' => 'EM Visualizer',
        'icon'  => 'fas fa-music',
        'route' => 'ut.em-visualizer',
        'for_active' => 'ut.em-visualizer',
        'icon_color' => 'icc-8',
        // 'permit' => 'me.dashboard',
        'sl' => 13
    ],
    [
        'title' => 'Bajar List',
        'icon'  => 'fas fa-list',
        'route' => 'ut.bajar-list.groups.index',
        'for_active' => 'ut.bajar-list.*',
        'icon_color' => 'icc-9',
        'permit' => 'me.dashboard',
        'sl' => 14
    ],

    [
        'title'      => 'Data Receiver',
        'icon'       => 'fas fa-satellite-dish',
        'route'      => 'ut.data-receiver',
        'for_active' => 'ut.data-receiver*',
        'icon_color' => 'icc-5',
        'permit'     => 'me.dashboard',
        'sl'         => 15
    ],

    [
        'title'      => 'Invoice Generator',
        'icon'       => 'fas fa-file-invoice',
        'route'      => 'ut.invoice-generator.index',
        'for_active' => 'ut.invoice-generator.*',
        'icon_color' => 'icc-4',
        'permit'     => 'me.dashboard',
        'sl'         => 17
    ],

    [
        'title'      => 'Image Share',
        'icon'       => 'fas fa-images',
        'route'      => 'ut.image-share.index',
        'for_active' => 'ut.image-share.*',
        'icon_color' => 'icc-7',
        'permit'     => 'me.dashboard',
        'sl'         => 16
    ],

];



