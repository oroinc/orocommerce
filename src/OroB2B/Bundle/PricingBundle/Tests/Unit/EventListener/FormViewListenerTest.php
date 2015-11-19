<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\EventListener\FormViewListener;
use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var FrontendPriceListRequestHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendPriceListRequestHandler;

    protected function setUp()
    {
        parent::setUp();

        $this->frontendPriceListRequestHandler = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator, $this->frontendPriceListRequestHandler);
    }

    public function testOnViewNoRequest()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = $this->getListener($requestStack);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $listener->onAccountView($event);
        $listener->onAccountGroupView($event);
        $listener->onProductView($event);
        $listener->onFrontendProductView($event);
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
     * @param bool $isPriceListsExist
     * @dataProvider viewDataProvider
     */
    public function testOnAccountView($isPriceListsExist)
    {
        $accountId = 1;
        $account = new Account();
        $websiteId1 = 12;
        $websiteId2 = 13;
        /** @var Website $website1 */
        $website1 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId1);
        /** @var Website $website2 */
        $website2 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId2);

        $priceListToAccount1 = new PriceListToAccount();
        $priceListToAccount1->setAccount($account);
        $priceListToAccount1->setWebsite($website1);
        $priceListToAccount1->setPriority(3);
        $priceListToAccount2 = clone $priceListToAccount1;
        $priceListToAccount2->setWebsite($website2);
        $priceListsToAccount = [$priceListToAccount1, $priceListToAccount2];
        $templateHtml = 'template_html';

        $request = new Request(['id' => $accountId]);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $priceToAccountRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $priceToAccountRepository->expects($this->once())
            ->method('findBy')
            ->with(['account' => $account])
            ->willReturn($isPriceListsExist ? $priceListsToAccount : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BAccountBundle:Account', $accountId)
            ->willReturn($account);
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method('getRepository')->willReturn($priceToAccountRepository);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with('OroB2BPricingBundle:PriceListToAccount')
            ->willReturn($em);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($isPriceListsExist ? $this->once() : $this->never())
            ->method('render')
            ->with(
                'OroB2BPricingBundle:Account:price_list_view.html.twig',
                ['priceListsByWebsites' =>
                    [
                        $websiteId1 => [
                            'priceLists' => [$priceListToAccount1],
                            'website' => $website1
                        ],
                        $websiteId2 => [
                            'priceLists' => [$priceListToAccount2],
                            'website' => $website2
                        ],
                    ]
                ]
            )
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onAccountView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPriceListsExist) {
            $this->assertEquals(
                [$templateHtml],
                $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        } else {
            $this->assertEmpty($scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]);
        }
    }

    /**
     * @param bool $isPriceListsExist
     * @dataProvider viewDataProvider
     */
    public function testOnAccountGroupView($isPriceListsExist)
    {
        $accountGroupId = 1;
        $accountGroup = new AccountGroup();
        $websiteId1 = 12;
        $websiteId2 = 13;
        /** @var Website $website1 */
        $website1 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId1);
        /** @var Website $website2 */
        $website2 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', $websiteId2);

        $priceListToAccountGroup1 = new PriceListToAccountGroup();
        $priceListToAccountGroup1->setAccountGroup($accountGroup);
        $priceListToAccountGroup1->setWebsite($website1);
        $priceListToAccountGroup1->setPriority(3);
        $priceListToAccountGroup2 = clone $priceListToAccountGroup1;
        $priceListToAccountGroup2->setWebsite($website2);
        $priceListsToAccount = [$priceListToAccountGroup1, $priceListToAccountGroup2];
        $templateHtml = 'template_html';

        $request = new Request(['id' => $accountGroupId]);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $priceToAccountGroupRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $priceToAccountGroupRepository->expects($this->once())
            ->method('findBy')
            ->with(['accountGroup' => $accountGroup])
            ->willReturn($isPriceListsExist ? $priceListsToAccount : null);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with('OroB2BAccountBundle:AccountGroup', $accountGroupId)
            ->willReturn($accountGroup);
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method('getRepository')->willReturn($priceToAccountGroupRepository);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with('OroB2BPricingBundle:PriceListToAccountGroup')
            ->willReturn($em);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($isPriceListsExist ? $this->once() : $this->never())
            ->method('render')
            ->with(
                'OroB2BPricingBundle:Account:price_list_view.html.twig',
                ['priceListsByWebsites' =>
                    [
                        $websiteId1 => [
                            'priceLists' => [$priceListToAccountGroup1],
                            'website' => $website1
                        ],
                        $websiteId2 => [
                            'priceLists' => [$priceListToAccountGroup2],
                            'website' => $website2
                        ],
                    ]
                ]
            )
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onAccountGroupView($event);
        $scrollData = $event->getScrollData()->getData();

        if ($isPriceListsExist) {
            $this->assertEquals(
                [$templateHtml],
                $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
            );
        } else {
            $this->assertEmpty($scrollData[ScrollData::DATA_BLOCKS][0][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]);
        }
    }

    public function testOnEntityEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BPricingBundle:Account:price_list_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);
        $listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnProductView()
    {
        $productId = 1;
        $product = new Product();
        $templateHtml = 'template_html';

        $request = new Request(['id' => $productId]);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

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
        $listener->onProductView($event);
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

        $request = new Request(['id' => $product->getId()]);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

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
        $listener->onFrontendProductView($event);
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
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);
        $listener->onProductEdit($event);
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

    /**
     * @param RequestStack $requestStack
     * @return FormViewListener
     */
    protected function getListener(RequestStack $requestStack)
    {
        return new FormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->frontendPriceListRequestHandler
        );
    }
}
