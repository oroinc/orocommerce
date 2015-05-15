<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class CurrencySelectionType extends AbstractType
{
    const NAME = 'oro_currency_selection';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @param ConfigManager $configManager
     * @param LocaleSettings $localeSettings
     */
    public function __construct(ConfigManager $configManager, LocaleSettings $localeSettings)
    {
        $this->configManager = $configManager;
        $this->localeSettings = $localeSettings;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                if ($options['currencies_list'] !== null && !is_array($options['currencies_list'])
                        || is_array($options['currencies_list']) && empty($options['currencies_list'])
                ) {
                    throw new LogicException('The option "currencies_list" must be null or not empty array.');
                }

                if (count($options['currencies_list'])) {
                    $currencies = $options['currencies_list'];
                } else {
                    $currencies = $this->configManager->get('oro_currency.allowed_currencies');
                }

                if (empty($currencies)) {
                    //TODO: Change the getting currency list from system configuration option
                    //TODO: "functional currency of organization" when it will be added.
                    $currencies = [$this->localeSettings->getCurrency()];
                }

                $this->checkCurrencies($currencies);

                return $this->getChoices($currencies, $options['compact']);
            },
            'compact' => false,
            'currencies_list' => null,
        ]);
    }

    /**
     * @param array $currencies
     * @param boolean $isCompact
     * @return array
     */
    protected function getChoices(array $currencies, $isCompact)
    {
        if ($isCompact) {
            $choices = array_combine($currencies, $currencies);
        } else {
            $currencyNames = Intl::getCurrencyBundle()->getCurrencyNames($this->localeSettings->getLocale());

            $choices = array_intersect_key($currencyNames, array_fill_keys($currencies, null));
        }

        return $choices;
    }

    /**
     * @param array $currencies
     * @throws LogicException
     */
    protected function checkCurrencies(array $currencies)
    {
        $invalidCurrencies = [];

        foreach ($currencies as $currency) {
            $name = Intl::getCurrencyBundle()->getCurrencyName($currency, $this->localeSettings->getLocale());

            if (!$name) {
                $invalidCurrencies[] = $currency;
            }
        }

        if (!empty($invalidCurrencies)) {
            throw new LogicException(sprintf('Found unknown currencies: %s.', implode(', ', $invalidCurrencies)));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
