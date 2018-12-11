<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\Extension\FrontendCustomerUserProfileExtension;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserProfileType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;

class FrontendCustomerUserProfileExtensionTest extends FormIntegrationTestCase
{
    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formBuilder;

    /** @var FrontendCustomerUserProfileExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->extension = new FrontendCustomerUserProfileExtension();
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
                ['constraints' => [new RemovedLandingPages(), new RemovedConsents()]]
            );

        $this->extension->buildForm($this->formBuilder, []);
    }
}
