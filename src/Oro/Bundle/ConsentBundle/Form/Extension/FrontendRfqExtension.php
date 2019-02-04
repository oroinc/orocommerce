<?php

namespace Oro\Bundle\ConsentBundle\Form\Extension;

use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedConsents;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RemovedLandingPages;
use Oro\Bundle\ConsentBundle\Validator\Constraints\RequiredConsents;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Extends `Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType` form type by adding consents field
 * Consider it is used both for LOGGED CustomerUser as well as GUEST
 */
class FrontendRfqExtension extends AbstractTypeExtension implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
    }

    /**
     * PreSet event handler
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $formData = $event->getData();
        $form = $event->getForm();

        $formOptions = ['constraints' => [
            new RemovedLandingPages(),
            new RemovedConsents(),
            new RequiredConsents()
        ]];

        // If we have CustomerUser in Request entity we can use corresponding property path so symfony populates and
        // saves consents by its own
        if ($formData instanceof Request && $formData->getCustomerUser()) {
            $formOptions['property_path'] = 'customerUser.acceptedConsents';
        // Otherwise we disable mapping and consider this is Guest (with empty CustomerUser) so we don't need pre
        // population and saving will be handled by `RfqBeforeFlushFormListener`
        } else {
            $formOptions['mapped'] = false;
        }

        $form->add(
            ConsentAcceptanceType::TARGET_FIELDNAME,
            ConsentAcceptanceType::class,
            $formOptions
        );
    }

    /**
     * @return string
     */
    public function getExtendedType()
    {
        return RequestType::class;
    }
}
