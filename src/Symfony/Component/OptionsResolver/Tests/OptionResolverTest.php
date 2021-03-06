<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\OptionsResolver\Tests;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

class OptionsResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OptionsResolver
     */
    private $resolver;

    protected function setUp()
    {
        $this->resolver = new OptionsResolver();
    }

    public function testResolve()
    {
        $this->resolver->setDefaults(array(
            'one' => '1',
            'two' => '2',
        ));

        $options = array(
            'two' => '20',
        );

        $this->assertEquals(array(
            'one' => '1',
            'two' => '20',
        ), $this->resolver->resolve($options));
    }

    public function testResolveLazy()
    {
        $this->resolver->setDefaults(array(
            'one' => '1',
            'two' => function (Options $options) {
                return '20';
            },
        ));

        $this->assertEquals(array(
            'one' => '1',
            'two' => '20',
        ), $this->resolver->resolve(array()));
    }

    public function testResolveLazyDependencyOnOptional()
    {
        $this->resolver->setDefaults(array(
            'one' => '1',
            'two' => function (Options $options) {
                return $options['one'] . '2';
            },
        ));

        $options = array(
            'one' => '10',
        );

        $this->assertEquals(array(
            'one' => '10',
            'two' => '102',
        ), $this->resolver->resolve($options));
    }

    public function testResolveLazyDependencyOnMissingOptionalWithoutDefault()
    {
        $test = $this;

        $this->resolver->setOptional(array(
            'one',
        ));

        $this->resolver->setDefaults(array(
            'two' => function (Options $options) use ($test) {
                /* @var \PHPUnit_Framework_TestCase $test */
                $test->assertFalse(isset($options['one']));

                return '2';
            },
        ));

        $options = array(
        );

        $this->assertEquals(array(
            'two' => '2',
        ), $this->resolver->resolve($options));
    }

    public function testResolveLazyDependencyOnOptionalWithoutDefault()
    {
        $test = $this;

        $this->resolver->setOptional(array(
            'one',
        ));

        $this->resolver->setDefaults(array(
            'two' => function (Options $options) use ($test) {
                /* @var \PHPUnit_Framework_TestCase $test */
                $test->assertTrue(isset($options['one']));

                return $options['one'] . '2';
            },
        ));

        $options = array(
            'one' => '10',
        );

        $this->assertEquals(array(
            'one' => '10',
            'two' => '102',
        ), $this->resolver->resolve($options));
    }

    public function testResolveLazyDependencyOnRequired()
    {
        $this->resolver->setRequired(array(
            'one',
        ));
        $this->resolver->setDefaults(array(
            'two' => function (Options $options) {
                return $options['one'] . '2';
            },
        ));

        $options = array(
            'one' => '10',
        );

        $this->assertEquals(array(
            'one' => '10',
            'two' => '102',
        ), $this->resolver->resolve($options));
    }

    public function testResolveLazyReplaceDefaults()
    {
        $test = $this;

        $this->resolver->setDefaults(array(
            'one' => function (Options $options) use ($test) {
                /* @var \PHPUnit_Framework_TestCase $test */
                $test->fail('Previous closure should not be executed');
            },
        ));

        $this->resolver->replaceDefaults(array(
            'one' => function (Options $options, $previousValue) {
                return '1';
            },
        ));

        $this->assertEquals(array(
            'one' => '1',
        ), $this->resolver->resolve(array()));
    }

    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testResolveFailsIfNonExistingOption()
    {
        $this->resolver->setDefaults(array(
            'one' => '1',
        ));

        $this->resolver->setRequired(array(
            'two',
        ));

        $this->resolver->setOptional(array(
            'three',
        ));

        $this->resolver->resolve(array(
            'foo' => 'bar',
        ));
    }

    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     */
    public function testResolveFailsIfMissingRequiredOption()
    {
        $this->resolver->setRequired(array(
            'one',
        ));

        $this->resolver->setDefaults(array(
            'two' => '2',
        ));

        $this->resolver->resolve(array(
            'two' => '20',
        ));
    }

    public function testResolveSucceedsIfOptionValueAllowed()
    {
        $this->resolver->setDefaults(array(
            'one' => '1',
        ));

        $this->resolver->setAllowedValues(array(
            'one' => array('1', 'one'),
        ));

        $options = array(
            'one' => 'one',
        );

        $this->assertEquals(array(
            'one' => 'one',
        ), $this->resolver->resolve($options));
    }

    public function testResolveSucceedsIfOptionValueAllowed2()
    {
        $this->resolver->setDefaults(array(
            'one' => '1',
            'two' => '2',
        ));

        $this->resolver->addAllowedValues(array(
            'one' => array('1'),
            'two' => array('2'),
        ));
        $this->resolver->addAllowedValues(array(
            'one' => array('one'),
            'two' => array('two'),
        ));

        $options = array(
            'one' => '1',
            'two' => 'two',
        );

        $this->assertEquals(array(
            'one' => '1',
            'two' => 'two',
        ), $this->resolver->resolve($options));
    }

    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testResolveFailsIfOptionValueNotAllowed()
    {
        $this->resolver->setDefaults(array(
            'one' => '1',
        ));

        $this->resolver->setAllowedValues(array(
            'one' => array('1', 'one'),
        ));

        $this->resolver->resolve(array(
            'one' => '2',
        ));
    }

    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     */
    public function testSetRequiredFailsIfDefaultIsPassed()
    {
        $this->resolver->setRequired(array(
            'one' => '1',
        ));
    }

    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     */
    public function testSetOptionalFailsIfDefaultIsPassed()
    {
        $this->resolver->setOptional(array(
            'one' => '1',
        ));
    }

    public function testFluidInterface()
    {
        $this->resolver->setDefaults(array('one' => '1'))
            ->replaceDefaults(array('one' => '2'))
            ->setAllowedValues(array('one' => array('1', '2')))
            ->addAllowedValues(array('one' => array('3')))
            ->setRequired(array('two'))
            ->setOptional(array('three'));

        $options = array(
            'two' => '2',
        );

        $this->assertEquals(array(
            'one' => '2',
            'two' => '2',
        ), $this->resolver->resolve($options));
    }
}
