<?php

declare(strict_types=1);

namespace Oro\Bundle\MoneyOrderBundle\Migration\DataHelper;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Helper methods for check/money order payment method and rule creation in data fixtures.
 */
class MoneyOrderHelper
{
    public function __construct(
        protected ObjectManager $manager,
        protected IntegrationIdentifierGeneratorInterface $identifierGenerator,
        protected ConfigManager $globalConfigManager,
    ) {
    }

    /**
     * @param string[] $currencies A separate payment rule will be created for each currency in this list
     */
    public function createMoneyOrderPaymentMethodAndPaymentRules(
        string $label,
        string $payTo,
        string $sendTo,
        User $owner,
        array $currencies,
        ?string $shortLabel = null,
        ?bool $enablePaymentRules = true,
        ?int $paymentRulesSortOrder = 1,
    ): void {
        $channel = $this->createMoneyOrderPaymentMethod(
            label: $label,
            payTo: $payTo,
            sendTo: $sendTo,
            owner: $owner,
            shortLabel: $shortLabel
        );
        foreach ($currencies as $currency) {
            $this->createMoneyOrderPaymentRule(
                owner: $owner,
                channel: $channel,
                enabled: $enablePaymentRules,
                sortOrder: $paymentRulesSortOrder,
                currency: $currency
            );
        }
    }

    public function createMoneyOrderPaymentMethod(
        string $label,
        string $payTo,
        string $sendTo,
        User $owner,
        ?string $shortLabel = null,
    ): Channel {
        $labelValue = LocalizedFallbackValue::createString($label);

        $transport = (new MoneyOrderSettings())
            ->addLabel($labelValue)
            ->addShortLabel($shortLabel ? LocalizedFallbackValue::createString($shortLabel) : $labelValue)
            ->setPayTo($payTo)
            ->setSendTo($sendTo);

        $channel = (new Channel())
            ->setType(MoneyOrderChannelType::TYPE)
            ->setName($label)
            ->setEnabled(true)
            ->setOrganization($owner->getOrganization())
            ->setDefaultUserOwner($owner)
            ->setTransport($transport);

        $this->manager->persist($channel);
        $this->manager->flush($channel);

        return $channel;
    }

    /**
     * @param string|null $currency If null, a payment rule will be created for the default currency
     */
    public function createMoneyOrderPaymentRule(
        User $owner,
        Channel $channel,
        ?bool $enabled = true,
        ?int $sortOrder = 1,
        ?string $currency = null
    ): PaymentMethodsConfigsRule {
        $methodConfig = (new PaymentMethodConfig())
            ->setType($this->identifierGenerator->generateIdentifier($channel));

        $rule = (new Rule())
            ->setName($channel->getName())
            ->setEnabled($enabled)
            ->setSortOrder($sortOrder);

        if (null === $currency) {
            $currencyConfigKey = CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY);
            $currency = $this->globalConfigManager->get($currencyConfigKey) ?: CurrencyConfig::DEFAULT_CURRENCY;
        }

        $paymentRule = (new PaymentMethodsConfigsRule())
            ->setRule($rule)
            ->setOrganization($owner->getOrganization())
            ->setCurrency($currency)
            ->addMethodConfig($methodConfig);

        $this->manager->persist($paymentRule);
        $this->manager->flush();

        return $paymentRule;
    }
}
