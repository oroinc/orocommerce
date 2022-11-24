<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\BasePriceListRelation;
use Oro\Bundle\PricingBundle\EventListener\AbstractCustomerFormViewListener;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

abstract class AbstractCustomerFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    protected $env;

    /** @var WebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $websiteProvider;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    protected $requestStack;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
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

    abstract protected function processEvent(BeforeListRenderEvent $event);

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '.trans';
            });

        $this->env = $this->createMock(Environment::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $website = new Website();
        $this->websiteProvider->expects($this->any())
            ->method('getWebsites')
            ->willReturn([$website]);

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
    }

    public function testSetUpdateTemplate()
    {
        $listener = $this->getListener();

        $listener->setUpdateTemplate('test');
        $this->assertSame('test', ReflectionUtil::getPropertyValue($listener, 'updateTemplate'));
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

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $priceLists = $this->setRepositoryExpectations();

        $this->env->expects($this->once())
            ->method('render')
            ->with(
                '@OroPricing/Customer/price_list_view.html.twig',
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
            ->with('@OroPricing/Customer/price_list_update.html.twig', ['form' => $formView])
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
     * @param Environment $environment
     * @param FormView $formView
     * @return BeforeListRenderEvent
     */
    protected function createEvent(Environment $environment, FormView $formView = null)
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
