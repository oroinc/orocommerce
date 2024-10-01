<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\OrderBundle\Datagrid\OrdersViewList;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrdersViewListTest extends \PHPUnit\Framework\TestCase
{
    private OrdersViewList $viewList;

    #[\Override]
    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return sprintf('*%s*', $key);
            });

        $this->viewList = new OrdersViewList($translator);
    }

    public function testGetList(): void
    {
        $view = new View(
            'oro_order.open_orders',
            [
                'internal_status' => [
                    'type'  => EnumFilterType::TYPE_IN,
                    'value' => ['order_internal_status.open']
                ]
            ]
        );
        $view->setLabel('*oro.order.datagrid.view.open_orders*')->setDefault(true);

        self::assertEquals(new ArrayCollection([$view]), $this->viewList->getList());
    }
}
