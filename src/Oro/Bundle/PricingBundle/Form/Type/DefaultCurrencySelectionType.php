<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\CurrencyBundle\Config\CurrencyConfigInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguration;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

class DefaultCurrencySelectionType extends CurrencySelectionType
{
    const NAME = 'oro_pricing_default_currency_selection';

    const ENABLED_CURRENCIES_NAME = 'oro_pricing___enabled_currencies';
    const DEFAULT_CURRENCY_NAME = 'oro_pricing___default_currency';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param CurrencyConfigInterface $configManager
     * @param LocaleSettings $localeSettings
     * @param TranslatorInterface $translator
     * @param RequestStack $requestStack
     * @param CurrencyNameHelper $nameHelper
     */
    public function __construct(
        CurrencyConfigInterface $configManager,
        LocaleSettings $localeSettings,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        CurrencyNameHelper $nameHelper
    ) {
        parent::__construct($configManager, $localeSettings, $nameHelper);
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
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
            $pricing = $this->requestStack->getCurrentRequest()->get('pricing');
            $defaultCurrency = $this->getDefaultCurrency($pricing[self::DEFAULT_CURRENCY_NAME]);
            $enabledCurrencies = $this->getEnabledCurrencies($pricing[self::ENABLED_CURRENCIES_NAME]);

            if (!in_array($defaultCurrency, $enabledCurrencies, true)) {
                $currencyName = Intl::getCurrencyBundle()
                    ->getCurrencyName($defaultCurrency, $this->localeSettings->getLocale());

                $form->addError(new FormError(
                    $this->translator->trans(
                        'oro.pricing.validators.is_not_enabled',
                        ['%currency%' => $currencyName],
                        'validators'
                    )
                ));
            }
        }
    }

    /**
     * @param array $defaultCurrencyData
     * @return string
     */
    protected function getDefaultCurrency(array $defaultCurrencyData = [])
    {
        if (isset($defaultCurrencyData['use_parent_scope_value'])) {
            $defaultCurrency = CurrencyConfiguration::DEFAULT_CURRENCY;
        } elseif (isset($defaultCurrencyData['value'])) {
            $defaultCurrency = $defaultCurrencyData['value'];
        } else {
            $defaultCurrency = '';
        }

        return $defaultCurrency;
    }

    /**
     * @param array $enabledCurrenciesData
     * @return array
     */
    protected function getEnabledCurrencies(array $enabledCurrenciesData)
    {
        if (isset($enabledCurrenciesData['use_parent_scope_value'])) {
            $enabledCurrencies = [CurrencyConfiguration::DEFAULT_CURRENCY];
        } elseif (isset($enabledCurrenciesData['value'])) {
            $enabledCurrencies = $enabledCurrenciesData['value'];
        } else {
            $enabledCurrencies = [];
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
