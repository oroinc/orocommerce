<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

abstract class AbstractAccountControllerTest extends WebTestCase
{
    /** @var $accountUser AccountUser */
    protected $accountUser;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], array_merge(static::generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'Oro\Component\Testing\Fixtures\LoadAccountUserData',
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders',
            ]
        );
        $manager = $this->client->getContainer()->get('doctrine')->getManagerForClass(
            'OroB2BAccountBundle:AccountUser'
        );
        $this->accountUser = $manager->getRepository('OroB2BAccountBundle:AccountUser')->findOneBy(
            ['username' => LoadOrders::ACCOUNT_USER]
        );
    }

    /**
     * @param Response $response
     */
    protected function checkDatagridResponse(Response $response)
    {
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertContains(sprintf('$%.2F', LoadOrders::SUBTOTAL), $result['data'][0]['subtotal']);
    }
}
