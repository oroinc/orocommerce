<?php

namespace OroB2B\Bundle\PricingBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class SystemCurrencyFormExtension extends AbstractTypeExtension
{
    const ALLOWED_CURRENCIES = 'oro_currency.allowed_currencies';
    const ENABLED_CURRENCIES = 'oro_b2b_pricing.enabled_currencies';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

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
        $this->configManager = $configManager;
        $this->localeSettings = $localeSettings;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_currency';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $restrict = $form->getConfig()->getOption('restrict');
        $enabledCurrencies = $this->configManager->get('oro_b2b_pricing.enabled_currencies');

        if ($restrict && $enabledCurrencies) {
            $allowedCurrencies = (array) $form->getData();
            $alreadyInUse = array_diff($enabledCurrencies, $allowedCurrencies);

            if ($alreadyInUse) {
                $form->addError(new FormError(
                    $this->translator->transChoice(
                        'orob2b.pricing.validators.using_as_available',
                        count($alreadyInUse),
                        ['%curr%' => implode(', ', $this->getCurrencyNames($alreadyInUse))],
                        'validators'
                    )
                ));
            }
        }
    }

    /**
     * @param array $currencies
     * @return array
     */
    protected function getCurrencyNames(array $currencies)
    {
        $currencyNames = Intl::getCurrencyBundle()->getCurrencyNames($this->localeSettings->getLocale());

        return array_intersect_key($currencyNames, array_fill_keys($currencies, null));
    }
}
