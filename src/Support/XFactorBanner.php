<?php

namespace ArielMejiaDev\XFactor\Support;

class XFactorBanner
{
    /** @var array<string, list<string>> */
    protected static array $letters = [
        'X' => [
            ' ██╗  ██╗',
            ' ╚██╗██╔╝',
            '  ╚███╔╝ ',
            '  ██╔██╗ ',
            ' ██╔╝ ██╗',
            ' ╚═╝  ╚═╝',
        ],
        '-' => [
            '          ',
            '          ',
            '  █████╗  ',
            '  ╚════╝  ',
            '          ',
            '          ',
        ],
        'F' => [
            '███████╗ ',
            '██╔════╝ ',
            '█████╗   ',
            '██╔══╝   ',
            '██║      ',
            '╚═╝      ',
        ],
        'A' => [
            ' █████╗  ',
            '██╔══██╗ ',
            '███████║ ',
            '██╔══██║ ',
            '██║  ██║ ',
            '╚═╝  ╚═╝ ',
        ],
        'C' => [
            ' ██████╗ ',
            '██╔════╝ ',
            '██║      ',
            '██║      ',
            '╚██████╗ ',
            ' ╚═════╝ ',
        ],
        'T' => [
            '████████╗',
            '╚══██╔══╝',
            '   ██║   ',
            '   ██║   ',
            '   ██║   ',
            '   ╚═╝   ',
        ],
        'O' => [
            ' ██████╗ ',
            '██╔═══██╗',
            '██║   ██║',
            '██║   ██║',
            '╚██████╔╝',
            ' ╚═════╝ ',
        ],
        'R' => [
            '██████╗ ',
            '██╔══██╗',
            '██████╔╝',
            '██╔══██╗',
            '██║  ██║',
            '╚═╝  ╚═╝',
        ],
    ];

    /** @var array<string, list<int>> */
    protected static array $gradients = [
        'aurora' => [51, 50, 49, 48, 47, 41],
        'sunset' => [214, 208, 202, 196, 160, 124],
        'ocean' => [81, 75, 69, 63, 57, 21],
        'ember' => [227, 221, 215, 209, 203, 197],
        'violet' => [213, 177, 141, 105, 69, 39],
        'cyberpunk' => [201, 165, 129, 93, 57, 21],
    ];

    /** @return list<string> */
    public static function render(?string $gradient = null): array
    {
        $colors = self::$gradients[$gradient ?? array_rand(self::$gradients)];
        $text = ['X', '-', 'F', 'A', 'C', 'T', 'O', 'R'];
        $output = [];

        for ($line = 0; $line < 6; $line++) {
            $row = '';
            foreach ($text as $char) {
                $row .= self::$letters[$char][$line].' ';
            }

            $color = $colors[$line];
            $output[] = "\e[38;5;{$color}m".rtrim($row)."\e[0m";
        }

        return $output;
    }
}
