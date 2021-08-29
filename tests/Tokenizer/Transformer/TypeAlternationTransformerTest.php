<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Tokenizer\Transformer;

use PhpCsFixer\Tests\Test\AbstractTransformerTestCase;
use PhpCsFixer\Tokenizer\CT;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Tokenizer\Transformer\TypeAlternationTransformer
 */
final class TypeAlternationTransformerTest extends AbstractTransformerTestCase
{
    /**
     * @dataProvider provideProcessCases
     * @requires PHP 7.1
     */
    public function testProcess(string $source, array $expectedTokens = []): void
    {
        $this->doTest(
            $source,
            $expectedTokens,
            [
                CT::T_TYPE_ALTERNATION,
            ]
        );
    }

    public function provideProcessCases()
    {
        return [
            'no namespace' => [
                '<?php try {} catch (ExceptionType1 | ExceptionType2 | ExceptionType3 $e) {}',
                [
                    11 => CT::T_TYPE_ALTERNATION,
                    15 => CT::T_TYPE_ALTERNATION,
                ],
            ],
            'comments & spacing' => [
                "<?php try {/* 1 */} catch (/* 2 */ExceptionType1/* 3 */\t\n|  \n\t/* 4 */\n\tExceptionType2 \$e) {}",
                [
                    14 => CT::T_TYPE_ALTERNATION,
                ],
            ],
            'native namespace only' => [
                '<?php try {} catch (\ExceptionType1 | \ExceptionType2 | \ExceptionType3 $e) {}',
                [
                    12 => CT::T_TYPE_ALTERNATION,
                    17 => CT::T_TYPE_ALTERNATION,
                ],
            ],
            'namespaces' => [
                '<?php try {} catch (A\ExceptionType1 | \A\ExceptionType2 | \A\B\C\ExceptionType3 $e) {}',
                [
                    13 => CT::T_TYPE_ALTERNATION,
                    20 => CT::T_TYPE_ALTERNATION,
                ],
            ],
            'do not fix cases' => [
                '<?php
                    echo 2 | 4;
                    echo "aaa" | "bbb";
                    echo F_OK | F_ERR;
                    echo foo(F_OK | F_ERR);
                    foo($A||$b);
                    foo($A|$b);
                    // try {} catch (ExceptionType1 | ExceptionType2) {}
                    $a = function(){};
                    $x = ($y|$z);
                    function foo(){}
                    $a = $b|$c;
                ',
            ],
        ];
    }

    /**
     * @dataProvider provideProcess80Cases
     * @requires PHP 8.0
     */
    public function testProcess80(string $source, array $expectedTokens): void
    {
        $this->doTest($source, $expectedTokens);
    }

    public function provideProcess80Cases()
    {
        yield 'arrow function' => [
            '<?php $a = fn(int|null $item): int|null => $item * 2;',
            [
                8 => CT::T_TYPE_ALTERNATION,
                16 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'static function' => ['<?php
$a = static function (A|B|int $a):int|null {};
',
            [
                11 => CT::T_TYPE_ALTERNATION,
                13 => CT::T_TYPE_ALTERNATION,
                20 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'static function with use' => ['<?php
$a = static function () use ($a) : int|null {};
',
            [
                21 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'function variable unions' => ['<?php
function Bar1(A|B|int $a) {
}
',
            [
                6 => CT::T_TYPE_ALTERNATION,
                8 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'class method variable unions' => ['<?php
class Foo
{
    public function Bar1(A|B|int $a) {}
    public function Bar2(A\B|\A\Z $a) {}
    public function Bar3(int $a) {}
}
',
            [
                // Bar1
                14 => CT::T_TYPE_ALTERNATION,
                16 => CT::T_TYPE_ALTERNATION,
                // Bar2
                34 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'class method return unions' => ['<?php
class Foo
{
    public function Bar(): A|B|int {}
}
',
            [
                17 => CT::T_TYPE_ALTERNATION,
                19 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'class attribute var + union' => ['<?php
class Number
{
    var int|float|null $number;
}
',
            [
                10 => CT::T_TYPE_ALTERNATION,
                12 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'class attributes visibility + unions' => ['<?php
class Number
{
    public array $numbers;

    /**  */
    public int|float $number1;

    // 2
    protected int|float|null $number2;

    # - foo 3
    private int|float|string|null $number3;

    /* foo 4 */
    private \Foo\Bar\A|null $number4;

    private \Foo|Bar $number5;

    private ?Bar $number6; // ? cannot be part of an union in PHP8
}
',
            [
                // number 1
                19 => CT::T_TYPE_ALTERNATION,
                // number 2
                30 => CT::T_TYPE_ALTERNATION,
                32 => CT::T_TYPE_ALTERNATION,
                // number 3
                43 => CT::T_TYPE_ALTERNATION,
                45 => CT::T_TYPE_ALTERNATION,
                47 => CT::T_TYPE_ALTERNATION,
                // number 4
                63 => CT::T_TYPE_ALTERNATION,
                // number 5
                73 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'typed static properties' => [
            '<?php
            class Foo {
                private static int | null $bar;

                private static int | float | string | null $baz;
            }',
            [
                14 => CT::T_TYPE_ALTERNATION,
                27 => CT::T_TYPE_ALTERNATION,
                31 => CT::T_TYPE_ALTERNATION,
                35 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'array as first element of types' => [
            '<?php function foo(array|bool|null $foo) {}',
            [
                6 => CT::T_TYPE_ALTERNATION,
                8 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'array as middle element of types' => [
            '<?php function foo(null|array|bool $foo) {}',
            [
                6 => CT::T_TYPE_ALTERNATION,
                8 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'array as last element of types' => [
            '<?php function foo(null|bool|array $foo) {}',
            [
                6 => CT::T_TYPE_ALTERNATION,
                8 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'multiple function parameters' => [
            '<?php function foo(A|B $x, C|D $y, E|F $z) {};',
            [
                6 => CT::T_TYPE_ALTERNATION,
                13 => CT::T_TYPE_ALTERNATION,
                20 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'function calls and function definitions' => [
            '<?php
                f1(CONST_A|CONST_B);
                function f2(A|B $x, C|D $y, E|F $z) {};
                f3(CONST_A|CONST_B);
                function f4(A|B $x, C|D $y, E|F $z) {};
                f5(CONST_A|CONST_B);
                $x = ($y|$z);
            ',
            [
                15 => CT::T_TYPE_ALTERNATION,
                22 => CT::T_TYPE_ALTERNATION,
                29 => CT::T_TYPE_ALTERNATION,
                52 => CT::T_TYPE_ALTERNATION,
                59 => CT::T_TYPE_ALTERNATION,
                66 => CT::T_TYPE_ALTERNATION,
            ],
        ];

        yield 'callable type' => [
            '<?php
                function f1(array|callable $x) {};
                function f2(callable|array $x) {};
                function f3(string|callable $x) {};
                function f4(callable|string $x) {};
            ',
            [
                7 => CT::T_TYPE_ALTERNATION,
                22 => CT::T_TYPE_ALTERNATION,
                37 => CT::T_TYPE_ALTERNATION,
                52 => CT::T_TYPE_ALTERNATION,
            ],
        ];
    }
}
