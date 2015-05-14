<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class CurrencySelectionType extends AbstractType
{
    const NAME = 'oro_currency_selection';

    /**
     * @var array
     */
    protected $currencies = [];

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param ConfigManager $configManager
     * @param string $locale
     */
    public function __construct(ConfigManager $configManager, $locale)
    {
        $this->currencies = $configManager->get('oro_currency.allowed_currencies');
        $this->locale = $locale;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                return $this->getCurrencies($options['currencies_list'], $options['compact']);
            },
            'compact' => false,
            'currencies_list' => [],
        ]);
    }

    /**
     * @param array $currenciesList
     * @param boolean $isCompact
     * @return array
     */
    protected function getCurrencies(array $currenciesList, $isCompact)
    {
        $currencies = count($currenciesList) ? $currenciesList : $this->currencies;

        $this->checkCurrencies($currencies);

        if ($isCompact) {
            $currencies = array_combine($currencies, $currencies);
        } else {
            $currencies = array_intersect_key(
                Intl::getCurrencyBundle()->getCurrencyNames($this->locale),
                array_fill_keys($currencies, null)
            );
        }

        return $currencies;
    }

    /**
     * @param array $currencies
     * @throws LogicException
     */
    protected function checkCurrencies(array $currencies)
    {
        $invalidCurrencies = [];

        foreach ($currencies as $currency) {
            $name = Intl::getCurrencyBundle()->getCurrencyName($currency, $this->locale);

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