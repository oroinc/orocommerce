<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Test;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * The base class for store frontend functional tests.
 */
abstract class FrontendWebTestCase extends WebTestCase
{
    use WebsiteManagerTrait;

    /**
     * @beforeResetClient
     */
    public static function afterFrontendTest()
    {
        self::getWebsiteManagerStub()->disableStub();
    }

    /**
     * @param string $email
     */
    protected function updateCustomerUserSecurityToken($email)
    {
        $user = $this->getContainer()
            ->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['email' => $email]);

        $token = new UsernamePasswordOrganizationToken(
            $user,
            false,
            'k',
            $user->getOrganization(),
            $user->getUserRoles()
        );

        $this->ensureSessionIsAvailable();
        $this->getContainer()->get('security.token_storage')->setToken($token);
    }
}
