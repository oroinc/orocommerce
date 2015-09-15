<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

/**
 * @dbIsolation
 */
class PaymentTermControllerTest extends WebTestCase
{
    const TERM_LABEL_NEW = 'net 100';
    const TERM_LABEL_UPDATED = 'net 142';

    const SAVE_AND_CLOSE_BUTTON = 'Save and Close';
    const CREATE_UPDATE_SUCCESS_MESSAGE = 'Payment term has been saved';

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->loadFixtures(['OroB2B\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData']);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_payment_term_index'));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains("payment-terms-grid", $html);
        $this->assertContains(LoadPaymentTermData::TERM_LABEL_NET_10, $html);
        $this->assertContains(LoadPaymentTermData::TERM_LABEL_NET_20, $html);
        $this->assertContains(LoadPaymentTermData::TERM_LABEL_NET_30, $html);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_payment_term_create'));

        /** @var Form $form */
        $createForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $createForm['orob2b_payment_term[label]'] = self::TERM_LABEL_NEW;
        $createForm['orob2b_payment_term[appendAccounts]'] = $this->getReference('account.level_1')->getId();
        $createForm['orob2b_payment_term[appendAccountGroups]'] = $this->getReference('account_group.group1')->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($createForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        $this->assertContains(self::TERM_LABEL_NEW, $html);

        $accountsGrid = $crawler->filter('.inner-grid')->eq(0)->attr('data-page-component-options');
        $this->assertContains($this->getReference('account.level_1')->getName(), $accountsGrid);

        $accountsGroupGrid = $crawler->filter('.inner-grid')->eq(1)->attr('data-page-component-options');
        $this->assertContains($this->getReference('account_group.group1')->getName(), $accountsGroupGrid);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $paymentTermData = $this->getPaymentTermDataByLabel(self::TERM_LABEL_NEW);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_payment_term_update', ['id' => $paymentTermData['id']])
        );

        /** @var Form $form */
        $updateForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $updateForm['orob2b_payment_term[label]'] = self::TERM_LABEL_UPDATED;
        $updateForm['orob2b_payment_term[appendAccounts]'] = $this->getReference('account.orphan')->getId();
        $updateForm['orob2b_payment_term[removeAccounts]'] = $this->getReference('account.level_1')->getId();
        $updateForm['orob2b_payment_term[appendAccountGroups]'] = $this->getReference('account_group.group2')->getId();
        $updateForm['orob2b_payment_term[removeAccountGroups]'] = $this->getReference('account_group.group1')->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($updateForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        $this->assertContains(self::TERM_LABEL_UPDATED, $html);

        $accountsGrid = $crawler->filter('.inner-grid')->eq(0)->attr('data-page-component-options');
        $this->assertContains($this->getReference('account.orphan')->getName(), $accountsGrid);
        $this->assertNotContains($this->getReference('account.level_1')->getName(), $accountsGrid);

        $accountsGroupGrid = $crawler->filter('.inner-grid')->eq(1)->attr('data-page-component-options');
        $this->assertContains($this->getReference('account_group.group2')->getName(), $accountsGroupGrid);
        $this->assertNotContains($this->getReference('account_group.group1')->getName(), $accountsGroupGrid);
    }

    /**
     * @depends testUpdate
     */
    public function testView()
    {
        $paymentTermData = $this->getPaymentTermDataByLabel(self::TERM_LABEL_UPDATED);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_payment_term_view', ['id' => $paymentTermData['id']])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(LoadPaymentTermData::TERM_LABEL_NET_10, $crawler->html());
    }

    /**
     * @depends testView
     */
    public function testDelete()
    {
        $paymentTermData = $this->getPaymentTermDataByLabel(LoadPaymentTermData::TERM_LABEL_NET_10);

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_delete_paymentterm', ['id' => $paymentTermData['id']])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('orob2b_payment_term_view', ['id' => $paymentTermData['id']]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    private function getPaymentTermDataByLabel($label)
    {
        $response = $this->client->requestGrid(
            'payment-terms-grid',
            ['payment-terms-grid[_filter][label][value]' => $label]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('data', $result);
        $this->assertNotEmpty($result['data']);

        $result = reset($result['data']);
        $this->assertNotEmpty($result);

        return $result;
    }
}
