<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\ParameterBag;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use OroB2B\Bundle\ProductBundle\EventListener\ProductSelectDBQueryEventListener;
use OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier;

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
     * @inheritDoc
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
    }

    public function testOnQueryWrongScope()
    {
        $this->modifier->expects($this->never())
            ->method($this->anything());

        $scope = 'scope';

        $productSelectDBQueryEventListener = new ProductSelectDBQueryEventListener(
            $this->configManager,
            $this->modifier
        );
        $productSelectDBQueryEventListener->setScope($scope);
        $productSelectDBQueryEventListener->setSystemConfigurationPath('path');

        $this->event->expects($this->once())
            ->method('getDataParameters')
            ->willReturn(new ParameterBag(['scope' => 'wrong_scope']));

        $productSelectDBQueryEventListener->onDBQuery($this->event);
    }

    public function testOnQuery()
    {
        $path = 'path';
        $scope = 'scope';

        $statuses = [
            'status1',
            'status2',
        ];

        $this->event->expects($this->once())
            ->method('getDataParameters')
            ->willReturn(new ParameterBag(['scope' => $scope]));

        $this->event->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($path)
            ->willReturn($statuses);

        $this->modifier->expects($this->once())
            ->method('modifyByInventoryStatus')
            ->with($this->queryBuilder, $statuses);

        $productSelectDBQueryEventListener = new ProductSelectDBQueryEventListener(
            $this->configManager,
            $this->modifier
        );

        $productSelectDBQueryEventListener->setScope($scope);
        $productSelectDBQueryEventListener->setSystemConfigurationPath($path);

        $productSelectDBQueryEventListener->onDBQuery($this->event);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage SystemConfigurationPath not configured for ProductSelectDBQueryEventListener
     */
    public function testSystemConfigurationPathEmpty()
    {
        $productSelectDBQueryEventListener = new ProductSelectDBQueryEventListener(
            $this->configManager,
            $this->modifier
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
            $this->modifier
        );

        $productSelectDBQueryEventListener->setSystemConfigurationPath('path');

        $productSelectDBQueryEventListener->onDBQuery($this->event);
    }
}
