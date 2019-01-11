<?php

namespace Oro\Bundle\ConsentBundle\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserRegistrationType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Extends FrontendCustomerUserRegistrationType with consents field
 */
class FrontendCustomerUserRegistrationExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

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
            new RemovedConsents(),
            new RequiredConsents()
        ];

        $builder->add(
            ConsentAcceptanceType::TARGET_FIELDNAME,
            ConsentAcceptanceType::class,
            [
                'constraints' => $constraints
            ]
        );
    }

    /**
     * @return string
     */
    public function getExtendedType()
    {
        return FrontendCustomerUserRegistrationType::class;
    }
}
