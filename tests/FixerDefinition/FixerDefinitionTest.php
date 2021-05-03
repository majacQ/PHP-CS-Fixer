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

namespace PhpCsFixer\Tests\FixerDefinition;

use PhpCsFixer\FixerDefinition\CodeSampleInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tests\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixer\FixerDefinition\FixerDefinition
 */
final class FixerDefinitionTest extends TestCase
{
    public function testGetSummary()
    {
        $definition = new FixerDefinition('Foo', []);

        static::assertSame('Foo', $definition->getSummary());
    }

    public function testGetCodeSamples()
    {
        $samples = [
            $this->prophesize(CodeSampleInterface::class)->reveal(),
            $this->prophesize(CodeSampleInterface::class)->reveal(),
        ];

        $definition = new FixerDefinition('', $samples);

        static::assertSame($samples, $definition->getCodeSamples());
    }

    public function testGetDescription()
    {
        $definition = new FixerDefinition('', []);

        static::assertNull($definition->getDescription());

        $definition = new FixerDefinition('', [], 'Foo');

        static::assertSame('Foo', $definition->getDescription());
    }

    /**
     * @group legacy
     */
    public function testGetConfigurationDescription()
    {
        $this->expectDeprecation('PhpCsFixer\\FixerDefinition\\FixerDefinition::getConfigurationDescription is deprecated and will be removed in 3.0.');
        $definition = new FixerDefinition('', []);

        static::assertNull($definition->getConfigurationDescription());

        $definition = new FixerDefinition('', [], null, 'Foo');

        static::assertNull($definition->getConfigurationDescription());

        $definition = new FixerDefinition('', [], null, 'Foo', []);

        static::assertSame('Foo', $definition->getConfigurationDescription());
    }

    /**
     * @group legacy
     */
    public function testGetDefaultConfiguration()
    {
        $this->expectDeprecation('PhpCsFixer\\FixerDefinition\\FixerDefinition::getDefaultConfiguration is deprecated and will be removed in 3.0.');
        $this->expectDeprecation('Argument #5 of FixerDefinition::__construct() is deprecated and will be removed in 3.0.');
        $definition = new FixerDefinition('', []);

        static::assertNull($definition->getDefaultConfiguration());

        $definition = new FixerDefinition('', [], null, null, ['Foo', 'Bar']);

        static::assertSame(['Foo', 'Bar'], $definition->getDefaultConfiguration());
    }

    public function testGetRiskyDescription()
    {
        $definition = new FixerDefinition('', []);

        static::assertNull($definition->getRiskyDescription());

        $definition = new FixerDefinition('', [], null, 'Foo');

        static::assertSame('Foo', $definition->getRiskyDescription());
    }

    /**
     * @group legacy
     */
    public function testLegacyGetRiskyDescription()
    {
        $this->expectDeprecation('Arguments #5 and #6 of FixerDefinition::__construct() are deprecated and will be removed in 3.0, use argument #4 instead.');
        $definition = new FixerDefinition('', [], null, null, null, 'Foo');

        static::assertSame('Foo', $definition->getRiskyDescription());
    }
}
