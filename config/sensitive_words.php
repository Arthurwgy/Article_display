<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 敏感词列表（MVP）
    |--------------------------------------------------------------------------
    | level: light（替换***后进入pending）| moderate（驳回first_reject）| severe（驳回first_reject）
    |
    | 完整 CRUD 由 M3 Filament SensitiveWordResource 提供。
    | 此处仅用于 MVP 机审逻辑，不读取数据库。
    */
    'words' => [
        // light — 替换为 *** 后进入 pending
        'test' => 'light',
        'advertisement' => 'light',
        'promotion' => 'light',

        // moderate — 直接驳回
        'guilty' => 'moderate',

        // severe — 直接驳回
        'illegal' => 'severe',
    ],
];
