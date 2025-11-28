<?php

declare(strict_types=1);

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Migration\DataHelper;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfig;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\MoneyOrderBundle\Entity\MoneyOrderSettings;
use Oro\Bundle\MoneyOrderBundle\Integration\MoneyOrderChannelType;
use Oro\Bundle\MoneyOrderBundle\Migration\DataHelper\MoneyOrderHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MoneyOrderHelperTest extends TestCase
{
    private ObjectManager|MockObject $manager;
    private IntegrationIdentifierGeneratorInterface|MockObject $identifierGenerator;
    private ConfigManager|MockObject $globalConfigManager;
    private MoneyOrderHelper $helper;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->globalConfigManager = $this->createMock(ConfigManager::class);

        $this->helper = new MoneyOrderHelper(
            $this->manager,
            $this->identifierGenerator,
            $this->globalConfigManager
        );
    }

    public function testCreateMoneyOrderPaymentMethod(): void
    {
        $label = 'Check/Money Order';
        $payTo = 'ACME Corp';
        $sendTo = '123 Main St';
        $shortLabel = 'Check';

        $organization = new Organization();
        $owner = new User();
        $owner->setOrganization($organization);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Channel::class));

        $this->manager->expects($this->once())
            ->method('flush')
            ->with($this->isInstanceOf(Channel::class));

        $channel = $this->helper->createMoneyOrderPaymentMethod(
            label: $label,
            payTo: $payTo,
            sendTo: $sendTo,
            owner: $owner,
            shortLabel: $shortLabel
        );

        $this->assertEquals(MoneyOrderChannelType::TYPE, $channel->getType());
        $this->assertEquals($label, $channel->getName());
        $this->assertTrue($channel->isEnabled());
        $this->assertSame($organization, $channel->getOrganization());
        $this->assertSame($owner, $channel->getDefaultUserOwner());

        $transport = $channel->getTransport();
        $this->assertInstanceOf(MoneyOrderSettings::class, $transport);
        $this->assertEquals($payTo, $transport->getPayTo());
        $this->assertEquals($sendTo, $transport->getSendTo());
        $this->assertCount(1, $transport->getLabels());
        $this->assertEquals($label, $transport->getLabels()->first()->getString());
        $this->assertCount(1, $transport->getShortLabels());
        $this->assertEquals($shortLabel, $transport->getShortLabels()->first()->getString());
    }

    public function testCreateMoneyOrderPaymentMethodWithoutShortLabel(): void
    {
        $label = 'Check/Money Order';
        $payTo = 'ACME Corp';
        $sendTo = '123 Main St';

        $organization = new Organization();
        $owner = new User();
        $owner->setOrganization($organization);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Channel::class));

        $this->manager->expects($this->once())
            ->method('flush')
            ->with($this->isInstanceOf(Channel::class));

        $channel = $this->helper->createMoneyOrderPaymentMethod(
            label: $label,
            payTo: $payTo,
            sendTo: $sendTo,
            owner: $owner
        );

        $transport = $channel->getTransport();
        $this->assertInstanceOf(MoneyOrderSettings::class, $transport);
        $this->assertCount(1, $transport->getShortLabels());
        $this->assertEquals($label, $transport->getShortLabels()->first()->getString());
    }

    public function testCreateMoneyOrderPaymentRuleWithCurrency(): void
    {
        $currency = 'EUR';
        $methodIdentifier = 'money_order_1';

        $organization = new Organization();
        $owner = new User();
        $owner->setOrganization($organization);

        $channel = new Channel();
        $channel->setName('Test Payment Method');

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($methodIdentifier);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PaymentMethodsConfigsRule::class));

        $this->manager->expects($this->once())
            ->method('flush');

        $paymentRule = $this->helper->createMoneyOrderPaymentRule(
            owner: $owner,
            channel: $channel,
            currency: $currency
        );

        $this->assertSame($organization, $paymentRule->getOrganization());
        $this->assertEquals($currency, $paymentRule->getCurrency());

        $rule = $paymentRule->getRule();
        $this->assertInstanceOf(Rule::class, $rule);
        $this->assertEquals($channel->getName(), $rule->getName());
        $this->assertTrue($rule->isEnabled());
        $this->assertEquals(1, $rule->getSortOrder());

        $methodConfigs = $paymentRule->getMethodConfigs();
        $this->assertCount(1, $methodConfigs);
        $methodConfig = $methodConfigs->first();
        $this->assertInstanceOf(PaymentMethodConfig::class, $methodConfig);
        $this->assertEquals($methodIdentifier, $methodConfig->getType());
    }

    public function testCreateMoneyOrderPaymentRuleWithoutCurrency(): void
    {
        $defaultCurrency = 'USD';
        $methodIdentifier = 'money_order_1';

        $organization = new Organization();
        $owner = new User();
        $owner->setOrganization($organization);

        $channel = new Channel();
        $channel->setName('Test Payment Method');

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($methodIdentifier);

        $currencyConfigKey = CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY);
        $this->globalConfigManager->expects($this->once())
            ->method('get')
            ->with($currencyConfigKey)
            ->willReturn($defaultCurrency);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PaymentMethodsConfigsRule::class));

        $this->manager->expects($this->once())
            ->method('flush');

        $paymentRule = $this->helper->createMoneyOrderPaymentRule(
            owner: $owner,
            channel: $channel
        );

        $this->assertEquals($defaultCurrency, $paymentRule->getCurrency());
    }

    public function testCreateMoneyOrderPaymentRuleWithoutCurrencyFallsBackToDefault(): void
    {
        $methodIdentifier = 'money_order_1';

        $organization = new Organization();
        $owner = new User();
        $owner->setOrganization($organization);

        $channel = new Channel();
        $channel->setName('Test Payment Method');

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($methodIdentifier);

        $currencyConfigKey = CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY);
        $this->globalConfigManager->expects($this->once())
            ->method('get')
            ->with($currencyConfigKey)
            ->willReturn(null);

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PaymentMethodsConfigsRule::class));

        $this->manager->expects($this->once())
            ->method('flush');

        $paymentRule = $this->helper->createMoneyOrderPaymentRule(
            owner: $owner,
            channel: $channel
        );

        $this->assertEquals(CurrencyConfig::DEFAULT_CURRENCY, $paymentRule->getCurrency());
    }

    public function testCreateMoneyOrderPaymentRuleWithCustomEnabledAndSortOrder(): void
    {
        $methodIdentifier = 'money_order_1';
        $enabled = false;
        $sortOrder = 10;

        $organization = new Organization();
        $owner = new User();
        $owner->setOrganization($organization);

        $channel = new Channel();
        $channel->setName('Test Payment Method');

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($methodIdentifier);

        $currencyConfigKey = CurrencyConfig::getConfigKeyByName(CurrencyConfig::KEY_DEFAULT_CURRENCY);
        $this->globalConfigManager->expects($this->once())
            ->method('get')
            ->with($currencyConfigKey)
            ->willReturn('USD');

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PaymentMethodsConfigsRule::class));

        $this->manager->expects($this->once())
            ->method('flush');

        $paymentRule = $this->helper->createMoneyOrderPaymentRule(
            owner: $owner,
            channel: $channel,
            enabled: $enabled,
            sortOrder: $sortOrder
        );

        $rule = $paymentRule->getRule();
        $this->assertFalse($rule->isEnabled());
        $this->assertEquals($sortOrder, $rule->getSortOrder());
    }

    public function testCreateMoneyOrderPaymentMethodAndPaymentRules(): void
    {
        $label = 'Check/Money Order';
        $payTo = 'ACME Corp';
        $sendTo = '123 Main St';
        $shortLabel = 'Check';
        $currencies = ['USD', 'EUR', 'GBP'];

        $organization = new Organization();
        $owner = new User();
        $owner->setOrganization($organization);

        $this->manager->expects($this->exactly(4))
            ->method('persist');

        $this->manager->expects($this->exactly(4))
            ->method('flush');

        $this->identifierGenerator->expects($this->exactly(3))
            ->method('generateIdentifier')
            ->willReturn('money_order_1');

        $this->helper->createMoneyOrderPaymentMethodAndPaymentRules(
            label: $label,
            payTo: $payTo,
            sendTo: $sendTo,
            owner: $owner,
            currencies: $currencies,
            shortLabel: $shortLabel
        );
    }

    public function testCreateMoneyOrderPaymentMethodAndPaymentRulesWithDisabledRules(): void
    {
        $label = 'Check/Money Order';
        $payTo = 'ACME Corp';
        $sendTo = '123 Main St';
        $currencies = ['USD', 'EUR'];
        $enablePaymentRules = false;

        $organization = new Organization();
        $owner = new User();
        $owner->setOrganization($organization);

        $this->manager->expects($this->exactly(3))
            ->method('persist');

        $this->manager->expects($this->exactly(3))
            ->method('flush');

        $this->identifierGenerator->expects($this->exactly(2))
            ->method('generateIdentifier')
            ->willReturn('money_order_1');

        $this->helper->createMoneyOrderPaymentMethodAndPaymentRules(
            label: $label,
            payTo: $payTo,
            sendTo: $sendTo,
            owner: $owner,
            currencies: $currencies,
            enablePaymentRules: $enablePaymentRules
        );
    }

    public function testCreateMoneyOrderPaymentMethodAndPaymentRulesWithCustomSortOrder(): void
    {
        $label = 'Check/Money Order';
        $payTo = 'ACME Corp';
        $sendTo = '123 Main St';
        $currencies = ['USD'];
        $paymentRulesSortOrder = 5;

        $organization = new Organization();
        $owner = new User();
        $owner->setOrganization($organization);

        $this->manager->expects($this->exactly(2))
            ->method('persist');

        $this->manager->expects($this->exactly(2))
            ->method('flush');

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->willReturn('money_order_1');

        $this->helper->createMoneyOrderPaymentMethodAndPaymentRules(
            label: $label,
            payTo: $payTo,
            sendTo: $sendTo,
            owner: $owner,
            currencies: $currencies,
            paymentRulesSortOrder: $paymentRulesSortOrder
        );
    }
}
