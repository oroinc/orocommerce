<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model;

use Oro\Bundle\CheckoutBundle\Exception\CheckoutLineItemConverterNotFoundException as RegistryException;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterRegistry;
use Psr\Log\LoggerInterface;

class CheckoutLineItemConverterRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var CheckoutLineItemConverterRegistry */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->registry = new CheckoutLineItemConverterRegistry($this->logger);
    }

    public function testAddAndGetConverter()
    {
        $source = new \stdClass();

        $converter1 = $this->createMock(CheckoutLineItemConverterInterface::class);
        $this->registry->addConverter($converter1, 'test');

        /** Override by Alias */
        $converter2 = $this->createMock(CheckoutLineItemConverterInterface::class);
        $this->registry->addConverter($converter2, 'test');

        /** Should be skipped by priority */
        $converter3 = $this->createMock(CheckoutLineItemConverterInterface::class);
        $this->registry->addConverter($converter3, 'test_another');

        $converter1
            ->expects($this->never())
            ->method('isSourceSupported');

        $converter2
            ->expects($this->once())
            ->method('isSourceSupported')
            ->with($source)
            ->willReturn(true);

        $converter3
            ->expects($this->never())
            ->method('isSourceSupported');

        self::assertSame($converter2, $this->registry->getConverter($source));
    }

    /**
     * @param mixed $object
     * @param string $expectedMessage
     *
     * @dataProvider exceptionDataProvider
     */
    public function testGetConverterException($object, $expectedMessage)
    {
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($expectedMessage, ['source_instance' => $object]);

        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->registry->getConverter($object);
    }

    /**
     * @return array
     */
    public function exceptionDataProvider()
    {
        return [
            'null' => [
                'object' => null,
                'expectedMessage' => sprintf(RegistryException::MESSAGE_PATTERN, 'NULL'),
            ],
            'string' => [
                'object' => '',
                'expectedMessage' => sprintf(RegistryException::MESSAGE_PATTERN, 'string'),
            ],
            'array' => [
                'object' => [],
                'expectedMessage' => sprintf(RegistryException::MESSAGE_PATTERN, 'array'),
            ],
            'object' => [
                'object' => new \stdClass(),
                'expectedMessage' => sprintf(RegistryException::MESSAGE_PATTERN, 'stdClass'),
            ],
        ];
    }
}
