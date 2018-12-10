<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\PopulateFieldCustomerConsentsSubscriber;
use Oro\Bundle\ConsentBundle\Form\Extension\CustomerEditConsentsExtension;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerEditConsentsExtensionTest extends FormIntegrationTestCase
{
    /** @var CustomerConsentsEventSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $saveConsentAcceptanceSubscriber;

    /** @var PopulateFieldCustomerConsentsSubscriber|\PHPUnit\Framework\MockObject\MockObject */
    private $populateFieldCustomerConsentsSubscriber;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formBuilder;

    /** @var CustomerEditConsentsExtension */
    private $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->saveConsentAcceptanceSubscriber = $this->createMock(CustomerConsentsEventSubscriber::class);
        $this->populateFieldCustomerConsentsSubscriber = $this->createMock(
            PopulateFieldCustomerConsentsSubscriber::class
        );
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->extension = new CustomerEditConsentsExtension(
            $this->saveConsentAcceptanceSubscriber,
            $this->populateFieldCustomerConsentsSubscriber
        );
        $this->extension->setFeatureChecker($this->featureChecker);
    }

    public function testGetEnabledRequiredConsentsConstraint()
    {
        $this->assertFalse($this->extension->getEnabledRequiredConsentsConstraint());

        $this->extension->setEnabledRequiredConsentsConstraint(true);
        $this->assertTrue($this->extension->getEnabledRequiredConsentsConstraint());

        $this->extension->setEnabledRequiredConsentsConstraint(false);
        $this->assertFalse($this->extension->getEnabledRequiredConsentsConstraint());
    }

    public function testGetExtendedType()
    {
        $this->assertNull($this->extension->getExtendedType());

        $this->extension->setExtendedType('extendedType');
        $this->assertEquals('extendedType', $this->extension->getExtendedType());
    }

    public function testBuildForm()
    {
        $this->extension->addFeature('feature');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->extension->setEnabledRequiredConsentsConstraint(true);

        $this->formBuilder->expects($this->once())
            ->method('add')
            ->with(
                CustomerConsentsType::TARGET_FIELDNAME,
                CustomerConsentsType::class,
                ['constraints' => [new RemovedLandingPages(), new RemovedConsents(), new RequiredConsents()]]
            );

        $this->formBuilder->expects($this->exactly(2))
            ->method('addEventSubscriber')
            ->withConsecutive(
                [$this->saveConsentAcceptanceSubscriber],
                [$this->populateFieldCustomerConsentsSubscriber]
            );

        $this->extension->buildForm($this->formBuilder, []);
    }

    public function testBuildFormFeatureDisabled()
    {
        $this->extension->addFeature('feature');
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $this->extension->setEnabledRequiredConsentsConstraint(true);

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

        $this->extension->setEnabledRequiredConsentsConstraint(false);

        $this->formBuilder->expects($this->once())
            ->method('add')
            ->with(
                CustomerConsentsType::TARGET_FIELDNAME,
                CustomerConsentsType::class,
                ['constraints' => [new RemovedLandingPages(), new RemovedConsents()]]
            );

        $this->formBuilder->expects($this->exactly(2))
            ->method('addEventSubscriber')
            ->withConsecutive(
                [$this->saveConsentAcceptanceSubscriber],
                [$this->populateFieldCustomerConsentsSubscriber]
            );

        $this->extension->buildForm($this->formBuilder, []);
    }
}
