<?php

declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\Provider;

use Oro\Bundle\CommerceBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provides seller-related details (e.g. company name, contact, address, tax ID)
 * from system configuration, with optional support for scope identifier (e.g. website, organization or its ID):
 *
 * * sellerCompanyName
 * * sellerBusinessAddress
 * * sellerPhoneNumber
 * * sellerContactEmail
 * * sellerWebsiteURL
 * * sellerTaxID
 */
class SellerInfoProvider
{
    public function __construct(
        private readonly ConfigManager $configManager,
    ) {
    }

    /**
     * Returns seller details from system configuration.
     *
     * @param object|int|null $scopeIdentifier System config scope identifier (e.g. website, organization or its ID)
     *
     * @return array{
     *     sellerCompanyName: string|null,
     *     sellerBusinessAddress: string|null,
     *     sellerPhoneNumber: string|null,
     *     sellerContactEmail: string|null,
     *     sellerWebsiteURL: string|null,
     *     sellerTaxID: string|null
     * }
     */
    public function getSellerInfo(object|int|null $scopeIdentifier = null): array
    {
        $configKeys = [
            'sellerCompanyName' => Configuration::COMPANY_NAME,
            'sellerBusinessAddress' => Configuration::BUSINESS_ADDRESS,
            'sellerPhoneNumber' => Configuration::PHONE_NUMBER,
            'sellerContactEmail' => Configuration::CONTACT_EMAIL,
            'sellerWebsiteURL' => Configuration::WEBSITE,
            'sellerTaxID' => Configuration::TAX_ID,
        ];

        $sellerInfo = [];
        foreach ($configKeys as $key => $configName) {
            $sellerInfo[$key] = $this->getConfigValue($configName, $scopeIdentifier);
        }

        return $sellerInfo;
    }

    private function getConfigValue(string $configName, object|int|null $scopeIdentifier = null): ?string
    {
        return $this->configManager->get(
            Configuration::getConfigKeyByName($configName),
            false,
            false,
            $scopeIdentifier
        );
    }
}
