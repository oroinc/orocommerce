<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class AccountUserControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        $this->loadFixtures([
            'Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
        ]);
    }

    public function testQuoteGridOnAccountViewPage()
    {
        /** @var Request $request */
        $request = $this->getReference('rfp.request.2');
        /** @var AccountUser $accountUser */
        $accountUser = $request->getAccountUser();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_account_account_user_view',
                ['id' => $accountUser->getId()]
            )
        );
        $gridAttr = $crawler->filter('[id^=grid-account-user-view-rfq-grid]')
            ->first()->attr('data-page-component-options');
        $gridJsonElements = json_decode(html_entity_decode($gridAttr), true);
        $this->assertCount(
            count(LoadRequestData::getRequestsFor('accountUser', $accountUser->getEmail())),
            $gridJsonElements['data']['data']
        );
    }
}
