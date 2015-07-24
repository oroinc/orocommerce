<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\EventListener\FormViewListener;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var FrontendPriceListRequestHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendPriceListRequestHandler;

    /**
     * @var FormViewListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->frontendPriceListRequestHandler = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new FormViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->frontendPriceListRequestHandler
        );
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->listener, $this->translator, $this->frontendPriceListRequestHandler);
    }

    public function testOnViewNoRequest()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $this->listener->onCustomerView($event);
        $this->listener->onCustomerGroupView($event);
        $this->listener->onProductView($event);
        $this->listener->onFrontendProductView($event);
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
    public function testOnCustomerView($isPriceListExist)
    {
        $customerId = 1;
        $customer = new Customer();
        $priceList = new PriceList();
        $templateHtml = 'template_html';

        $this->listener->setRequest(new Request(['id' => $customerId]));

        $priceRepository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceRepository->expects($this->once())
            ->method('getPriceListByCustomer')
            ->with($customer)
            ->willReturn($isPriceListExist ? $priceList : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BCustomerBundle:Customer', $customerId)
            ->willReturn($customer);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPricingBundle:PriceList')
            ->willReturn($priceRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($isPriceListExist ? $this->once() : $this->never())
            ->method('render')
            ->with('OroB2BPricingBundle:Customer:price_list_view.html.twig', ['priceList' => $priceList])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $this->listener->onCustomerView($event);
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
    public function testOnCustomerGroupView($isPriceListExist)
    {
        $customerGroupId = 1;
        $customerGroup = new CustomerGroup();
        $priceList = new PriceList();
        $templateHtml = 'template_html';

        $this->listener->setRequest(new Request(['id' => $customerGroupId]));

        $priceRepository = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceRepository->expects($this->once())
            ->method('getPriceListByCustomerGroup')
            ->with($customerGroup)
            ->willReturn($isPriceListExist ? $priceList : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BCustomerBundle:CustomerGroup', $customerGroupId)
            ->willReturn($customerGroup);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPricingBundle:PriceList')
            ->willReturn($priceRepository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($isPriceListExist ? $this->once() : $this->never())
            ->method('render')
            ->with('OroB2BPricingBundle:Customer:price_list_view.html.twig', ['priceList' => $priceList])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $this->listener->onCustomerGroupView($event);
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
            ->with('OroB2BPricingBundle:Customer:price_list_update.html.twig', ['form' => $formView])
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

    public function testOnFrontendProductView()
    {
        $templateHtml = 'template_html';
        $prices = [];

        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 11);

        /** @var PriceList $priceList */
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', 42);

        $this->listener->setRequest(new Request(['id' => $product->getId()]));

        $productPriceRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $productPriceRepository->expects($this->once())
            ->method('findByPriceListIdAndProductIds')
            ->with($priceList->getId(), [$product->getId()])
            ->willReturn($prices);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with('OroB2BPricingBundle:ProductPrice')
            ->willReturn($productPriceRepository);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BProductBundle:Product', $product->getId())
            ->willReturn($product);

        $this->frontendPriceListRequestHandler->expects($this->once())
            ->method('getPriceList')
            ->willReturn($priceList);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Frontend/Product:productPrice.html.twig', ['prices' => $prices])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $this->listener->onFrontendProductView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][1][ScrollData::DATA]
        );
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

    /**
     * @param string $class
     * @param int $id
     * @return object
     */
    protected function getEntity($class, $id)
    {
        $entity = new $class();
        $reflection = new \ReflectionProperty(get_class($entity), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);

        return $entity;
    }
}
