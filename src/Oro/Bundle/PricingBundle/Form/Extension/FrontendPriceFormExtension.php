<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendPriceFormExtension extends AbstractTypeExtension
{
    /**
     * @var FrontendHelper
     */
    private $frontendHelper;

    /**
     * @var UserCurrencyManager
     */
    private $currencyManager;

    public function __construct(FrontendHelper $frontendHelper, UserCurrencyManager $currencyManager)
    {
        $this->frontendHelper = $frontendHelper;
        $this->currencyManager = $currencyManager;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->frontendHelper->isFrontendRequest()) {
            return;
        }

        $builder->get('currency')
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $selectedCurrency = $event->getData();
                if (empty($selectedCurrency)) {
                    $selectedCurrency = $this->currencyManager->getUserCurrency();
                    $event->setData($selectedCurrency);
                }
            }, 250);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            $resolver->setDefaults([
                'additional_currencies' => null,
                'currencies_list' => $this->currencyManager->getAvailableCurrencies(),
                'default_currency' => $this->currencyManager->getUserCurrency(),
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [PriceType::class];
    }
}
