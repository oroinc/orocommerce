<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\EventListener\CustomerGroupFormViewListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;

class CustomerGroupFormViewListenerTest extends FormViewListenerTestCase
{

    /**
     * @var WebsiteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->websiteProvider = $this->getMockBuilder(WebsiteProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $website = new Website();
        $this->websiteProvider->method('getWebsites')->willReturn([$website]);
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator, $this->websiteProvider);
    }
    
    public function testOnViewNoRequest()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        
        $listener = $this->getListener($requestStack);
        
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');
  
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
  
        $env = $this->createMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $listener->onCustomerGroupView($event);
    }
    
    public function testOnCustomerGroupView()
    {
        $customerGroupId = 1;
        $customerGroup = new CustomerGroup();

        $priceListToCustomerGroup1 = new PriceListToCustomerGroup();
        $priceListToCustomerGroup1->setCustomerGroup($customerGroup);
        $priceListToCustomerGroup1->setPriority(3);
        $priceListToCustomerGroup1->setWebsite(current($this->websiteProvider->getWebsites()));
        $priceListToCustomerGroup2 = clone $priceListToCustomerGroup1;
        $priceListsToCustomerGroup = [$priceListToCustomerGroup1, $priceListToCustomerGroup2];
        
        $templateHtml = 'template_html';
        
        $fallbackEntity = new PriceListCustomerGroupFallback();
        $fallbackEntity->setCustomerGroup($customerGroup);
        $fallbackEntity->setFallback(PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY);
        
        $request = new Request(['id' => $customerGroupId]);
        $requestStack = $this->getRequestStack($request);
        
        /** @var CustomerGroupFormViewListener $listener */
        $listener = $this->getListener($requestStack);
        
        $this->setRepositoryExpectationsForCustomerGroup(
            $customerGroup,
            $priceListsToCustomerGroup,
            $fallbackEntity,
            $this->websiteProvider->getWebsites()
        );
        
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->createMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with(
                'OroPricingBundle:Customer:price_list_view.html.twig',
                [
                    'priceLists' => [
                        $priceListToCustomerGroup1,
                        $priceListToCustomerGroup2,
                    ],
                    'fallback' => 'oro.pricing.fallback.current_customer_group_only.label'
                ]
            )
            ->willReturn($templateHtml);
        
        $event = $this->createEvent($environment);
        $listener->onCustomerGroupView($event);
        $scrollData = $event->getScrollData()->getData();
        
        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }
    
    public function testOnEntityEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';
        
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        
        /** @var CustomerGroupFormViewListener $listener */
        $listener = $this->getListener($requestStack);
        
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->createMock('\Twig_Environment');
        
        $environment->expects($this->once())
            ->method('render')
            ->with('OroPricingBundle:Customer:price_list_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);
        
        $event = $this->createEvent($environment, $formView);
        $listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();
        
        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }
    
    /**
     * @param array $scrollData
     * @param string $html
     */
    protected function assertScrollDataPriceBlock(array $scrollData, $html)
    {
        $this->assertEquals(
            'oro.pricing.productprice.entity_plural_label.trans',
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
                            ScrollData::DATA => [],
                        ]
                    ]
                ]
            ]
        ];
        
        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), $formView);
    }
    
    /**
     * @param RequestStack $requestStack
     * @return CustomerGroupFormViewListener
     */
    protected function getListener(RequestStack $requestStack)
    {
        return new CustomerGroupFormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->websiteProvider
        );
    }
    
    /**
     * @param CustomerGroup $customerGroup
     * @param PriceListToCustomerGroup[] $priceListsToCustomerGroup
     * @param PriceListCustomerGroupFallback $fallbackEntity
     * @param Website[] $websites
     */
    protected function setRepositoryExpectationsForCustomerGroup(
        CustomerGroup $customerGroup,
        $priceListsToCustomerGroup,
        PriceListCustomerGroupFallback $fallbackEntity,
        array $websites
    ) {
        $priceToCustomerGroupRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $priceToCustomerGroupRepository->expects($this->once())
            ->method('findBy')
            ->with(['customerGroup' => $customerGroup, 'website' => $websites])
            ->willReturn($priceListsToCustomerGroup);
        $fallbackRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $fallbackRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['customerGroup' => $customerGroup, 'website' => $websites])
            ->willReturn($fallbackEntity);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customerGroup);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroPricingBundle:PriceListToCustomerGroup', $priceToCustomerGroupRepository],
                        ['OroPricingBundle:PriceListCustomerGroupFallback', $fallbackRepository]
                    ]
                )
            );
    }
    
    /**
     * @param $request
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected function getRequestStack($request)
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);
        return $requestStack;
    }
}
