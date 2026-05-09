<?php

namespace Tests\Unit;

use App\Models\Question;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BulkQuestionPayloadNormalizationTest extends TestCase
{
    #[DataProvider('singleOptionCases')]
    public function test_normalize_payload_single_option_cases(
        mixed $rawText,
        mixed $rawCorrect,
        string $expectedText,
        bool $expectedCorrect
    ): void {
        $payload = [
            'options' => [
                [
                    'option_text' => $rawText,
                    'is_correct' => $rawCorrect,
                ],
            ],
        ];

        $normalized = Question::normalizeQuestionPayload($payload);

        $this->assertSame($expectedText, $normalized['options'][0]['option_text']);
        $this->assertSame($expectedCorrect, $normalized['options'][0]['is_correct']);
        $this->assertSame(0, $normalized['options'][0]['order_index']);
    }

    #[DataProvider('multiOptionCases')]
    public function test_normalize_payload_multi_option_cases(
        array $rawOptions,
        array $expectedTexts,
        array $expectedCorrects
    ): void {
        $payload = ['options' => $rawOptions];

        $normalized = Question::normalizeQuestionPayload($payload);

        $this->assertCount(count($expectedTexts), $normalized['options']);

        foreach ($normalized['options'] as $index => $option) {
            $this->assertSame($expectedTexts[$index], $option['option_text']);
            $this->assertSame($expectedCorrects[$index], $option['is_correct']);
            $this->assertSame($index, $option['order_index']);
        }
    }

    public static function singleOptionCases(): array
    {
        return [
            's01' => ['A', true, 'A', true],
            's02' => [' A ', true, 'A', true],
            's03' => ['B', false, 'B', false],
            's04' => [' B ', false, 'B', false],
            's05' => ['C', 1, 'C', true],
            's06' => [' C ', 1, 'C', true],
            's07' => ['D', 0, 'D', false],
            's08' => [' D ', 0, 'D', false],
            's09' => ['E', '1', 'E', true],
            's10' => [' E ', '1', 'E', true],
            's11' => ['F', '0', 'F', false],
            's12' => [' F ', '0', 'F', false],
            's13' => ['G', 'true', 'G', true],
            's14' => [' G ', 'true', 'G', true],
            's15' => ['H', '', 'H', false],
            's16' => [' H ', '', 'H', false],
            's17' => ['I', null, 'I', false],
            's18' => [' I ', null, 'I', false],
            's19' => ["\tJ\t", true, 'J', true],
            's20' => ["\nK\n", false, 'K', false],
            's21' => ['L', 'yes', 'L', true],
            's22' => [' L ', 'yes', 'L', true],
            's23' => ['M', 'no', 'M', true],
            's24' => [' M ', 'no', 'M', true],
            's25' => ['N', [], 'N', false],
            's26' => [' N ', [], 'N', false],
            's27' => ['O', [1], 'O', true],
            's28' => [' O ', [1], 'O', true],
            's29' => ['P', new \stdClass(), 'P', true],
            's30' => [' P ', new \stdClass(), 'P', true],
            's31' => ['', true, '', true],
            's32' => ['   ', true, '', true],
            's33' => ['', false, '', false],
            's34' => ['   ', false, '', false],
            's35' => ['Q', 'false', 'Q', true],
            's36' => [' Q ', 'false', 'Q', true],
            's37' => ['R', 99, 'R', true],
            's38' => [' R ', -1, 'R', true],
            's39' => ['S', 0.0, 'S', false],
            's40' => [' S ', 0.1, 'S', true],
            's41' => ['T', 'on', 'T', true],
            's42' => [' T ', 'off', 'T', true],
            's43' => ['U', ' ', 'U', true],
            's44' => [' U ', '   ', 'U', true],
            's45' => ['V', "\t", 'V', true],
            's46' => [' V ', "\n", 'V', true],
            's47' => ['W', 'n', 'W', true],
            's48' => [' W ', 'y', 'W', true],
            's49' => ['X', 'anything', 'X', true],
            's50' => [' Y ', false, 'Y', false],
        ];
    }

    public static function multiOptionCases(): array
    {
        return [
            'm01' => [
                [['option_text' => ' A ', 'is_correct' => true], ['option_text' => ' B ', 'is_correct' => false]],
                ['A', 'B'],
                [true, false],
            ],
            'm02' => [
                [['option_text' => ' C ', 'is_correct' => 1], ['option_text' => ' D ', 'is_correct' => 0]],
                ['C', 'D'],
                [true, false],
            ],
            'm03' => [
                [['option_text' => "\tE\t", 'is_correct' => '1'], ['option_text' => "\nF\n", 'is_correct' => '0']],
                ['E', 'F'],
                [true, false],
            ],
            'm04' => [
                [['option_text' => 'G', 'is_correct' => null], ['option_text' => 'H', 'is_correct' => 'yes']],
                ['G', 'H'],
                [false, true],
            ],
            'm05' => [
                [['option_text' => ' I ', 'is_correct' => false], ['option_text' => ' J ', 'is_correct' => true], ['option_text' => ' K ', 'is_correct' => false]],
                ['I', 'J', 'K'],
                [false, true, false],
            ],
            'm06' => [
                [['option_text' => 'L', 'is_correct' => 'true'], ['option_text' => 'M', 'is_correct' => 'false']],
                ['L', 'M'],
                [true, true],
            ],
            'm07' => [
                [['option_text' => '', 'is_correct' => false], ['option_text' => ' N ', 'is_correct' => true]],
                ['', 'N'],
                [false, true],
            ],
            'm08' => [
                [['option_text' => ' O ', 'is_correct' => []], ['option_text' => ' P ', 'is_correct' => [1]]],
                ['O', 'P'],
                [false, true],
            ],
            'm09' => [
                [['option_text' => 'Q', 'is_correct' => 0.0], ['option_text' => 'R', 'is_correct' => 0.1]],
                ['Q', 'R'],
                [false, true],
            ],
            'm10' => [
                [['option_text' => ' S ', 'is_correct' => 'on'], ['option_text' => ' T ', 'is_correct' => 'off']],
                ['S', 'T'],
                [true, true],
            ],
            'm11' => [
                [['option_text' => 'U', 'is_correct' => false], ['option_text' => 'V', 'is_correct' => false], ['option_text' => 'W', 'is_correct' => false]],
                ['U', 'V', 'W'],
                [false, false, false],
            ],
            'm12' => [
                [['option_text' => 'X', 'is_correct' => true], ['option_text' => 'Y', 'is_correct' => true], ['option_text' => 'Z', 'is_correct' => true]],
                ['X', 'Y', 'Z'],
                [true, true, true],
            ],
            'm13' => [
                [['option_text' => 'A1', 'is_correct' => 1], ['option_text' => 'B1', 'is_correct' => '1'], ['option_text' => 'C1', 'is_correct' => true]],
                ['A1', 'B1', 'C1'],
                [true, true, true],
            ],
            'm14' => [
                [['option_text' => 'A2', 'is_correct' => 0], ['option_text' => 'B2', 'is_correct' => '0'], ['option_text' => 'C2', 'is_correct' => null]],
                ['A2', 'B2', 'C2'],
                [false, false, false],
            ],
            'm15' => [
                [['option_text' => '  AA  ', 'is_correct' => true], ['option_text' => '  BB  ', 'is_correct' => false]],
                ['AA', 'BB'],
                [true, false],
            ],
            'm16' => [
                [['option_text' => "\tCC\t", 'is_correct' => true], ['option_text' => "\nDD\n", 'is_correct' => false]],
                ['CC', 'DD'],
                [true, false],
            ],
            'm17' => [
                [['option_text' => 'EE', 'is_correct' => 'n'], ['option_text' => 'FF', 'is_correct' => 'y']],
                ['EE', 'FF'],
                [true, true],
            ],
            'm18' => [
                [['option_text' => 'GG', 'is_correct' => ' '], ['option_text' => 'HH', 'is_correct' => '   ']],
                ['GG', 'HH'],
                [true, true],
            ],
            'm19' => [
                [['option_text' => 'II', 'is_correct' => "\t"], ['option_text' => 'JJ', 'is_correct' => "\n"]],
                ['II', 'JJ'],
                [true, true],
            ],
            'm20' => [
                [['option_text' => 'KK', 'is_correct' => 'anything'], ['option_text' => 'LL', 'is_correct' => 'x']],
                ['KK', 'LL'],
                [true, true],
            ],
            'm21' => [
                [['option_text' => 'MM', 'is_correct' => -1], ['option_text' => 'NN', 'is_correct' => 99]],
                ['MM', 'NN'],
                [true, true],
            ],
            'm22' => [
                [['option_text' => 'OO', 'is_correct' => 0], ['option_text' => 'PP', 'is_correct' => 1]],
                ['OO', 'PP'],
                [false, true],
            ],
            'm23' => [
                [['option_text' => 'QQ', 'is_correct' => 0.0], ['option_text' => 'RR', 'is_correct' => 1.0]],
                ['QQ', 'RR'],
                [false, true],
            ],
            'm24' => [
                [['option_text' => 'SS', 'is_correct' => []], ['option_text' => 'TT', 'is_correct' => [0]]],
                ['SS', 'TT'],
                [false, true],
            ],
            'm25' => [
                [['option_text' => 'UU', 'is_correct' => new \stdClass()], ['option_text' => 'VV', 'is_correct' => null]],
                ['UU', 'VV'],
                [true, false],
            ],
            'm26' => [
                [['option_text' => ' WW ', 'is_correct' => true], ['option_text' => ' XX ', 'is_correct' => false], ['option_text' => ' YY ', 'is_correct' => true], ['option_text' => ' ZZ ', 'is_correct' => false]],
                ['WW', 'XX', 'YY', 'ZZ'],
                [true, false, true, false],
            ],
            'm27' => [
                [['option_text' => 'A3', 'is_correct' => true], ['option_text' => 'B3', 'is_correct' => false], ['option_text' => 'C3', 'is_correct' => true], ['option_text' => 'D3', 'is_correct' => false]],
                ['A3', 'B3', 'C3', 'D3'],
                [true, false, true, false],
            ],
            'm28' => [
                [['option_text' => 'E3', 'is_correct' => false], ['option_text' => 'F3', 'is_correct' => true], ['option_text' => 'G3', 'is_correct' => false], ['option_text' => 'H3', 'is_correct' => true]],
                ['E3', 'F3', 'G3', 'H3'],
                [false, true, false, true],
            ],
            'm29' => [
                [['option_text' => 'I3', 'is_correct' => true], ['option_text' => 'J3', 'is_correct' => true], ['option_text' => 'K3', 'is_correct' => false], ['option_text' => 'L3', 'is_correct' => false]],
                ['I3', 'J3', 'K3', 'L3'],
                [true, true, false, false],
            ],
            'm30' => [
                [['option_text' => 'M3', 'is_correct' => false], ['option_text' => 'N3', 'is_correct' => false], ['option_text' => 'O3', 'is_correct' => true], ['option_text' => 'P3', 'is_correct' => true]],
                ['M3', 'N3', 'O3', 'P3'],
                [false, false, true, true],
            ],
            'm31' => [
                [['option_text' => 'Q3', 'is_correct' => true], ['option_text' => 'R3', 'is_correct' => false], ['option_text' => 'S3', 'is_correct' => false], ['option_text' => 'T3', 'is_correct' => false]],
                ['Q3', 'R3', 'S3', 'T3'],
                [true, false, false, false],
            ],
            'm32' => [
                [['option_text' => 'U3', 'is_correct' => false], ['option_text' => 'V3', 'is_correct' => true], ['option_text' => 'W3', 'is_correct' => false], ['option_text' => 'X3', 'is_correct' => false]],
                ['U3', 'V3', 'W3', 'X3'],
                [false, true, false, false],
            ],
            'm33' => [
                [['option_text' => 'Y3', 'is_correct' => false], ['option_text' => 'Z3', 'is_correct' => false], ['option_text' => 'A4', 'is_correct' => true], ['option_text' => 'B4', 'is_correct' => false]],
                ['Y3', 'Z3', 'A4', 'B4'],
                [false, false, true, false],
            ],
            'm34' => [
                [['option_text' => 'C4', 'is_correct' => false], ['option_text' => 'D4', 'is_correct' => false], ['option_text' => 'E4', 'is_correct' => false], ['option_text' => 'F4', 'is_correct' => true]],
                ['C4', 'D4', 'E4', 'F4'],
                [false, false, false, true],
            ],
            'm35' => [
                [['option_text' => 'G4', 'is_correct' => true], ['option_text' => 'H4', 'is_correct' => true], ['option_text' => 'I4', 'is_correct' => true], ['option_text' => 'J4', 'is_correct' => false]],
                ['G4', 'H4', 'I4', 'J4'],
                [true, true, true, false],
            ],
            'm36' => [
                [['option_text' => 'K4', 'is_correct' => false], ['option_text' => 'L4', 'is_correct' => true], ['option_text' => 'M4', 'is_correct' => true], ['option_text' => 'N4', 'is_correct' => true]],
                ['K4', 'L4', 'M4', 'N4'],
                [false, true, true, true],
            ],
            'm37' => [
                [['option_text' => 'O4', 'is_correct' => true], ['option_text' => 'P4', 'is_correct' => false], ['option_text' => 'Q4', 'is_correct' => true], ['option_text' => 'R4', 'is_correct' => false], ['option_text' => 'S4', 'is_correct' => true]],
                ['O4', 'P4', 'Q4', 'R4', 'S4'],
                [true, false, true, false, true],
            ],
            'm38' => [
                [['option_text' => 'T4', 'is_correct' => false], ['option_text' => 'U4', 'is_correct' => true], ['option_text' => 'V4', 'is_correct' => false], ['option_text' => 'W4', 'is_correct' => true], ['option_text' => 'X4', 'is_correct' => false]],
                ['T4', 'U4', 'V4', 'W4', 'X4'],
                [false, true, false, true, false],
            ],
            'm39' => [
                [['option_text' => 'Y4', 'is_correct' => true], ['option_text' => 'Z4', 'is_correct' => true], ['option_text' => 'A5', 'is_correct' => false], ['option_text' => 'B5', 'is_correct' => false], ['option_text' => 'C5', 'is_correct' => false]],
                ['Y4', 'Z4', 'A5', 'B5', 'C5'],
                [true, true, false, false, false],
            ],
            'm40' => [
                [['option_text' => 'D5', 'is_correct' => false], ['option_text' => 'E5', 'is_correct' => false], ['option_text' => 'F5', 'is_correct' => true], ['option_text' => 'G5', 'is_correct' => true], ['option_text' => 'H5', 'is_correct' => true]],
                ['D5', 'E5', 'F5', 'G5', 'H5'],
                [false, false, true, true, true],
            ],
            'm41' => [
                [['option_text' => '  I5 ', 'is_correct' => true], ['option_text' => '  J5 ', 'is_correct' => false], ['option_text' => '  K5 ', 'is_correct' => true]],
                ['I5', 'J5', 'K5'],
                [true, false, true],
            ],
            'm42' => [
                [['option_text' => "\tL5\t", 'is_correct' => true], ['option_text' => "\nM5\n", 'is_correct' => false], ['option_text' => "\tN5\t", 'is_correct' => true]],
                ['L5', 'M5', 'N5'],
                [true, false, true],
            ],
            'm43' => [
                [['option_text' => 'O5', 'is_correct' => '1'], ['option_text' => 'P5', 'is_correct' => '0'], ['option_text' => 'Q5', 'is_correct' => '1']],
                ['O5', 'P5', 'Q5'],
                [true, false, true],
            ],
            'm44' => [
                [['option_text' => 'R5', 'is_correct' => 1], ['option_text' => 'S5', 'is_correct' => 0], ['option_text' => 'T5', 'is_correct' => 1]],
                ['R5', 'S5', 'T5'],
                [true, false, true],
            ],
            'm45' => [
                [['option_text' => 'U5', 'is_correct' => null], ['option_text' => 'V5', 'is_correct' => null], ['option_text' => 'W5', 'is_correct' => null]],
                ['U5', 'V5', 'W5'],
                [false, false, false],
            ],
            'm46' => [
                [['option_text' => 'X5', 'is_correct' => []], ['option_text' => 'Y5', 'is_correct' => []], ['option_text' => 'Z5', 'is_correct' => []]],
                ['X5', 'Y5', 'Z5'],
                [false, false, false],
            ],
            'm47' => [
                [['option_text' => 'A6', 'is_correct' => [1]], ['option_text' => 'B6', 'is_correct' => [1]], ['option_text' => 'C6', 'is_correct' => [1]]],
                ['A6', 'B6', 'C6'],
                [true, true, true],
            ],
            'm48' => [
                [['option_text' => 'D6', 'is_correct' => 'true'], ['option_text' => 'E6', 'is_correct' => 'false'], ['option_text' => 'F6', 'is_correct' => 'true']],
                ['D6', 'E6', 'F6'],
                [true, true, true],
            ],
            'm49' => [
                [['option_text' => '', 'is_correct' => false], ['option_text' => '   ', 'is_correct' => true], ['option_text' => "\t", 'is_correct' => false]],
                ['', '', ''],
                [false, true, false],
            ],
            'm50' => [
                [['option_text' => 'G6', 'is_correct' => true], ['option_text' => 'H6', 'is_correct' => false], ['option_text' => 'I6', 'is_correct' => true], ['option_text' => 'J6', 'is_correct' => false], ['option_text' => 'K6', 'is_correct' => true], ['option_text' => 'L6', 'is_correct' => false]],
                ['G6', 'H6', 'I6', 'J6', 'K6', 'L6'],
                [true, false, true, false, true, false],
            ],
        ];
    }
}

