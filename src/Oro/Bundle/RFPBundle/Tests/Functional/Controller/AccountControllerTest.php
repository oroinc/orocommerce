<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class AccountControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            'Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
        ]);
    }

    public function testQuoteGridOnAccountViewPage()
    {
        $repo = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository('OroRFPBundle:Request');
        /** @var Request $request */
        $request = $repo->findOneBy(['note' => 'rfp.request.3']);
        /** @var Account $account */
        $account = $request->getAccountUser()->getAccount();
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_account_view', ['id' => $account->getId()]));
        $gridAttr = $crawler->filter('[id^=grid-account-view-rfq-grid]')->first()->attr('data-page-component-options');
        $gridJsonElements = json_decode(html_entity_decode($gridAttr), true);
        $this->assertContains($request->getAccountUser()->getFullName(), $gridAttr);
        $this->assertCount(
            count(LoadRequestData::getRequestsFor('account', $account->getName())),
            $gridJsonElements['data']['data']
        );
    }
}
