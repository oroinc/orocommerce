<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\Extension\FrontendCustomerUserRegistrationExtension;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserRegistrationType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;

class FrontendCustomerUserRegistrationExtensionTest extends FormIntegrationTestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formBuilder;

    /** @var FrontendCustomerUserRegistrationExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->extension = new FrontendCustomerUserRegistrationExtension();
        $this->extension->setFeatureChecker($this->featureChecker);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(FrontendCustomerUserRegistrationType::class, $this->extension->getExtendedType());
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
                ['constraints' => [new RemovedLandingPages(), new RemovedConsents(), new RequiredConsents()]]
            );

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
            ->method('addEventSubscriber');

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
                ['constraints' => [new RemovedLandingPages(), new RemovedConsents(), new RequiredConsents()]]
            );

        $this->extension->buildForm($this->formBuilder, []);
    }
}
