<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\GuestCustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\PopulateFieldCustomerConsentsSubscriber;
use Oro\Bundle\ConsentBundle\Form\Extension\FrontendRfqExtension;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FrontendRfqExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var FrontendRfqExtension */
    private $extension;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var CustomerConsentsEventSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $saveConsentAcceptanceSubscriber;

    /** @var GuestCustomerConsentsEventSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $guestCustomerConsentsEventSubscriber;

    /** @var PopulateFieldCustomerConsentsSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $populateFieldCustomerConsentsSubscriber;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->saveConsentAcceptanceSubscriber = $this->createMock(CustomerConsentsEventSubscriber::class);
        $this->guestCustomerConsentsEventSubscriber = $this->createMock(GuestCustomerConsentsEventSubscriber::class);
        $this->populateFieldCustomerConsentsSubscriber = $this->createMock(
            PopulateFieldCustomerConsentsSubscriber::class
        );
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->extension = new FrontendRfqExtension(
            $this->saveConsentAcceptanceSubscriber,
            $this->guestCustomerConsentsEventSubscriber,
            $this->populateFieldCustomerConsentsSubscriber,
            $this->tokenStorage
        );

        $this->extension->setFeatureChecker($this->featureChecker);
        $this->extension->addFeature('consents');

        $this->builder = $this->createMock(FormBuilderInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->featureChecker,
            $this->saveConsentAcceptanceSubscriber,
            $this->extension,
            $this->builder,
            $this->guestCustomerConsentsEventSubscriber,
            $this->tokenStorage
        );
    }

    public function testBuildFormWithFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(false);

        $this->builder->expects($this->never())
            ->method('addEventSubscriber');
        $this->builder->expects($this->never())
            ->method('add');

        $this->extension->buildForm($this->builder, []);
    }

    public function testBuildFormWithFeatureEnabledAndLoggedCustomerUser()
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($this->getEntity(CustomerUser::class, ['id' => 1]));

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->builder->expects($this->exactly(2))
            ->method('addEventSubscriber')
            ->withConsecutive(
                [$this->saveConsentAcceptanceSubscriber],
                [$this->populateFieldCustomerConsentsSubscriber]
            );

        $this->builder->expects($this->once())
            ->method('add')
            ->with(
                CustomerConsentsType::TARGET_FIELDNAME,
                CustomerConsentsType::class,
                [
                    'constraints' => [
                        new RemovedLandingPages(),
                        new RemovedConsents(),
                        new RequiredConsents()
                    ]
                ]
            );

        $this->extension->buildForm($this->builder, []);
    }

    public function testBuildFormWithFeatureEnabledAndAnonymousCustomerUser()
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->guestCustomerConsentsEventSubscriber);

        $this->builder->expects($this->once())
            ->method('add')
            ->with(
                CustomerConsentsType::TARGET_FIELDNAME,
                CustomerConsentsType::class,
                [
                    'constraints' => [
                        new RemovedLandingPages(),
                        new RemovedConsents(),
                        new RequiredConsents()
                    ]
                ]
            );

        $this->extension->buildForm($this->builder, []);
    }

    public function testGetExtendedType()
    {
        $this->assertNull($this->extension->getExtendedType());

        $this->extension->setExtendedType('extended_type');
        $this->assertEquals('extended_type', $this->extension->getExtendedType());
    }
}
