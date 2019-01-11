<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\ConsentBundle\EventListener\CustomerUserViewListener;
use Oro\Bundle\ConsentBundle\Provider\CustomerUserConsentProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerUserViewListenerTest extends FormViewListenerTestCase
{
    use EntityTrait;

    /** @var CustomerUserViewListener */
    private $listener;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var CustomerUserConsentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUserConsentProvider;

    public function setUp()
    {
        parent::setUp();

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->environment = $this->createMock(\Twig_Environment::class);
        $this->customerUserConsentProvider = $this->createMock(CustomerUserConsentProvider::class);

        $this->listener = new CustomerUserViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->requestStack,
            $this->customerUserConsentProvider
        );
    }

    public function testOnCustomerUserView()
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new \stdClass(), new FormView());

        $this->expectRequestWithId(35);

        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 35]);

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(CustomerUser::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('find')
            ->with(35)
            ->willReturn($customerUser);

        $this->customerUserConsentProvider->expects($this->once())
            ->method('hasEnabledConsentsByCustomerUser')
            ->with($customerUser)
            ->willReturn(true);

        $consentsWithAcceptances = [
            'consent' => $this->getEntity(Consent::class, ['id' => 1]),
            'accepted' => true,
            'landingPage' => $this->getEntity(Page::class, ['id' => 1])
        ];
        $this->customerUserConsentProvider->expects($this->once())
            ->method('getCustomerUserConsentsWithAcceptances')
            ->with($customerUser)
            ->willReturn($consentsWithAcceptances);

        $this->environment->expects($this->once())
            ->method('render')
            ->with(
                'OroConsentBundle:CustomerUser:consent_view.html.twig',
                ['consents' => $consentsWithAcceptances]
            )
            ->willReturn('template');

        $this->assertFalse($scrollData->hasBlock(0));

        $this->listener->onCustomerUserView($event);

        $this->assertTrue($scrollData->hasBlock(0));
        $this->assertTrue($scrollData->hasNamedField('0'));
        $this->assertEquals([
            'dataBlocks' => [
                [
                    'subblocks' => [
                        [
                            'data' => ['template'],
                        ]
                    ],
                    'title' => 'oro.consent.entity_plural_label.trans',
                    'useSubBlockDivider' => true,
                ],
            ],
        ], $scrollData->getData());
    }

    public function testOnCustomerUserViewFeatureDisabled()
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new \stdClass(), new FormView());

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->customerUserConsentProvider->expects($this->never())
            ->method('hasEnabledConsentsByCustomerUser');

        $this->environment->expects($this->never())
            ->method('render');

        $this->assertFalse($scrollData->hasBlock(0));

        $featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener->setFeatureChecker($featureChecker);
        $this->listener->addFeature('disabledFeature');

        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('disabledFeature', null)
            ->willReturn(false);

        $this->listener->onCustomerUserView($event);

        $this->assertFalse($scrollData->hasBlock(0));
    }

    public function testOnCustomerUserViewNoConsents()
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new \stdClass(), new FormView());

        $this->expectRequestWithId(35);

        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 35]);

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(CustomerUser::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('find')
            ->with(35)
            ->willReturn($customerUser);

        $this->customerUserConsentProvider->expects($this->once())
            ->method('hasEnabledConsentsByCustomerUser')
            ->with($customerUser)
            ->willReturn(false);

        $this->environment->expects($this->never())
            ->method('render');

        $this->assertFalse($scrollData->hasBlock(0));

        $this->listener->onCustomerUserView($event);

        $this->assertFalse($scrollData->hasBlock(0));
    }

    public function testOnCustomerUserViewNoRequest()
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new \stdClass(), new FormView());

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->customerUserConsentProvider->expects($this->never())
            ->method('hasEnabledConsentsByCustomerUser');

        $this->environment->expects($this->never())
            ->method('render');

        $this->assertFalse($scrollData->hasBlock(0));

        $this->listener->onCustomerUserView($event);

        $this->assertFalse($scrollData->hasBlock(0));
    }

    public function testOnCustomerUserViewNoEntityId()
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new \stdClass(), new FormView());

        $this->expectRequestWithId(null);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->customerUserConsentProvider->expects($this->never())
            ->method('hasEnabledConsentsByCustomerUser');

        $this->environment->expects($this->never())
            ->method('render');

        $this->assertFalse($scrollData->hasBlock(0));

        $this->listener->onCustomerUserView($event);

        $this->assertFalse($scrollData->hasBlock(0));
    }

    public function testOnCustomerUserViewNoEntity()
    {
        $scrollData = new ScrollData();
        $event = new BeforeListRenderEvent($this->environment, $scrollData, new \stdClass(), new FormView());

        $this->expectRequestWithId(35);

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(CustomerUser::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('find')
            ->with(35)
            ->willReturn(null);

        $this->customerUserConsentProvider->expects($this->never())
            ->method('hasEnabledConsentsByCustomerUser');

        $this->environment->expects($this->never())
            ->method('render');

        $this->assertFalse($scrollData->hasBlock(0));

        $this->listener->onCustomerUserView($event);

        $this->assertFalse($scrollData->hasBlock(0));
    }

    /**
     * @param int|null $id
     */
    private function expectRequestWithId($id)
    {
        $request = $this->getRequest();
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $request->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn($id);
    }
}
