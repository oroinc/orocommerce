<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchTypeProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class WebsiteSearchTypeProviderCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    protected const TESTED_SERVICE_NAME = 'oro_website_search.search_type_chain_provider';
    protected const TESTED_SERVICE_TAG = 'oro_website_search.search_type';

    /**
     * @var WebsiteSearchTypeProviderCompilerPass
     */
    protected $compilerPass;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $container;

    protected function setUp()
    {
        $this->container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->getMock();

        $this->compilerPass = new WebsiteSearchTypeProviderCompilerPass();
    }

    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testServiceNotExists(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(self::TESTED_SERVICE_NAME)
            ->willReturn(false);

        $this->container
            ->expects($this->never())
            ->method('findDefinition');

        $this->container
            ->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    /**
     * @expectedException \LogicException
     */
    public function testNoServicesWithTagFound(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(self::TESTED_SERVICE_NAME)
            ->willReturn(true);

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container
            ->expects($this->once())
            ->method('findDefinition')
            ->with(self::TESTED_SERVICE_NAME)
            ->will($this->returnValue($definition));

        $services = [];

        $this->container
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(self::TESTED_SERVICE_TAG)
            ->willReturn($services);

        $definition->expects($this->never())
            ->method('addMethodCall');

        $this->compilerPass->process($this->container);
    }

    /**
     * @expectedException \LogicException
     */
    public function testHasNoParameterIsDefault(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(self::TESTED_SERVICE_NAME)
            ->willReturn(true);

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container
            ->expects($this->once())
            ->method('findDefinition')
            ->with(self::TESTED_SERVICE_NAME)
            ->will($this->returnValue($definition));

        $services = [
            'oro_test_service_1' => [
                ['type' => 'test_1'],
            ],
        ];

        $this->container
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(self::TESTED_SERVICE_TAG)
            ->willReturn($services);

        $definition->expects($this->never())
            ->method('addMethodCall');

        $this->compilerPass->process($this->container);
    }

    /**
     * @expectedException \LogicException
     */
    public function testHasNoParameterType(): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(self::TESTED_SERVICE_NAME)
            ->willReturn(true);

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container
            ->expects($this->once())
            ->method('findDefinition')
            ->with(self::TESTED_SERVICE_NAME)
            ->will($this->returnValue($definition));

        $services = [
            'oro_test_service_1' => [
                ['typo' => 'test_1'],
            ],
        ];

        $this->container
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(self::TESTED_SERVICE_TAG)
            ->willReturn($services);

        $definition->expects($this->never())
            ->method('addMethodCall');

        $this->compilerPass->process($this->container);
    }

    /**
     * @param array $services
     *
     * @param int   $methodCallNumbers
     *
     * @dataProvider stubFoundServiceDataProvider
     */
    public function testWithTheServicesFound(array $services, int $methodCallNumbers): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(self::TESTED_SERVICE_NAME)
            ->willReturn(true);

        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container
            ->expects($this->once())
            ->method('findDefinition')
            ->with(self::TESTED_SERVICE_NAME)
            ->will($this->returnValue($definition));

        $this->container
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(self::TESTED_SERVICE_TAG)
            ->willReturn($services);

        $definition->expects($this->exactly($methodCallNumbers))
            ->method('addMethodCall');

        $this->compilerPass->process($this->container);
    }

    /**
     * @return array
     */
    public function stubFoundServiceDataProvider(): array
    {
        return [
            'one service'  => [
                'services'          => [
                    'oro_test_service_1' => [
                        ['type' => 'test_1', 'isDefault' => true],
                    ],
                ],
                'methodCallNumbers' => 2,
            ],
            'few services' => [
                'services'          => [
                    'oro_test_service_1' => [
                        ['type' => 'test_1'],
                    ],
                    'oro_test_service_2' => [
                        ['type' => 'test_2', 'isDefault' => true],
                    ],
                    'oro_test_service_3' => [
                        ['type' => 'test_3'],
                    ],
                ],
                'methodCallNumbers' => 4,
            ],
        ];
    }

    /**
     * @param array $services
     * @param array $expectedOrder
     *
     * @dataProvider servicesOrderDataProvider
     */
    public function testServicesOrder(array $services, array $expectedOrder): void
    {
        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(self::TESTED_SERVICE_NAME)
            ->willReturn(true);

        $definition = new Definition();

        $this->container
            ->expects($this->once())
            ->method('findDefinition')
            ->with(self::TESTED_SERVICE_NAME)
            ->will($this->returnValue($definition));

        $this->container
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(self::TESTED_SERVICE_TAG)
            ->willReturn($services);

        $this->compilerPass->process($this->container);

        $calls = $definition->getMethodCalls();

        $this->assertEquals($calls, $expectedOrder);
    }

    /**
     * @return array
     */
    public function servicesOrderDataProvider(): array
    {
        return [
            'direct order'    => [
                'services'      => [
                    'oro_test_service_1' => [
                        ['type' => 'test_1', 'order' => 0],
                    ],
                    'oro_test_service_2' => [
                        ['type' => 'test_2', 'order' => 10],
                    ],
                    'oro_test_service_3' => [
                        ['type' => 'test_3', 'order' => 20, 'isDefault' => true],
                    ],
                ],
                'expectedOrder' => [
                    [
                        'addSearchType',
                        ['test_1', new Reference('oro_test_service_1')],
                    ],
                    [
                        'addSearchType',
                        ['test_2', new Reference('oro_test_service_2')],
                    ],
                    [
                        'setDefaultSearchType',
                        [new Reference('oro_test_service_3')],
                    ],
                    [
                        'addSearchType',
                        ['test_3', new Reference('oro_test_service_3')],
                    ],
                ],
            ],
            'in direct order' => [
                'services'      => [
                    'oro_test_service_1' => [
                        ['type' => 'test_1', 'order' => 80],
                    ],
                    'oro_test_service_2' => [
                        ['type' => 'test_2', 'order' => 0],
                    ],
                    'oro_test_service_3' => [
                        ['type' => 'test_3', 'order' => 20, 'isDefault' => true],
                    ],
                ],
                'expectedOrder' => [
                    [
                        'addSearchType',
                        ['test_2', new Reference('oro_test_service_2')],
                    ],
                    [
                        'setDefaultSearchType',
                        [new Reference('oro_test_service_3')],
                    ],
                    [
                        'addSearchType',
                        ['test_3', new Reference('oro_test_service_3')],
                    ],
                    [
                        'addSearchType',
                        ['test_1', new Reference('oro_test_service_1')],
                    ],
                ],
            ],
        ];
    }
}
