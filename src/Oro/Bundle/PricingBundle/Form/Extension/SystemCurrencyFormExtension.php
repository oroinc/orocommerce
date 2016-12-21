<?php

namespace Oro\Bundle\PricingBundle\Form\Extension;

use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

class SystemCurrencyFormExtension extends AbstractTypeExtension
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var CurrencyProviderInterface
     */
    protected $currencyProvider;

    /**
     * @param LocaleSettings $localeSettings
     * @param TranslatorInterface $translator
     * @param CurrencyProviderInterface $currencyProvider
     */
    public function __construct(
        LocaleSettings $localeSettings,
        TranslatorInterface $translator,
        CurrencyProviderInterface $currencyProvider
    ) {
        $this->localeSettings = $localeSettings;
        $this->translator = $translator;
        $this->currencyProvider = $currencyProvider;
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
        $enabledCurrencies = $this->currencyProvider->getCurrencyList();

        if ($restrict && $enabledCurrencies) {
            $allowedCurrencies = (array) $form->getData();
            $alreadyInUse = array_diff($enabledCurrencies, $allowedCurrencies);

            if ($alreadyInUse) {
                $form->addError(new FormError(
                    $this->translator->transChoice(
                        'oro.pricing.validators.using_as_available',
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
