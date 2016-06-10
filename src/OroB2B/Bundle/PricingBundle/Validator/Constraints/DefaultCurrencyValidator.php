<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class DefaultCurrencyValidator extends ConstraintValidator
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string $value
     * @param ProductPriceCurrency $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $currencies = $this->configManager->get('oro_b2b_pricing.enabled_currencies', []);

        if (!in_array($value, $currencies, true)) {
            $this->context->addViolation($constraint->message, ['%invalidCurrency%' => $value]);
        }

    }
}
