<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;
use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use OroB2B\Bundle\ProductBundle\EventListener\ProductSelectDBQueryEventListener;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;
use Symfony\Component\HttpFoundation\Request;

class ProductSelectDBQueryEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductSelectDBQueryEventListener
     */
    protected $productSelectDBQueryEventListener;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ProductVisibilityQueryBuilderModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $modifier;

    /**
     * @var ProductSelectDBQueryEvent|\PHPUnit_Framework_MockObject_MockObject
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

        $this->modifier = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier');

        $this->event = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent')
            ->disableOriginalConstructor()->getMock();

        $this->queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()
            ->getMock();

        $this->frontendHelper = $this->getMockBuilder('OroB2B\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnQueryWrongScope()
    {
        $this->modifier->expects($this->never())
            ->method($this->anything());

        $scope = 'scope';

        $productSelectDBQueryEventListener = new ProductSelectDBQueryEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper
        );
        $productSelectDBQueryEventListener->setScope($scope);
        $productSelectDBQueryEventListener->setBackendSystemConfigurationPath('path');

        $this->event->expects($this->once())
            ->method('getDataParameters')
            ->willReturn(new ParameterBag(['scope' => 'wrong_scope']));

        $productSelectDBQueryEventListener->onDBQuery($this->event);
    }

    /**
     * @dataProvider onQueryDataProvider
     * @param bool $isFrontend
     * @param string|null $frontendPath
     * @param string|null $backendPath
     */
    public function testOnQuery($isFrontend, $frontendPath, $backendPath)
    {
        $scope = 'scope';

        $statuses = [
            'status1',
            'status2',
        ];

        $this->frontendHelper->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn($isFrontend);

        $this->event->expects($this->once())
            ->method('getDataParameters')
            ->willReturn(new ParameterBag(['scope' => $scope]));

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

        $productSelectDBQueryEventListener = new ProductSelectDBQueryEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper
        );

        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $productSelectDBQueryEventListener->setRequestStack($requestStack);

        $productSelectDBQueryEventListener->setScope($scope);
        $productSelectDBQueryEventListener->setFrontendSystemConfigurationPath($frontendPath);
        $productSelectDBQueryEventListener->setBackendSystemConfigurationPath($backendPath);

        $productSelectDBQueryEventListener->onDBQuery($this->event);
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
        $productSelectDBQueryEventListener = new ProductSelectDBQueryEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper
        );

        $productSelectDBQueryEventListener->onDBQuery($this->event);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Scope not configured for ProductSelectDBQueryEventListener
     */
    public function testScopeEmpty()
    {
        $productSelectDBQueryEventListener = new ProductSelectDBQueryEventListener(
            $this->configManager,
            $this->modifier,
            $this->frontendHelper
        );

        $productSelectDBQueryEventListener->setBackendSystemConfigurationPath('path');

        $productSelectDBQueryEventListener->onDBQuery($this->event);
    }
}
