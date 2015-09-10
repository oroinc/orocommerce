<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller;

/**
 * @dbIsolation
 */
class AccountControllerTest extends AbstractAccountControllerTest
{
    public function testView()
    {
        $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_view', ['id' => $this->accountUser->getAccount()->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertContains('account-orders-grid', $content);
    }

    public function testAccountQuoteViewGrid()
    {
        $response = $this->client->requestGrid(
            [
                'gridName' => 'account-orders-grid',
                'account-orders-grid[account_id]' => $this->accountUser->getAccount()->getId(),
            ]
        );
        $this->checkDatagridResponse($response);
    }
}
