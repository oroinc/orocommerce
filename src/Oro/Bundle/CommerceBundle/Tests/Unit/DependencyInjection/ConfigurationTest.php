<?php

namespace Oro\Bundle\CommerceBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CommerceBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testProcessConfiguration(): void
    {
        self::assertSame(
            [
                'settings' => [
                    'resolved' => true,
                    Configuration::COMPANY_NAME => ['value' => null, 'scope' => 'app'],
                    Configuration::BUSINESS_ADDRESS => ['value' => null, 'scope' => 'app'],
                    Configuration::PHONE_NUMBER => ['value' => null, 'scope' => 'app'],
                    Configuration::CONTACT_EMAIL => ['value' => null, 'scope' => 'app'],
                    Configuration::WEBSITE => ['value' => null, 'scope' => 'app'],
                    Configuration::TAX_ID => ['value' => null, 'scope' => 'app'],
                ],
            ],
            $this->processConfiguration([])
        );
    }

    private function processConfiguration(array $configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }
}
