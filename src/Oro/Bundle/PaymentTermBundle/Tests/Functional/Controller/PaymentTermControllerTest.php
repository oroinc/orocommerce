<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Controller;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PaymentTermControllerTest extends WebTestCase
{
    private const TERM_LABEL_NEW = 'net 100';
    private const TERM_LABEL_UPDATED = 'net 142';
    private const TERM_LABEL_TAG = '<script>alert(something)</script>';
    private const TERM_LABEL_TAG_REMOVED = 'alert(something)';
    private const SAVE_AND_CLOSE_BUTTON = 'Save and Close';
    private const CREATE_UPDATE_SUCCESS_MESSAGE = 'Payment term has been saved';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPaymentTermData::class]);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_index'));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        self::assertStringContainsString('payment-terms-grid', $html);
        self::assertStringContainsString(LoadPaymentTermData::TERM_LABEL_NET_10, $html);
        self::assertStringContainsString(LoadPaymentTermData::TERM_LABEL_NET_20, $html);
        self::assertStringContainsString(LoadPaymentTermData::TERM_LABEL_NET_30, $html);
    }

    public function testCreate(): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_create'));

        $createForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $createForm['oro_payment_term[label]'] = self::TERM_LABEL_NEW;

        $action = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->attr('data-action');
        $createForm['input_action'] = $action;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($createForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        self::assertStringContainsString(self::TERM_LABEL_NEW, $html);

        $paymentTerm = $this->getPaymentTermDataByLabel(self::TERM_LABEL_NEW);
        $this->assertNotEmpty($paymentTerm);

        return $paymentTerm->getId();
    }

    public function testCreateWithTag()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_create'));

        $createForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $createForm['oro_payment_term[label]'] = self::TERM_LABEL_TAG;

        $action = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->attr('data-action');
        $createForm['input_action'] = $action;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($createForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        self::assertStringContainsString(self::TERM_LABEL_TAG_REMOVED, $html);
    }

    public function testCreateWithTagAndValid()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_create'));

        $createForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $createForm['oro_payment_term[label]'] = self::TERM_LABEL_TAG . self::TERM_LABEL_NEW;

        $action = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->attr('data-action');
        $createForm['input_action'] = $action;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($createForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        self::assertStringContainsString(self::TERM_LABEL_TAG_REMOVED, $html);
        self::assertStringContainsString(self::TERM_LABEL_NEW, $html);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(int $id): int
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_term_update', ['id' => $id])
        );

        $updateForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $updateForm['oro_payment_term[label]'] = self::TERM_LABEL_UPDATED;

        $action = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->attr('data-action');
        $updateForm['input_action'] = $action;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($updateForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        self::assertStringContainsString(self::TERM_LABEL_UPDATED, $html);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testView(int $paymentTermId): int
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_payment_term_view', ['id' => $paymentTermId])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString(self::TERM_LABEL_UPDATED, $crawler->html());

        return $paymentTermId;
    }

    private function getPaymentTermDataByLabel(string $label): PaymentTerm
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(PaymentTerm::class)
            ->findOneBy(['label' => $label]);
    }
}
