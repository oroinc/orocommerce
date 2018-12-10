<?php

namespace Oro\Bundle\ConsentBundle\Form\Type;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The field that is used for managing customer user accepted consents on the checkout page
 */
class CheckoutCustomerConsentsType extends AbstractType implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const CHECKOUT_OPTION_NAME = 'checkout';

    /** @var ConsentAcceptanceProvider */
    private $consentAcceptanceProvider;

    /** @var CustomerUserExtractor */
    private $customerUserExtractor;

    /**
     * @param ConsentAcceptanceProvider $consentAcceptanceProvider
     * @param CustomerUserExtractor     $customerUserExtractor
     */
    public function __construct(
        ConsentAcceptanceProvider $consentAcceptanceProvider,
        CustomerUserExtractor $customerUserExtractor
    ) {
        $this->consentAcceptanceProvider = $consentAcceptanceProvider;
        $this->customerUserExtractor = $customerUserExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $customerUser = null;
        $checkout = $options[self::CHECKOUT_OPTION_NAME];
        if ($checkout instanceof Checkout) {
            $customerUser = $this->customerUserExtractor->extract($checkout);
        }

        if ($customerUser instanceof CustomerUser) {
            $consentAcceptances = $this->consentAcceptanceProvider->getCustomerConsentAcceptances();
            $builder->setData($consentAcceptances);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                self::CHECKOUT_OPTION_NAME => false,
            ]
        );

        $resolver->addAllowedTypes(
            self::CHECKOUT_OPTION_NAME,
            [
                Checkout::class,
                'null',
                'bool', // we use bool to solve issue with calling buildForm with invalid options
            ]
        );

        $resolver->setDefined(self::CHECKOUT_OPTION_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_checkout_customer_consents';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CustomerConsentsType::class;
    }
}
