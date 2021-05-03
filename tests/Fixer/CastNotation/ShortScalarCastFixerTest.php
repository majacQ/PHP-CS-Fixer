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
 * @covers \PhpCsFixer\Fixer\CastNotation\ShortScalarCastFixer
 */
final class ShortScalarCastFixerTest extends AbstractFixerTestCase
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
        foreach (['boolean' => 'bool', 'integer' => 'int', 'double' => 'float', 'binary' => 'string'] as $from => $to) {
            foreach ($this->createCasesFor($from, $to) as $case) {
                yield $case;
            }
        }
    }

    public function provideFixDeprecatedCases()
    {
        return $this->createCasesFor('real', 'float');
    }

    /**
     * @param string $expected
     *
     * @dataProvider provideNoFixCases
     */
    public function testNoFix($expected)
    {
        $this->doTest($expected);
    }

    public function provideNoFixCases()
    {
        $cases = [];
        $types = ['string', 'array', 'object'];

        if (\PHP_VERSION_ID < 80000) {
            $types[] = 'unset';
        }

        foreach ($types as $cast) {
            $cases[] = [sprintf('<?php $b=(%s) $d;', $cast)];
            $cases[] = [sprintf('<?php $b=( %s ) $d;', $cast)];
            $cases[] = [sprintf('<?php $b=(%s ) $d;', ucfirst($cast))];
            $cases[] = [sprintf('<?php $b=(%s ) $d;', strtoupper($cast))];
        }

        return $cases;
    }

    private function createCasesFor($from, $to)
    {
        yield [
            sprintf('<?php echo ( %s  )$a;', $to),
            sprintf('<?php echo ( %s  )$a;', $from),
        ];
        yield [
            sprintf('<?php $b=(%s) $d;', $to),
            sprintf('<?php $b=(%s) $d;', $from),
        ];
        yield [
            sprintf('<?php $b= (%s)$d;', $to),
            sprintf('<?php $b= (%s)$d;', strtoupper($from)),
        ];
        yield [
            sprintf('<?php $b=( %s) $d;', $to),
            sprintf('<?php $b=( %s) $d;', ucfirst($from)),
        ];
        yield [
            sprintf('<?php $b=(%s ) $d;', $to),
            sprintf('<?php $b=(%s ) $d;', ucfirst($from)),
        ];
    }
}
