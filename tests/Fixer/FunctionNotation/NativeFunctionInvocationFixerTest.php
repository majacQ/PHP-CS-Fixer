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

namespace PhpCsFixer\Tests\Fixer\FunctionNotation;

use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author Andreas Möller <am@localheinz.com>
 * @author SpacePossum
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer
 */
final class NativeFunctionInvocationFixerTest extends AbstractFixerTestCase
{
    public function testConfigureRejectsUnknownConfigurationKey()
    {
        $key = 'foo';

        $this->expectException(\PhpCsFixer\ConfigurationException\InvalidConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            '[native_function_invocation] Invalid configuration: The option "%s" does not exist.',
            $key
        ));

        $this->fixer->configure([
            $key => 'bar',
        ]);
    }

    /**
     * @dataProvider provideInvalidConfigurationElementCases
     *
     * @param mixed $element
     */
    public function testConfigureRejectsInvalidConfigurationElement($element)
    {
        $this->expectException(\PhpCsFixer\ConfigurationException\InvalidConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Each element must be a non-empty, trimmed string, got "%s" instead.',
            \is_object($element) ? \get_class($element) : \gettype($element)
        ));

        $this->fixer->configure([
            'exclude' => [
                $element,
            ],
        ]);
    }

    /**
     * @return array
     */
    public function provideInvalidConfigurationElementCases()
    {
        return [
            'null' => [null],
            'false' => [false],
            'true' => [false],
            'int' => [1],
            'array' => [[]],
            'float' => [0.1],
            'object' => [new \stdClass()],
            'not-trimmed' => ['  json_encode  '],
        ];
    }

    /**
     * @param string[]    $include
     * @param null|string $expectedExceptionClass
     * @param null|string $expectedExceptionMessage
     *
     * @dataProvider provideConfigureIncludeSetsCases
     */
    public function testConfigureIncludeSets(
        array $include,
        $expectedExceptionClass = null,
        $expectedExceptionMessage = null
    ) {
        if (null !== $expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
            $this->expectExceptionMessageMatches(sprintf('#^%s$#', preg_quote($expectedExceptionMessage, '#')));
        }

        $this->fixer->configure(['include' => $include]);

        if (null === $expectedExceptionClass) {
            $this->addToAssertionCount(1);
        }
    }

    public function provideConfigureIncludeSetsCases()
    {
        return [
            [['foo', 'bar']],
            [['@all']],
            [['@all', 'bar']],
            [
                ['@xxx'],
                InvalidFixerConfigurationException::class,
                '[native_function_invocation] Invalid configuration: Unknown set "@xxx", known sets are "@all", "@internal", "@compiler_optimized".',
            ],
            [
                [' x '],
                InvalidFixerConfigurationException::class,
                '[native_function_invocation] Invalid configuration: Each element must be a non-empty, trimmed string, got "string" instead.',
            ],
        ];
    }

    public function testConfigureResetsExclude()
    {
        $this->fixer->configure([
            'exclude' => [
                'json_encode',
            ],
        ]);

        $before = <<<'PHP'
<?php

namespace WithClassNotPrefixed;

class Bar
{
    public function baz($foo)
    {
        if (isset($foo)) {
            json_encode($foo);
        }
    }
}
PHP;

        $after = <<<'PHP'
<?php

namespace WithClassNotPrefixed;

class Bar
{
    public function baz($foo)
    {
        if (isset($foo)) {
            \json_encode($foo);
        }
    }
}
PHP;

        $this->doTest($before);

        $this->fixer->configure([]);

        $this->doTest($after, $before);
    }

    /**
     * @dataProvider provideFixWithDefaultConfigurationCases
     *
     * @param string      $expected
     * @param null|string $input
     */
    public function testFixWithDefaultConfiguration($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return array
     */
    public function provideFixWithDefaultConfigurationCases()
    {
        return [
            [
                '<?php

\json_encode($foo);
',
            ],
            [
                '<?php

\json_encode($foo);
',
                '<?php

json_encode($foo);
',
            ],
            [
                '<?php

class Foo
{
    public function bar($foo)
    {
        return \json_encode($foo);
    }
}
',
            ],
            [
                '<?php

\json_encode($foo);
\strlen($foo);
',
                '<?php

json_encode($foo);
strlen($foo);
',
            ],
            [
                '<?php

class Foo
{
    public function bar($foo)
    {
        return \JSON_ENCODE($foo);
    }
}
',
                '<?php

class Foo
{
    public function bar($foo)
    {
        return JSON_ENCODE($foo);
    }
}
',
            ],
            'fix multiple calls in single code' => [
                '<?php

\json_encode($foo);
\strlen($foo);
\strlen($foo);
',
                '<?php

json_encode($foo);
strlen($foo);
strlen($foo);
',
            ],
        ];
    }

    /**
     * @dataProvider provideFixWithConfiguredExcludeCases
     *
     * @param string      $expected
     * @param null|string $input
     */
    public function testFixWithConfiguredExclude($expected, $input = null)
    {
        $this->fixer->configure([
            'exclude' => [
                'json_encode',
            ],
        ]);

        $this->doTest($expected, $input);
    }

    /**
     * @return array
     */
    public function provideFixWithConfiguredExcludeCases()
    {
        return [
            [
                '<?php

json_encode($foo);
',
            ],
            [
                '<?php

class Foo
{
    public function bar($foo)
    {
        return json_encode($foo);
    }
}
',
            ],
        ];
    }

    /**
     * @dataProvider provideFixWithNamespaceConfigurationCases
     *
     * @param string      $expected
     * @param null|string $input
     * @param string      $scope
     */
    public function testFixWithNamespaceConfiguration($expected, $input = null, $scope = 'namespaced')
    {
        $this->fixer->configure(['scope' => $scope]);
        $this->doTest($expected, $input);
    }

    public function provideFixWithNamespaceConfigurationCases()
    {
        return [
            [
                '<?php echo count([1]);',
            ],
            [
                '<?php
namespace space1 { ?>
<?php echo \count([2]) ?>
<?php }namespace {echo count([1]);}
',
                '<?php
namespace space1 { ?>
<?php echo count([2]) ?>
<?php }namespace {echo count([1]);}
',
            ],
            [
                '<?php
namespace Bar {
    echo \strtoLOWER("in 1");
}

namespace {
    echo strtolower("out 1");
}

namespace {
    echo strtolower("out 2");
}

namespace Bar{
    echo \strtolower("in 2");
}

namespace {
    echo strtolower("out 3");
}
',
                '<?php
namespace Bar {
    echo strtoLOWER("in 1");
}

namespace {
    echo strtolower("out 1");
}

namespace {
    echo strtolower("out 2");
}

namespace Bar{
    echo strtolower("in 2");
}

namespace {
    echo strtolower("out 3");
}
',
            ],
            [
                '<?php
namespace space11 ?>

    <?php
echo \strtolower(__NAMESPACE__);
namespace space2;
echo \strtolower(__NAMESPACE__);
',
                '<?php
namespace space11 ?>

    <?php
echo strtolower(__NAMESPACE__);
namespace space2;
echo strtolower(__NAMESPACE__);
',
            ],
            [
                '<?php namespace PhpCsFixer\Tests\Fixer\Casing;\count([1]);',
                '<?php namespace PhpCsFixer\Tests\Fixer\Casing;count([1]);',
            ],
            [
                '<?php
namespace Space12;

echo \count([1]);

namespace Space2;

echo \count([1]);
?>
',
                '<?php
namespace Space12;

echo count([1]);

namespace Space2;

echo count([1]);
?>
',
            ],
            [
                '<?php namespace {echo strtolower("out 2");}',
            ],
            [
                '<?php
namespace space13 {
    echo \strtolower("in 1");
}

namespace space2 {
    echo \strtolower("in 2");
}

namespace { // global
    echo strtolower("global 1");
}
',
                '<?php
namespace space13 {
    echo strtolower("in 1");
}

namespace space2 {
    echo strtolower("in 2");
}

namespace { // global
    echo strtolower("global 1");
}
',
            ],
            [
                '<?php
namespace space1 {
    echo \count([1]);
}
namespace {
    echo \count([1]);
}
',
                '<?php
namespace space1 {
    echo count([1]);
}
namespace {
    echo count([1]);
}
',
                'all',
            ],
        ];
    }

    /**
     * @dataProvider provideFixWithConfiguredIncludeCases
     *
     * @param string      $expected
     * @param null|string $input
     */
    public function testFixWithConfiguredInclude($expected, $input = null, array $configuration = [])
    {
        $this->fixer->configure($configuration);

        $this->doTest($expected, $input);
    }

    public function provideFixWithConfiguredIncludeCases()
    {
        $tests = [
            'include set + 1, exclude 1' => [
                '<?php
                    echo \count([1]);
                    \some_other($a, 3);
                    echo strlen($a);
                    not_me();
                ',
                '<?php
                    echo count([1]);
                    some_other($a, 3);
                    echo strlen($a);
                    not_me();
                ',
                [
                    'include' => ['@internal', 'some_other'],
                    'exclude' => ['strlen'],
                ],
            ],
            'include @all' => [
                '<?php
                    echo \count([1]);
                    \some_other($a, 3);
                    echo \strlen($a);
                    \me_as_well();
                ',
                '<?php
                    echo count([1]);
                    some_other($a, 3);
                    echo strlen($a);
                    me_as_well();
                ',
                [
                    'include' => ['@all'],
                ],
            ],
            'include @compiler_optimized' => [
                '<?php
                    // do not fix
                    $a = strrev($a);
                    $a .= str_repeat($a, 4);
                    $b = \already_prefixed_function();
                    // fix
                    $c = \get_class($d);
                    $e = \intval($f);
                ',
                '<?php
                    // do not fix
                    $a = strrev($a);
                    $a .= str_repeat($a, 4);
                    $b = \already_prefixed_function();
                    // fix
                    $c = get_class($d);
                    $e = intval($f);
                ',
                [
                    'include' => ['@compiler_optimized'],
                ],
            ],
            [
                '<?php class Foo {
                        public function & strlen($name) {
                        }
                    }
                ',
            ],
            'scope namespaced and strict enabled' => [
                '<?php
                    $a = not_compiler_optimized_function();
                    $b = intval($c);
                ',
                '<?php
                    $a = \not_compiler_optimized_function();
                    $b = \intval($c);
                ',
                [
                    'scope' => 'namespaced',
                    'strict' => true,
                ],
            ],
            [
                '<?php
                    use function foo\json_decode;
                    json_decode($base);
                ',
                null,
                [
                    'include' => ['@all'],
                ],
            ],
        ];

        foreach ($tests as $index => $test) {
            yield $index => $test;
        }

        if (\PHP_VERSION_ID < 80000) {
            yield 'include @compiler_optimized with strict enabled' => [
                '<?php
                        $a = not_compiler_optimized_function();
                        $b =  not_compiler_optimized_function();
                        $c = \intval($d);
                    ',
                '<?php
                        $a = \not_compiler_optimized_function();
                        $b = \ not_compiler_optimized_function();
                        $c = intval($d);
                    ',
                [
                    'include' => ['@compiler_optimized'],
                    'strict' => true,
                ],
            ];
        }
    }

    /**
     * @requires PHP 7.3
     */
    public function testFix73()
    {
        $this->doTest(
            '<?php $name = \get_class($foo, );',
            '<?php $name = get_class($foo, );'
        );
    }

    /**
     * @requires PHP <8.0
     */
    public function testFixPrePHP80()
    {
        $this->doTest(
            '<?php
echo \/**/strlen($a);
echo \ strlen($a);
echo \#
#
strlen($a);
echo \strlen($a);
',
            '<?php
echo \/**/strlen($a);
echo \ strlen($a);
echo \#
#
strlen($a);
echo strlen($a);
'
        );
    }

    /**
     * @requires PHP 8.0
     */
    public function testFixWithAttributesAndStrict()
    {
        $this->testFixWithConfiguredInclude(
            '<?php
#[\Attribute(\Attribute::TARGET_CLASS)]
class Foo {}
',
            null,
            ['strict' => true]
        );
    }

    /**
     * @requires PHP 8.0
     */
    public function testFixWithNullSafeObjectOperator()
    {
        $this->doTest('<?php $x?->count();');
    }
}
