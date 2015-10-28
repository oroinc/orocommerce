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
     * @var
     */
    protected $productSelectDBQueryEventListener;

    public function testOnDBQueryWrongScope()
    {
        $configManager = $this->getConfigManagerMock();

        $modifier = $this->getModifierMock();

        $modifier->expects($this->never())
            ->method($this->anything());

        $scope = 'scope';

        $productSelectDBQueryEventListener = new ProductSelectDBQueryEventListener(
            $configManager,
            $modifier,
            $scope,
            'path'
        );

        $event = $this->getEventMock(new ParameterBag(['scope' => 'wrong_scope']));

        $productSelectDBQueryEventListener->onDBQuery($event);
    }

    public function testOnDBQuery()
    {
        $path = 'path';
        $scope = 'scope';

        $statuses = [
            'status1',
            'status2',
        ];

        $event = $this->getEventMock(new ParameterBag(['scope' => $scope]));

        $queryBuilder = $this->getQueryBuilderMock();

        $event->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $configManager = $this->getConfigManagerMock();

        $configManager->expects($this->once())
            ->method('get')
            ->with($path)
            ->willReturn($statuses);

        $modifier = $this->getModifierMock();

        $modifier->expects($this->once())
            ->method('modifyByInventoryStatus')
            ->with($queryBuilder, $statuses);

        $productSelectDBQueryEventListener = new ProductSelectDBQueryEventListener(
            $configManager,
            $modifier,
            $scope,
            $path
        );


        $productSelectDBQueryEventListener->onDBQuery($event);
    }

    /**
     * @return ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigManagerMock()
    {
        return $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ProductVisibilityQueryBuilderModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getModifierMock()
    {
        return $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductVisibilityQueryBuilderModifier');
    }

    /**
     * @param ParameterBag $dataParameters
     * @return ProductSelectDBQueryEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEventMock($dataParameters)
    {
        $event = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent')
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getDataParameters')
            ->willReturn($dataParameters);

        return $event;
    }

    /**
     * @return QueryBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getQueryBuilderMock()
    {
        return $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();
    }
}
