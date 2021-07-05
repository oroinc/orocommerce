<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SwitchPricingStorageCommandTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    public function testExecute(): void
    {
        $storageSwitch = [
            'flat' => 'combined',
            'combined' => 'flat'
        ];

        static::bootKernel();
        $configManager = self::getConfigManager(null);
        $currentStorage = $configManager->get('oro_pricing.price_storage');

        // Check unknown storage
        $output = $this->runCommand('oro:price-lists:switch-pricing-storage', ['unknown']);
        $this->assertEquals($currentStorage, $configManager->get('oro_pricing.price_storage'));
        $this->assertStringContainsString(
            sprintf('Unknown storage "%s". Possible storage options are: flat, combined', 'unknown'),
            $output
        );

        // Check that it's impossible to switch to already selected storage
        $output = $this->runCommand('oro:price-lists:switch-pricing-storage', [$currentStorage]);
        $this->assertStringContainsString(
            sprintf('Pricing storage "%s" already selected.', $currentStorage),
            $output
        );

        // Switch to new storage
        $newStorage = $storageSwitch[$currentStorage];
        $output = $this->runCommand('oro:price-lists:switch-pricing-storage', [$newStorage]);
        $this->assertEquals($newStorage, $configManager->get('oro_pricing.price_storage'));
        $this->assertStringContainsString(
            sprintf('Pricing storage was successfully switched to "%s"', $newStorage),
            $output
        );

        // Switch back to the current storage
        $output = $this->runCommand('oro:price-lists:switch-pricing-storage', [$currentStorage]);
        $this->assertEquals($currentStorage, $configManager->get('oro_pricing.price_storage'));
        $this->assertStringContainsString(
            sprintf('Pricing storage was successfully switched to "%s"', $currentStorage),
            $output
        );
    }
}
