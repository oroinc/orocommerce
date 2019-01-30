<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Event\DeclinedConsentsEvent;
use Oro\Bundle\ConsentBundle\Form\Extension\FrontendCustomerUserProfileExtension;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\CustomerUserStub;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserProfileType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\TestUtils\ORM\Mocks\UnitOfWork;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FrontendCustomerUserProfileExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formBuilder;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var FrontendCustomerUserProfileExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->extension = new FrontendCustomerUserProfileExtension($this->eventDispatcher);
        $this->extension->setFeatureChecker($this->featureChecker);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(FrontendCustomerUserProfileType::class, $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        $this->extension->addFeature('feature');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->formBuilder->expects($this->once())
            ->method('add')
            ->with(
                ConsentAcceptanceType::TARGET_FIELDNAME,
                ConsentAcceptanceType::class,
                ['constraints' => [new RemovedLandingPages(), new RemovedConsents()]]
            );

        $this->formBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->extension, 'onPostSubmit']);

        $this->extension->buildForm($this->formBuilder, []);
    }

    public function testBuildFormFeatureDisabled()
    {
        $this->extension->addFeature('feature');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->formBuilder->expects($this->never())
            ->method('add');

        $this->formBuilder->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($this->formBuilder, []);
    }

    public function testBuildFormNotEnabledRequiredConsentsConstraint()
    {
        $this->extension->addFeature('feature');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->formBuilder->expects($this->once())
            ->method('add')
            ->with(
                ConsentAcceptanceType::TARGET_FIELDNAME,
                ConsentAcceptanceType::class,
                ['constraints' => [new RemovedLandingPages(), new RemovedConsents()]]
            );

        $this->extension->buildForm($this->formBuilder, []);
    }

    public function testOnPostSubmitWithoutCustomerUser()
    {
        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(new \stdClass());

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitWithoutAcceptedConsents()
    {
        $customerUser = new CustomerUserStub();

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($customerUser);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitWithoutDeclinedConsents()
    {
        $customerUser = new CustomerUserStub();
        $customerUser->setAcceptedConsents($this->getCollection());

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($customerUser);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->extension->onPostSubmit($event);
    }

    public function testOnPostSubmitWithDeclinedConsents()
    {
        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $acceptedConsents = $this->getCollection([$consentAcceptance]);

        // Decline accepted consent
        $acceptedConsents->removeElement($consentAcceptance);

        $customerUser = new CustomerUserStub();
        $customerUser->setAcceptedConsents(
            $acceptedConsents
        );

        /** @var FormEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($customerUser);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(DeclinedConsentsEvent::EVENT_NAME, new DeclinedConsentsEvent([$consentAcceptance], $customerUser));

        $this->extension->onPostSubmit($event);
    }

    /**
     * @param array $items
     *
     * @return PersistentCollection
     */
    private function getCollection(array $items = []): PersistentCollection
    {
        $uow = new UnitOfWork();
        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $em->method('getUnitOfWork')
            ->willReturn($uow);

        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collection = new PersistentCollection($em, $metadata, new ArrayCollection($items));

        $collection->takeSnapshot();

        return $collection;
    }
}
