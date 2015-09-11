<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

/**
 * @dbIsolation
 */
class AccountUserControllerTest extends AbstractAccountControllerTest
{
    public function testView()
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_account_user_view', ['id' => $this->accountUser->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertContains('account-user-orders-grid', $content);
    }

    public function testAccountUserOrderViewGrid()
    {
        $response = $this->client->requestGrid(
            [
                'gridName' => 'account-user-orders-grid',
                'account-user-orders-grid[account_user_id]' => $this->accountUser->getId(),
            ]
        );
        $this->checkDatagridResponse($response);
    }
}
