<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tests\Fixer\CastNotation;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author SpacePossum
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\CastNotation\LowercaseCastFixer
 */
final class LowercaseCastFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     * @dataProvider provideFixDeprecatedCases
     * @requires PHP < 7.4
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     * @requires PHP 7.4
     */
    public function testFix74($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixDeprecatedCases
     * @requires PHP 7.4
     * @group legacy
     */
    public function testFix74Deprecated($expected, $input = null)
    {
        if (\PHP_VERSION_ID >= 80000) {
            static::markTestSkipped('PHP < 8.0 is required.');
        }

        $this->expectDeprecation('%AThe (real) cast is deprecated, use (float) instead');

        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        $types = ['boolean', 'bool', 'integer', 'int', 'double', 'float', 'float', 'string', 'array', 'object', 'binary'];

        if (\PHP_VERSION_ID < 80000) {
            $types[] = 'unset';
        }

        foreach ($types as $from) {
            foreach ($this->createCasesFor($from) as $case) {
                yield $case;
            }
        }
    }

    public function provideFixDeprecatedCases()
    {
        return $this->createCasesFor('real');
    }

    private function createCasesFor($type)
    {
        yield [
            sprintf('<?php $b= (%s)$d;', $type),
            sprintf('<?php $b= (%s)$d;', strtoupper($type)),
        ];

        yield [
            sprintf('<?php $b=( %s) $d;', $type),
            sprintf('<?php $b=( %s) $d;', ucfirst($type)),
        ];

        yield [
            sprintf('<?php $b=(%s ) $d;', $type),
            sprintf('<?php $b=(%s ) $d;', strtoupper($type)),
        ];

        yield [
            sprintf('<?php $b=(  %s  ) $d;', $type),
            sprintf('<?php $b=(  %s  ) $d;', ucfirst($type)),
        ];
    }
}
