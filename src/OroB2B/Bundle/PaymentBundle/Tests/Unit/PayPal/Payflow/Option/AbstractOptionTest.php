<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

abstract class AbstractOptionTest extends \PHPUnit_Framework_TestCase
{
    /** @var Option\AbstractOption */
    protected $option;

    /** @return Option\AbstractOption */
    abstract protected function getOption();

    protected function setUp()
    {
        $this->option = $this->getOption();
    }

    protected function tearDown()
    {
        unset($this->option);
    }

    /**
     * @param array $options
     * @param array $expectedResult
     * @param array $exceptionAndMessage
     * @dataProvider configureOptionDataProvider
     */
    public function testConfigureOption(
        array $options = [],
        array $expectedResult = [],
        array $exceptionAndMessage = []
    ) {
        if ($exceptionAndMessage) {
            list($exception, $message) = $exceptionAndMessage;
            $this->setExpectedException($exception, $message);
        }

        $resolver = new Option\OptionsResolver();
        $this->option->configureOption($resolver);
        $resolvedOptions = $resolver->resolve($options);

        if ($expectedResult) {
            $this->assertEquals($expectedResult, $resolvedOptions);
        }
    }

    /**
     * @return array
     */
    abstract public function configureOptionDataProvider();
}
