<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\EventListener\ProductSelectDBQueryEventListener;
use Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

class ProductSelectDBQueryEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductSelectDBQueryEventListener
     */
    protected $listener;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ProductVisibilityQueryBuilderModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $modifier;

    /**
     * @var ProductDBQueryRestrictionEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryBuilder;

    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendHelper;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->modifier = $this->getMock('Oro\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier');

        $this->event = $this->getMockBuilder('Oro\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent')
            ->disableOriginalConstructor()->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()
            ->getMock();

        $this->frontendHelper = $this->getMockBuilder('Oro\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = $this->createListener();
    }

    /**
     * @return ProductSelectDBQueryEventListener
     */
    protected function createListener()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        return new ProductSelectDBQueryEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper,
            $requestStack
        );
    }

    /**
     * @dataProvider onQueryDataProvider
     * @param bool $isFrontend
     * @param string|null $frontendPath
     * @param string|null $backendPath
     */
    public function testOnQuery($isFrontend, $frontendPath, $backendPath)
    {
        $statuses = [
            'status1',
            'status2',
        ];

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn($isFrontend);

        $this->event->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        if ($isFrontend && $frontendPath) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with($frontendPath)
                ->willReturn($statuses);

            $this->modifier->expects($this->once())
                ->method('modifyByInventoryStatus')
                ->with($this->queryBuilder, $statuses);
        } elseif (!$isFrontend && $backendPath) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with($backendPath)
                ->willReturn($statuses);

            $this->modifier->expects($this->once())
                ->method('modifyByInventoryStatus')
                ->with($this->queryBuilder, $statuses);
        } else {
            $this->modifier->expects($this->never())
                ->method('modifyByInventoryStatus')
                ->with($this->queryBuilder, $statuses);
        }

        $this->listener->setFrontendSystemConfigurationPath($frontendPath);
        $this->listener->setBackendSystemConfigurationPath($backendPath);

        $this->listener->onDBQuery($this->event);
    }

    /**
     * @return array
     */
    public function onQueryDataProvider()
    {
        return [
            [
                'isFrontend' => false,
                'frontendPath' => 'frontend_path',
                'backendPath' => 'backend_path',
            ],
            [
                'isFrontend' => false,
                'frontendPath' => null,
                'backendPath' => 'backend_path',
            ],
            [
                'isFrontend' => true,
                'frontendPath' => 'frontend_path',
                'backendPath' => 'backend_path',
            ],
            [
                'isFrontend' => true,
                'frontendPath' => 'frontend_path',
                'backendPath' => null,
            ],
            [
                'isFrontend' => false,
                'frontendPath' => 'frontend_path',
                'backendPath' => null,
            ],
            [
                'isFrontend' => true,
                'frontendPath' => null,
                'backendPath' => 'backend_path',
            ]
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage SystemConfigurationPath not configured for ProductSelectDBQueryEventListener
     */
    public function testSystemConfigurationPathEmpty()
    {
        $this->listener->setFrontendSystemConfigurationPath(null);
        $this->listener->setBackendSystemConfigurationPath(null);

        $this->listener->onDBQuery($this->event);
    }
}
