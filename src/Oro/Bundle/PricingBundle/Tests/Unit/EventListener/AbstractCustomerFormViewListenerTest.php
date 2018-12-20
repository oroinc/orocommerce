<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\EventListener\AbstractCustomerFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractCustomerFormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var WebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websiteProvider;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestStack;

    /**
     * @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $env;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $featureChecker;

    /**
     * @return BasePriceListRelation[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    abstract protected function setRepositoryExpectations();

    /**
     * @return string
     */
    abstract protected function getFallbackLabel();

    /**
     * @return AbstractCustomerFormViewListener
     */
    abstract protected function getListener();

    /**
     * @param BeforeListRenderEvent $event
     */
    abstract protected function processEvent(BeforeListRenderEvent $event);

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

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->env = $this->createMock(\Twig_Environment::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
    }

    protected function tearDown()
    {
        unset($this->websiteProvider, $this->requestStack, $this->env, $this->featureChecker);

        parent::tearDown();
    }

    public function testSetUpdateTemplate()
    {
        $listener = $this->getListener();

        $listener->setUpdateTemplate("test");

        $reflection = new \ReflectionObject($listener);
        $relationClass = $reflection->getProperty('updateTemplate');
        $relationClass->setAccessible(true);

        $this->assertSame("test", $relationClass->getValue($listener));
    }

    public function testOnEntityEditFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $listener = $this->getListener();
        $listener->setFeatureChecker($this->featureChecker);
        $listener->addFeature('feature1');

        $this->env->expects($this->never())
            ->method('render');

        $event = $this->createEvent($this->env);
        $listener->onEntityEdit($event);
    }

    public function testOnViewNoRequest()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        $event = $this->createEvent($this->env);
        $this->processEvent($event);
    }

    public function testOnCustomerView()
    {
        $customerId = 1;
        $templateHtml = 'template_html';

        $request = new Request(['id' => $customerId]);

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        $priceLists = $this->setRepositoryExpectations();

        $this->env->expects($this->once())
            ->method('render')
            ->with(
                'OroPricingBundle:Customer:price_list_view.html.twig',
                [
                    'priceLists' => $priceLists,
                    'fallback' => $this->getFallbackLabel(),
                ]
            )
            ->willReturn($templateHtml);
        $event = $this->createEvent($this->env);

        $this->processEvent($event);
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

        $this->env->expects($this->once())
            ->method('render')
            ->with('OroPricingBundle:Customer:price_list_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $event = $this->createEvent($this->env, $formView);
        $listener = $this->getListener();
        $listener->setFeatureChecker($this->featureChecker);
        $listener->addFeature('feature1');
        $listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
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
        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), new \stdClass(), $formView);
    }
}
