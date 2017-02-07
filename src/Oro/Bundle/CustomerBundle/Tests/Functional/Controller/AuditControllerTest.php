<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AuditControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
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
                    'entity' => 'Oro_Bundle_CustomerBundle_Entity_CustomerUser',
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
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
    }
}
