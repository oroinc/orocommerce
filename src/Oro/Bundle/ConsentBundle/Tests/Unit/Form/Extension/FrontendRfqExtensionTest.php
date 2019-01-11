<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\Extension\FrontendRfqExtension;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class FrontendRfqExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var FrontendRfqExtension */
    private $extension;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->extension = new FrontendRfqExtension();

        $this->extension->setFeatureChecker($this->featureChecker);
        $this->extension->addFeature('consents');

        $this->builder = $this->createMock(FormBuilderInterface::class);
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
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents')
            ->willReturn(true);

        $this->builder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SET_DATA,
                [$this->extension, 'preSetData']
            );

        $this->extension->buildForm($this->builder, []);
    }

    public function testPreSetDataForExistingCustomer()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $requestForQuote = new Request();
        $customerUser = new CustomerUser();
        $requestForQuote->setCustomerUser($customerUser);

        $event = new FormEvent($form, $requestForQuote);

        $formOptions = [
            'constraints' => [
                new RemovedLandingPages(),
                new RemovedConsents(),
                new RequiredConsents()
            ],
            'property_path' => 'customerUser.acceptedConsents'
        ];

        $form->expects($this->once())
            ->method('add')
            ->with(
                ConsentAcceptanceType::TARGET_FIELDNAME,
                ConsentAcceptanceType::class,
                $formOptions
            );

        $this->extension->preSetData($event);
    }

    public function testPreSetDataForEmptyCustomer()
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $requestForQuote = new Request();

        $event = new FormEvent($form, $requestForQuote);

        $formOptions = [
            'constraints' => [
                new RemovedLandingPages(),
                new RemovedConsents(),
                new RequiredConsents()
            ],
            'mapped' => false
        ];

        $form->expects($this->once())
            ->method('add')
            ->with(
                ConsentAcceptanceType::TARGET_FIELDNAME,
                ConsentAcceptanceType::class,
                $formOptions
            );

        $this->extension->preSetData($event);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(RequestType::class, $this->extension->getExtendedType());
    }
}
