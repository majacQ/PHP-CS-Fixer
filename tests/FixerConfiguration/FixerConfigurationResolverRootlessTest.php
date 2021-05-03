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

namespace PhpCsFixer\Tests\FixerConfiguration;

use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverRootless;
use PhpCsFixer\FixerConfiguration\FixerOption;
use PhpCsFixer\Tests\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixer\FixerConfiguration\FixerConfigurationResolverRootless
 */
final class FixerConfigurationResolverRootlessTest extends TestCase
{
    public function testMapRootConfigurationTo()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "bar" option is not defined.');

        new FixerConfigurationResolverRootless('bar', [
            new FixerOption('foo', 'Bar.'),
        ], 'bar');
    }

    /**
     * @group legacy
     */
    public function testResolveWithMappedRoot()
    {
        $this->expectDeprecation('Passing "foo" at the root of the configuration for rule "bar" is deprecated and will not be supported in 3.0, use "foo" => array(...) option instead.');
        $options = [new FixerOption('foo', 'Bar.')];
        $configuration = new FixerConfigurationResolverRootless('foo', $options, 'bar');

        static::assertSame($options, $configuration->getOptions());

        $configuration->resolve(['baz' => 'qux']);
    }
}
