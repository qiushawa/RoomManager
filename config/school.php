<?php

return [
    // 全域 buildings 設定（Home/長借匯入共用）
    'buildings' => [
        [
            'code' => 'BGC',
            'match_prefixes' => ['BGC', 'GC'],
            'category' => 'B',
            'building' => 'GC',
            'label' => '綜三館',
            'home_name' => '綜三館 BGC',
        ],
        [
            'code' => 'BCB',
            'match_prefixes' => ['BCB', 'CB'],
            'category' => 'B',
            'building' => 'CB',
            'label' => '跨領域',
            'home_name' => '跨領域 BCB',
        ],
        [
            'code' => 'BRA',
            'match_prefixes' => ['BRA', 'RA'],
            'category' => 'B',
            'building' => 'RA',
            'label' => '科研大樓',
            'home_name' => '科研大樓 BRA',
        ],
        [
            'code' => 'AIA',
            'match_prefixes' => ['AIA', 'IA'],
            'category' => 'A',
            'building' => 'IA',
            'label' => '資訊大樓',
            'home_name' => '資訊大樓 AIA',
        ],
    ],
];
