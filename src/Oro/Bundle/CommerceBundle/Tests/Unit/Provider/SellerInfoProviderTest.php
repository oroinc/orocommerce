<?php

declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Tests\Unit\Provider;

use Oro\Bundle\CommerceBundle\DependencyInjection\Configuration;
use Oro\Bundle\CommerceBundle\Provider\SellerInfoProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\TestCase;

final class SellerInfoProviderTest extends TestCase
{
    public function testGetSellerInfoWithoutScope(): void
    {
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects(self::exactly(6))
            ->method('get')
            ->withConsecutive(
                [Configuration::getConfigKeyByName(Configuration::COMPANY_NAME), false, false, null],
                [Configuration::getConfigKeyByName(Configuration::BUSINESS_ADDRESS), false, false, null],
                [Configuration::getConfigKeyByName(Configuration::PHONE_NUMBER), false, false, null],
                [Configuration::getConfigKeyByName(Configuration::CONTACT_EMAIL), false, false, null],
                [Configuration::getConfigKeyByName(Configuration::WEBSITE), false, false, null],
                [Configuration::getConfigKeyByName(Configuration::TAX_ID), false, false, null],
            )
            ->willReturnOnConsecutiveCalls(
                'Acme Inc.',
                '1234 Main St',
                '+123456789',
                'info@acme.com',
                'https://acme.com',
                'TAX123'
            );

        $provider = new SellerInfoProvider($configManager);

        self::assertSame([
            'sellerCompanyName' => 'Acme Inc.',
            'sellerBusinessAddress' => '1234 Main St',
            'sellerPhoneNumber' => '+123456789',
            'sellerContactEmail' => 'info@acme.com',
            'sellerWebsiteURL' => 'https://acme.com',
            'sellerTaxID' => 'TAX123'
        ], $provider->getSellerInfo());
    }

    public function testGetSellerInfoWithScope(): void
    {
        $website = new Website();

        $config = $this->createMock(ConfigManager::class);
        $config->expects(self::exactly(6))
            ->method('get')
            ->withConsecutive(
                [Configuration::getConfigKeyByName(Configuration::COMPANY_NAME), false, false, $website],
                [Configuration::getConfigKeyByName(Configuration::BUSINESS_ADDRESS), false, false, $website],
                [Configuration::getConfigKeyByName(Configuration::PHONE_NUMBER), false, false, $website],
                [Configuration::getConfigKeyByName(Configuration::CONTACT_EMAIL), false, false, $website],
                [Configuration::getConfigKeyByName(Configuration::WEBSITE), false, false, $website],
                [Configuration::getConfigKeyByName(Configuration::TAX_ID), false, false, $website],
            )
            ->willReturnOnConsecutiveCalls(
                'Scoped Inc.',
                '789 Scoped Ave',
                '987-654-3210',
                'scoped@acme.com',
                'https://scoped.acme.com',
                'TAX987'
            );

        $provider = new SellerInfoProvider($config);

        self::assertSame([
            'sellerCompanyName' => 'Scoped Inc.',
            'sellerBusinessAddress' => '789 Scoped Ave',
            'sellerPhoneNumber' => '987-654-3210',
            'sellerContactEmail' => 'scoped@acme.com',
            'sellerWebsiteURL' => 'https://scoped.acme.com',
            'sellerTaxID' => 'TAX987'
        ], $provider->getSellerInfo($website));
    }
}
