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
     * @var array
     */
    protected $currencies = [];

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param ConfigManager $configManager
     * @param LocaleSettings $localeSettings
     */
    public function __construct(ConfigManager $configManager, LocaleSettings $localeSettings)
    {
        $this->currencies = $configManager->get('oro_currency.allowed_currencies');

        if (empty($this->currencies)) {
            //TODO: Change the getting currency list from system configuration option
            //TODO: "functional currency of organization" when it will be added.
            $this->currencies = [$localeSettings->getCurrency()];
        }

        $this->locale = $localeSettings->getLocale();
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                if ($options['currencies_list'] !== null && !is_array($options['currencies_list'])) {
                    throw new LogicException('The option "currencies_list" must be array.');
                }

                $currencies = count($options['currencies_list']) ? $options['currencies_list'] : $this->currencies;

                return $this->getCurrencies($currencies, $options['compact']);
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
    protected function getCurrencies(array $currencies, $isCompact)
    {
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
