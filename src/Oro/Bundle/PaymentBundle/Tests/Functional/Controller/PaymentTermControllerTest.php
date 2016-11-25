<?php

namespace Oro\Bundle\PaymentBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;

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
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\PaymentBundle\Tests\Functional\DataFixtures\LoadPaymentTermData']);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_index'));

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
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_create'));

        /** @var Form $form */
        $createForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $createForm['oro_payment_term[label]'] = self::TERM_LABEL_NEW;
        $createForm['oro_payment_term[appendAccounts]'] = $this->getReference('account.level_1')->getId();
        $createForm['oro_payment_term[appendAccountGroups]'] = $this->getReference('account_group.group1')->getId();

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

        $paymentTerm = $this->getPaymentTermDataByLabel(self::TERM_LABEL_NEW);
        $this->assertNotEmpty($paymentTerm);

        return $paymentTerm->getId();
    }

    /**
     * @depends testCreate
     * @param $id int
     * @return int
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_term_update', ['id' => $id])
        );

        /** @var Form $form */
        $updateForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $updateForm['oro_payment_term[label]'] = self::TERM_LABEL_UPDATED;
        $updateForm['oro_payment_term[appendAccounts]'] = $this->getReference('account.orphan')->getId();
        $updateForm['oro_payment_term[removeAccounts]'] = $this->getReference('account.level_1')->getId();
        $updateForm['oro_payment_term[appendAccountGroups]'] = $this->getReference('account_group.group2')->getId();
        $updateForm['oro_payment_term[removeAccountGroups]'] = $this->getReference('account_group.group1')->getId();

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

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $paymentTermId
     * @return int
     */
    public function testView($paymentTermId)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_term_view', ['id' => $paymentTermId])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::TERM_LABEL_UPDATED, $crawler->html());

        return $paymentTermId;
    }

    /**
     * @depends testView
     * @param int $paymentTermId
     */
    public function testDelete($paymentTermId)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_paymentterm', ['id' => $paymentTermId]),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('oro_payment_term_view', ['id' => $paymentTermId]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    /**
     * @param string $label
     * @return PaymentTerm
     */
    private function getPaymentTermDataByLabel($label)
    {
        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPaymentBundle:PaymentTerm')
            ->getRepository('OroPaymentBundle:PaymentTerm')
            ->findOneBy(['label' => $label]);

        return $paymentTerm;
    }
}
