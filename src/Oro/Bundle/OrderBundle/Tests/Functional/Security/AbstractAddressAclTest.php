<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Security;

use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractAddressAclTest extends WebTestCase
{
    use RolePermissionExtension;

    protected const ROLE = 'ROLE_ADMINISTRATOR';

    protected function checkAddresses(
        Crawler $crawler,
        string $formName,
        string $addressType,
        array $expected
    ): void {
        if ($expected['manually']) {
            $filter = sprintf('select[name="%s[%s][customerAddress]"]', $formName, $addressType);
            $customerAddressSelector = $crawler->filter($filter)->html();

            static::assertStringContainsString('Enter other address', $customerAddressSelector);
        }

        // Check customer addresses
        if (!empty($expected['customer'])) {
            $filter = sprintf(
                'select[name="%s[%s][customerAddress]"] optgroup[label="Customer Address Book"]',
                $formName,
                $addressType
            );
            $customerAddresses = $crawler->filter($filter)->html();

            foreach ($expected['customer'] as $customerAddress) {
                static::assertStringContainsString($customerAddress, $customerAddresses);
            }
        }

        // Check customer users addresses
        if (!empty($expected['customerUser'])) {
            $filter = sprintf(
                'select[name="%s[%s][customerAddress]"] optgroup[label="User Address Book"]',
                $formName,
                $addressType
            );
            $customerUserAddresses = $crawler->filter($filter)->html();

            foreach ($expected['customerUser'] as $customerUserAddress) {
                static::assertStringContainsString($customerUserAddress, $customerUserAddresses);
            }
        }
    }

    protected function setEntityPermissions(string $entityClass, int $accessLevel): void
    {
        $this->updateRolePermission(static::ROLE, $entityClass, $accessLevel);
    }

    protected function setActionPermissions(string $actionId, bool $value): void
    {
        $this->updateRolePermissionForAction(static::ROLE, $actionId, $value);
    }
}
