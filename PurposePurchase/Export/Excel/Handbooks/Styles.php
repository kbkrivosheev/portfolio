<?php

declare(strict_types=1);

namespace app\Features\PurposePurchase\Export\Excel\Handbooks;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Styles
{
    public const FONT_NAME = 'Times New Roman';
    public const FONT_SIZE = 10;

    public const   ALIGNMENT_CENTER = [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ];

    public const TITLE_STYLE = [
        'font' => [
            'bold' => true,
            'size' => 30,
            'name' => self::FONT_NAME,
            'color' => [
                'argb' => 'FFFFFF'
            ]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => '808080'
            ]
        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];
    public const STYLE_ROW_1 = [
        'font' => [
            'bold' => true,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,
            'color' => [
                'argb' => 'FFFFFF'
            ]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => '808080'
            ]
        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];

    public const STYLE_ROW_2_BROWN = [
        'font' => [
            'bold' => true,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,
            'color' => [
                'argb' => 'FFFFFF'
            ]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => 'c55a11'
            ]
        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];

    public const STYLE_ROW_2_DEEP_BLUE = [
        'font' => [
            'bold' => true,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,
            'color' => [
                'argb' => 'FFFFFF'
            ]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => '1f4e79'
            ]
        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];

    public const STYLE_ROW_2_GREEN = [
        'font' => [
            'bold' => true,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,
            'color' => [
                'argb' => 'FFFFFF'
            ]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => '548235'
            ]
        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];


    public const STYLE_ROW_2_BLUE = [
        'font' => [
            'bold' => true,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,
            'color' => [
                'argb' => 'FFFFFF'
            ]
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => '2e75b6'
            ]
        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];

    public const STYLE_ROW_3 = [
        'font' => [
            'bold' => true,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'argb' => 'd0cece'
            ]
        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];

    public const STYLE_ROW_4 = [
        'font' => [
            'bold' => true,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,

        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];

    public const STYLE_ROW_5 = [
        'font' => [
            'bold' => false,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,

        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_TOP,
            'wrapText' => true,
        ]
    ];


    public const STYLE_ROW_6 = [
        'font' => [
            'bold' => true,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,
        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];
    public const YELLOW_FILL = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'fff2cc',
            ],
        ],
    ];
    public const YELLOW_HIGHLIGHT = [
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => [
                'rgb' => 'ffff00',
            ],
        ],
    ];

    public const  BORDER_BOTTOM = [
        'borders' => [
            'bottom' => [
                'borderStyle' => Border::BORDER_THICK,
                'borderSize' => 2,
            ],
        ],
    ];
    public const STYLE_ROW_7 = [
        'font' => [
            'bold' => false,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,
        ],
        'alignment' => self::ALIGNMENT_CENTER
    ];

    public const TITLE_NOTICE_STYLE = [
        'font' => [
            'bold' => true,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,

        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ]
    ];


    public const TEXT_STYLE = [
        'font' => [
            'bold' => false,
            'size' => self::FONT_SIZE,
            'name' => self::FONT_NAME,
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_TOP,
            'wrapText' => true,
        ]
    ];

}