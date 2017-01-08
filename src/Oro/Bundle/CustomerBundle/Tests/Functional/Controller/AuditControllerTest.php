<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AuditControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);
    }

    public function testAuditHistory()
    {
        $user = $this->getCurrentUser();
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_customer_frontend_dataaudit_history',
                [
                    'entity' => 'Oro_Bundle_CustomerBundle_Entity_AccountUser',
                    'id' => $user->getId(),
                ]
            )
        );
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    /**
     * @return CustomerUser
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:CustomerUser')
            ->getRepository('OroCustomerBundle:CustomerUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }
}
