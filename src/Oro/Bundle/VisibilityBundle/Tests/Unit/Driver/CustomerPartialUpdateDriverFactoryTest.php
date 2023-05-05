<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Driver;

use Oro\Bundle\SearchBundle\Engine\EngineParameters;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverFactory;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CustomerPartialUpdateDriverFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var EngineParameters|\PHPUnit\Framework\MockObject\MockObject */
    private $engineParametersBag;

    /** @var ServiceLocator|\PHPUnit\Framework\MockObject\MockObject */
    private $locator;

    protected function setUp(): void
    {
        $this->engineParametersBag = $this->createMock(EngineParameters::class);
        $this->engineParametersBag->expects(self::any())
            ->method('getEngineName')
            ->willReturn('search_engine_name');

        $this->locator = $this->createMock(ServiceLocator::class);
    }

    public function testCustomerPartialUpdateDriverInstanceReturned()
    {
        $customerPartialUpdateDriverMock = $this->createMock(CustomerPartialUpdateDriverInterface::class);
        $this->locator->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBag->getEngineName())
            ->willReturn($customerPartialUpdateDriverMock);

        self::assertEquals(
            $customerPartialUpdateDriverMock,
            CustomerPartialUpdateDriverFactory::create($this->locator, $this->engineParametersBag)
        );
    }

    /**
     * @dataProvider wrongCustomerPartialUpdateDriverInstancesProvider
     */
    public function testWrongCustomerPartialUpdateDriverInstanceTypeReturned($engine)
    {
        $this->locator->expects(self::once())
            ->method('get')
            ->with($this->engineParametersBag->getEngineName())
            ->willReturn($engine);

        $this->expectException(UnexpectedTypeException::class);

        CustomerPartialUpdateDriverFactory::create($this->locator, $this->engineParametersBag);
    }

    public function wrongCustomerPartialUpdateDriverInstancesProvider(): array
    {
        return ['scalar' => ['test string'], 'array' => [[]], 'object' => [new \stdClass()]];
    }
}
