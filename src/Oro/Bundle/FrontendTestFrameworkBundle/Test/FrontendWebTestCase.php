<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Test;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * The base class for the storefront functional tests.
 */
abstract class FrontendWebTestCase extends WebTestCase
{
    use WebsiteManagerTrait;

    /**
     * @beforeResetClient
     */
    public static function afterFrontendTest(): void
    {
        self::getWebsiteManagerStub()->disableStub();
    }

    protected function updateCustomerUserSecurityToken(string $email): void
    {
        $user = self::getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['email' => $email]);

        $token = new UsernamePasswordOrganizationToken(
            $user,
            'k',
            $user->getOrganization(),
            $user->getUserRoles()
        );

        $this->ensureSessionIsAvailable();
        self::getContainer()->get('security.token_storage')->setToken($token);
    }
}
