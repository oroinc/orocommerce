<?php

namespace Oro\Bundle\ConsentBundle\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\PopulateFieldCustomerConsentsSubscriber;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Generic form extension that adds customer consents field to the subscribed forms
 */
class CustomerEditConsentsExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var bool */
    private $enabledRequiredConsentsConstraint = false;

    /** @var string */
    private $extendedType;

    /** @var CustomerConsentsEventSubscriber */
    private $saveConsentAcceptanceSubscriber;

    /** @var PopulateFieldCustomerConsentsSubscriber */
    private $populateFieldCustomerConsentsSubscriber;

    /**
     * @param CustomerConsentsEventSubscriber $saveConsentAcceptanceSubscriber
     * @param PopulateFieldCustomerConsentsSubscriber $populateFieldCustomerConsentsSubscriber
     */
    public function __construct(
        CustomerConsentsEventSubscriber $saveConsentAcceptanceSubscriber,
        PopulateFieldCustomerConsentsSubscriber $populateFieldCustomerConsentsSubscriber
    ) {
        $this->saveConsentAcceptanceSubscriber = $saveConsentAcceptanceSubscriber;
        $this->populateFieldCustomerConsentsSubscriber = $populateFieldCustomerConsentsSubscriber;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabledRequiredConsentsConstraint(bool $enabled)
    {
        $this->enabledRequiredConsentsConstraint = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabledRequiredConsentsConstraint()
    {
        return $this->enabledRequiredConsentsConstraint;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $constraints = [
            new RemovedLandingPages(),
            new RemovedConsents()
        ];

        if ($this->getEnabledRequiredConsentsConstraint()) {
            $constraints[] = new RequiredConsents();
        }

        $builder->add(
            CustomerConsentsType::TARGET_FIELDNAME,
            CustomerConsentsType::class,
            [
                'constraints' => $constraints
            ]
        );

        $builder->addEventSubscriber($this->saveConsentAcceptanceSubscriber);
        $builder->addEventSubscriber($this->populateFieldCustomerConsentsSubscriber);
    }

    /**
     * @param string $extendedType
     */
    public function setExtendedType($extendedType)
    {
        $this->extendedType = $extendedType;
    }

    /**
     * @return string
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }
}
