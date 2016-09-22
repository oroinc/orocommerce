<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Security;

use Symfony\Component\DomCrawler\Crawler;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
abstract class AbstractAddressACLTest extends WebTestCase
{
    /** @var Role */
    protected $role;

    /**
     * @param Crawler $crawler
     * @param string $formName
     * @param string $addressType
     * @param array $expected
     */
    protected function checkAddresses(Crawler $crawler, $formName, $addressType, array $expected)
    {
        if ($expected['manually']) {
            $filter = sprintf('select[name="%s[%s][accountAddress]"]', $formName, $addressType);
            $accountAddressSelector = $crawler->filter($filter)->html();

            $this->assertContains('Enter other address', $accountAddressSelector);
        }

        // Check account addresses
        if (!empty($expected['account'])) {
            $filter = sprintf(
                'select[name="%s[%s][accountAddress]"] optgroup[label="Global Address Book"]',
                $formName,
                $addressType
            );
            $accountAddresses = $crawler->filter($filter)->html();

            foreach ($expected['account'] as $accountAddress) {
                $this->assertContains($accountAddress, $accountAddresses);
            }
        }

        // Check account users addresses
        if (!empty($expected['accountUser'])) {
            $filter = sprintf(
                'select[name="%s[%s][accountAddress]"] optgroup[label="My Address Book"]',
                $formName,
                $addressType
            );
            $accountUserAddresses = $crawler->filter($filter)->html();

            foreach ($expected['accountUser'] as $accountUserAddress) {
                $this->assertContains($accountUserAddress, $accountUserAddresses);
            }
        }
    }

    /**
     * @param int $level
     * @param AclPrivilegeIdentity $identity
     */
    protected function setRolePermissions($level, AclPrivilegeIdentity $identity)
    {
        $aclPrivilege = new AclPrivilege();

        $aclPrivilege->setIdentity($identity);
        $permissions = [
            new AclPermission('VIEW', $level)
        ];

        foreach ($permissions as $permission) {
            $aclPrivilege->addPermission($permission);
        }

        $this->getContainer()->get('oro_security.acl.privilege_repository')->savePrivileges(
            $this->getContainer()->get('oro_security.acl.manager')->getSid($this->role),
            new ArrayCollection([$aclPrivilege])
        );
    }

    /**
     * @return AclPrivilegeIdentity
     */
    protected function getAccountAddressIdentity()
    {
        return new AclPrivilegeIdentity(
            'entity:Oro\Bundle\CustomerBundle\Entity\AccountAddress',
            'oro.customer.accountaddress.entity_label'
        );
    }

    /**
     * @return AclPrivilegeIdentity
     */
    protected function getAccountAddressUserIdentity()
    {
        return new AclPrivilegeIdentity(
            'entity:Oro\Bundle\CustomerBundle\Entity\AccountUserAddress',
            'oro.customer.accountuseraddress.entity_label'
        );
    }

    /**
     * @param string $actionId
     * @param bool $value
     */
    protected function setActionPermissions($actionId, $value)
    {
        $aclManager = $this->getContainer()->get('oro_security.acl.manager');

        $sid = $aclManager->getSid($this->role);
        $oid = $aclManager->getOid('action:' . $actionId);
        $builder = $aclManager->getMaskBuilder($oid);
        $mask = $value ? $builder->reset()->add('EXECUTE')->get() : $builder->reset()->get();
        $aclManager->setPermission($sid, $oid, $mask, true);

        $aclManager->flush();
    }
}
