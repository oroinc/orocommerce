<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CheckoutCustomerConsentsEventSubscriber;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Validation event subscriber processes only on root forms,
 * we use own type to add event listeners that can use the validation result.
 */
class CustomerConsentTransitionType extends AbstractType implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    /** @var CheckoutCustomerConsentsEventSubscriber */
    private $checkoutCustomerConsentsEventSubscriber;

    /**
     * @param CheckoutCustomerConsentsEventSubscriber $checkoutCustomerConsentsEventSubscriber
     */
    public function __construct(CheckoutCustomerConsentsEventSubscriber $checkoutCustomerConsentsEventSubscriber)
    {
        $this->checkoutCustomerConsentsEventSubscriber = $checkoutCustomerConsentsEventSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $builder->addEventSubscriber($this->checkoutCustomerConsentsEventSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_customer_consents_transition_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return WorkflowTransitionType::class;
    }
}
