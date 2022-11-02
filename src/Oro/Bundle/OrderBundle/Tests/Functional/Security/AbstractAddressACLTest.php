<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Security;

use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractAddressACLTest extends WebTestCase
{
    use RolePermissionExtension;

    protected const ROLE = 'ROLE_ADMINISTRATOR';

    /**
     * @param Crawler $crawler
     * @param string $formName
     * @param string $addressType
     * @param array $expected
     */
    protected function checkAddresses(Crawler $crawler, $formName, $addressType, array $expected)
    {
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

    /**
     * @param string $entityClass
     * @param int $accessLevel
     */
    protected function setEntityPermissions($entityClass, $accessLevel)
    {
        $this->updateRolePermission(static::ROLE, $entityClass, $accessLevel);
    }

    /**
     * @param string $actionId
     * @param bool $value
     */
    protected function setActionPermissions($actionId, $value)
    {
        $this->updateRolePermissionForAction(static::ROLE, $actionId, $value);
    }
}
