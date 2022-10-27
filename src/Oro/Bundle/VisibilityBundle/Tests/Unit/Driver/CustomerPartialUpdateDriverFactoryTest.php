<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Driver;

use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverFactory;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CustomerPartialUpdateDriverFactoryTest extends TestCase
{
    private EngineParameters $engineParametersBagMock;

    private ServiceLocator $locatorMock;

    protected function setUp(): void
    {
        $this->engineParametersBagMock = self::createMock(EngineParameters::class);
        $this->engineParametersBagMock->method('getEngineName')
            ->willReturn('search_engine_name');

        $this->locatorMock = self::createMock(ServiceLocator::class);
    }

    public function testCustomerPartialUpdateDriverInstanceReturned()
    {
        $customerPartialUpdateDriverMock = self::createMock(CustomerPartialUpdateDriverInterface::class);
        $this->locatorMock->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBagMock->getEngineName())
            ->willReturn($customerPartialUpdateDriverMock);

        self::assertEquals(
            $customerPartialUpdateDriverMock,
            CustomerPartialUpdateDriverFactory::create($this->locatorMock, $this->engineParametersBagMock)
        );
    }

    /**
     * @dataProvider wrongCustomerPartialUpdateDriverInstancesProvider
     */
    public function testWrongCustomerPartialUpdateDriverInstanceTypeReturned($engine)
    {
        $this->locatorMock->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBagMock->getEngineName())
            ->willReturn($engine);

        $this->expectException(UnexpectedTypeException::class);

        CustomerPartialUpdateDriverFactory::create($this->locatorMock, $this->engineParametersBagMock);
    }

    /**
     * @return array
     */
    public function wrongCustomerPartialUpdateDriverInstancesProvider(): array
    {
        return ['scalar' => ['test string'], 'array' => [[]], 'object' => [new \StdClass()]];
    }
}
