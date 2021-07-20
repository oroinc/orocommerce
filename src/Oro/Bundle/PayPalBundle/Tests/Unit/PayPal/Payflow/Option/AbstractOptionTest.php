<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Option;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

abstract class AbstractOptionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Option\AbstractOption[] */
    protected $options;

    /** @return Option\AbstractOption[] */
    abstract protected function getOptions();

    protected function setUp(): void
    {
        $this->options = $this->getOptions();
    }

    protected function tearDown(): void
    {
        unset($this->options);
    }

    /**
     * @dataProvider configureOptionDataProvider
     */
    public function testConfigureOption(
        array $options = [],
        array $expectedResult = [],
        array $exceptionAndMessage = []
    ) {
        if ($exceptionAndMessage) {
            list($exception, $message) = $exceptionAndMessage;
            $this->expectException($exception);
            $this->expectExceptionMessage($message);
        }

        $resolver = new Option\OptionsResolver();
        foreach ($this->options as $option) {
            $resolver->addOption($option);
        }
        $resolvedOptions = $resolver->resolve($options);

        if ($expectedResult) {
            // Sort array to avoid different order in strict comparison
            sort($expectedResult);
            sort($resolvedOptions);
            $this->assertSame($expectedResult, $resolvedOptions);
        }
    }

    /**
     * @return array
     */
    abstract public function configureOptionDataProvider();
}
