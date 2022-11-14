<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model;

use Oro\Bundle\CheckoutBundle\Exception\CheckoutLineItemConverterNotFoundException as RegistryException;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterInterface;
use Oro\Bundle\CheckoutBundle\Model\CheckoutLineItemConverterRegistry;
use Psr\Log\LoggerInterface;

class CheckoutLineItemConverterRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $converter1;

    /** @var CheckoutLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $converter2;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var CheckoutLineItemConverterRegistry */
    private $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->converter1 = $this->createMock(CheckoutLineItemConverterInterface::class);
        $this->converter2 = $this->createMock(CheckoutLineItemConverterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->registry = new CheckoutLineItemConverterRegistry(
            [$this->converter1, $this->converter2],
            $this->logger
        );
    }

    public function testGetConverter()
    {
        $source = new \stdClass();

        $this->converter1->expects($this->once())
            ->method('isSourceSupported')
            ->with($source)
            ->willReturn(false);
        $this->converter2->expects($this->once())
            ->method('isSourceSupported')
            ->with($source)
            ->willReturn(true);

        self::assertSame($this->converter2, $this->registry->getConverter($source));
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testGetConverterWhenNoConvertersSupportSource(mixed $object, string $expectedMessage)
    {
        $this->converter1->expects($this->once())
            ->method('isSourceSupported')
            ->with($object)
            ->willReturn(false);
        $this->converter2->expects($this->once())
            ->method('isSourceSupported')
            ->with($object)
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($expectedMessage, ['source_instance' => $object]);

        $this->expectException(RegistryException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->registry->getConverter($object);
    }

    public function exceptionDataProvider(): array
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
