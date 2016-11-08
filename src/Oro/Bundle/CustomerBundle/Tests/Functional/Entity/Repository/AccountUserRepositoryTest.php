<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\Repository\AccountUserRepository;

/**
 * @dbIsolation
 */
class AccountUserRepositoryTest extends WebTestCase
{
    /**
     * @var AccountUserRepository
     */
    protected $repository;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCustomerBundle:AccountUser');

        $this->loadFixtures(
            [
                'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData'
            ]
        );

        $this->aclHelper = $this->getContainer()->get('oro_security.acl_helper');
    }

    public function testGetAccountUsersQueryBuilder()
    {
        /** TODO Add in BB-4518 */
    }
}
