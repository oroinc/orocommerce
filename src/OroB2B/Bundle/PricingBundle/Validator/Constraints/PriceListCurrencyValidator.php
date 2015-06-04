<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListCurrencyValidator extends ConstraintValidator
{
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
     * @param PriceList|object $value
     * @param PriceListCurrency $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof PriceList) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    'OroB2B\Bundle\PricingBundle\Entity\PriceList',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $availableCurrencies = $this->getAvailableCurrencies($constraint);
        $invalidCurrencies = array_diff($value->getCurrencies(), $availableCurrencies);
        if ($invalidCurrencies) {
            $labels = array_map(
                function ($currencyCode) {
                    $label = Intl::getCurrencyBundle()->getCurrencyName($currencyCode);
                    if (!$label) {
                        return $currencyCode;
                    }

                    return sprintf('%s [%s]', $label, $currencyCode);
                },
                $invalidCurrencies
            );

            $this->context->addViolationAt(
                'currencies',
                $constraint->message,
                ['%invalidCurrencies%' => implode(', ', $labels)],
                implode(', ', $invalidCurrencies),
                count($invalidCurrencies)
            );
        }
    }

    /**
     * @param PriceListCurrency $constraint
     * @return array
     */
    protected function getAvailableCurrencies(PriceListCurrency $constraint)
    {
        if ($constraint->useIntl) {
            $availableCurrencies = array_keys(Intl::getCurrencyBundle()->getCurrencyNames());
        } else {
            $availableCurrencies = (array)$this->configManager->get('oro_currency.allowed_currencies');
        }

        if (!$availableCurrencies) {
            $availableCurrencies = [$this->localeSettings->getCurrency()];
        }

        return $availableCurrencies;
    }
}
