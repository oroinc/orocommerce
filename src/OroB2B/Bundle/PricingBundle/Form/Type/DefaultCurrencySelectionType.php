<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration as LocaleConfiguration;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

class DefaultCurrencySelectionType extends CurrencySelectionType
{
    const NAME = 'orob2b_pricing_default_currency_selection';

    const ENABLED_CURRENCIES_NAME = 'oro_b2b_pricing___enabled_currencies';
    const DEFAULT_CURRENCY_NAME = 'oro_b2b_pricing___default_currency';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ConfigManager $configManager
     * @param LocaleSettings $localeSettings
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigManager $configManager,
        LocaleSettings $localeSettings,
        TranslatorInterface $translator
    ) {
        parent::__construct($configManager, $localeSettings);
        $this->translator = $translator;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $rootForm = $form->getRoot();

        if ($this->isSyncApplicable($rootForm)) {
            $defaultCurrency = $this->getDefaultCurrency($rootForm);
            $enabledCurrencies = $this->getEnabledCurrencies($rootForm);

            if (!in_array($defaultCurrency, $enabledCurrencies, true)) {
                $currencyName = Intl::getCurrencyBundle()
                    ->getCurrencyName($defaultCurrency, $this->localeSettings->getLocale());

                $form->addError(new FormError(
                    $this->translator->trans(
                        'orob2b.pricing.validators.is_not_enabled',
                        ['%currency%' => $currencyName],
                        'validators'
                    )
                ));
            }
        }
    }

    /**
     * @param FormInterface $form
     * @return string
     */
    protected function getDefaultCurrency(FormInterface $form)
    {
        $defaultCurrencyData = $form->get(self::DEFAULT_CURRENCY_NAME)->getData();

        if ($defaultCurrencyData['use_parent_scope_value']) {
            $defaultCurrency = LocaleConfiguration::DEFAULT_CURRENCY;
        } else {
            $defaultCurrency = $defaultCurrencyData['value'];
        }

        return $defaultCurrency;
    }

    /**
     * @param FormInterface $form
     * @return array
     */
    protected function getEnabledCurrencies(FormInterface $form)
    {
        $enabledCurrenciesData = $form->get(self::ENABLED_CURRENCIES_NAME)->getData();

        if ($enabledCurrenciesData['use_parent_scope_value']) {
            $enabledCurrencies = [LocaleConfiguration::DEFAULT_CURRENCY];
        } else {
            $enabledCurrencies = $enabledCurrenciesData['value'];
        }

        return $enabledCurrencies;
    }

    /**
     * @param FormInterface $rootForm
     * @return bool
     */
    protected function isSyncApplicable(FormInterface $rootForm)
    {
        return $rootForm && $rootForm->getName() == 'pricing' && $rootForm->has(self::ENABLED_CURRENCIES_NAME);
    }
}
