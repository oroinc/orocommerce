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
            $filter = sprintf('select[name="%s[%s][customerAddress]"]', $formName, $addressType);
            $customerAddressSelector = $crawler->filter($filter)->html();

            $this->assertContains('Enter other address', $customerAddressSelector);
        }

        // Check customer addresses
        if (!empty($expected['customer'])) {
            $filter = sprintf(
                'select[name="%s[%s][customerAddress]"] optgroup[label="Global Address Book"]',
                $formName,
                $addressType
            );
            $customerAddresses = $crawler->filter($filter)->html();

            foreach ($expected['customer'] as $customerAddress) {
                $this->assertContains($customerAddress, $customerAddresses);
            }
        }

        // Check customer users addresses
        if (!empty($expected['customerUser'])) {
            $filter = sprintf(
                'select[name="%s[%s][customerAddress]"] optgroup[label="My Address Book"]',
                $formName,
                $addressType
            );
            $customerUserAddresses = $crawler->filter($filter)->html();

            foreach ($expected['customerUser'] as $customerUserAddress) {
                $this->assertContains($customerUserAddress, $customerUserAddresses);
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
    protected function getCustomerAddressIdentity()
    {
        return new AclPrivilegeIdentity(
            'entity:Oro\Bundle\CustomerBundle\Entity\CustomerAddress',
            'oro.customer.customeraddress.entity_label'
        );
    }

    /**
     * @return AclPrivilegeIdentity
     */
    protected function getCustomerAddressUserIdentity()
    {
        return new AclPrivilegeIdentity(
            'entity:Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress',
            'oro.customer.customeruseraddress.entity_label'
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
