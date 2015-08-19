<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\EventListener\FormViewListener;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var FormViewListener
     */
    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper);
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->listener);
    }

    public function testOnViewNoRequest()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $this->listener->onAccountView($event);
        $this->listener->onAccountGroupView($event);
        $this->listener->onProductView($event);
    }

    /**
     * @return array
     */
    public function viewDataProvider()
    {
        return [
            'price list does not exist' => [false],
            'price list does exists' => [true],
        ];
    }

    /**
     * @param bool $isPriceListExist
     * @dataProvider viewDataProvider
     */
    public function testOnAccountView($isPriceListExist)
    {
        $accountId = 1;
        $account = new Account();
        $priceList = new PriceList();
        $templateHtml = 'template_html';

        $this->listener->setRequest(new Request(['id' => $accountId]));

        $priceRepository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceRepository->expects($this->once())
            ->method('getPriceListByAccount')
            ->with($account)
            ->willReturn($isPriceListExist ? $priceList : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BAccountBundle:Account', $accountId)
            ->willReturn($account);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPricingBundle:PriceList')
            ->willReturn($priceRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($isPriceListExist ? $this->once() : $this->never())
            ->method('render')
            ->with('OroB2BPricingBundle:Account:price_list_view.html.twig', ['priceList' => $priceList])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $this->listener->onAccountView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPriceListExist) {
            $this->assertEquals(
                [$templateHtml],
                $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        } else {
            $this->assertEmpty($scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]);
        }
    }

    /**
     * @param bool $isPriceListExist
     * @dataProvider viewDataProvider
     */
    public function testOnAccountGroupView($isPriceListExist)
    {
        $accountGroupId = 1;
        $accountGroup = new AccountGroup();
        $priceList = new PriceList();
        $templateHtml = 'template_html';

        $this->listener->setRequest(new Request(['id' => $accountGroupId]));

        $priceRepository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceRepository->expects($this->once())
            ->method('getPriceListByAccountGroup')
            ->with($accountGroup)
            ->willReturn($isPriceListExist ? $priceList : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BAccountBundle:AccountGroup', $accountGroupId)
            ->willReturn($accountGroup);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPricingBundle:PriceList')
            ->willReturn($priceRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($isPriceListExist ? $this->once() : $this->never())
            ->method('render')
            ->with('OroB2BPricingBundle:Account:price_list_view.html.twig', ['priceList' => $priceList])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $this->listener->onAccountGroupView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPriceListExist) {
            $this->assertEquals(
                [$templateHtml],
                $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        } else {
            $this->assertEmpty($scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]);
        }
    }

    public function testOnEntityEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Account:price_list_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);
        $this->listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnProductView()
    {
        $productId = 1;
        $product = new Product();
        $templateHtml = 'template_html';

        $this->listener->setRequest(new Request(['id' => $productId]));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BProductBundle:Product', $productId)
            ->willReturn($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Product:prices_view.html.twig', ['entity' => $product])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $this->listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertScrollDataPriceBlock($scrollData, $templateHtml);
    }

    public function testOnProductEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Product:prices_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);
        $this->listener->onProductEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertScrollDataPriceBlock($scrollData, $templateHtml);
    }

    /**
     * @param array $scrollData
     * @param string $html
     */
    protected function assertScrollDataPriceBlock(array $scrollData, $html)
    {
        $this->assertEquals(
            'orob2b.pricing.productprice.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::TITLE]
        );
        $this->assertEquals(
            [$html],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param FormView $formView
     * @return BeforeListRenderEvent
     */
    protected function createEvent(\Twig_Environment $environment, FormView $formView = null)
    {
        $defaultData = [
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => []
                        ]
                    ]
                ]
            ]
        ];

        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), $formView);
    }
}
