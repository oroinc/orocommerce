<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\Controller;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class PaymentTermControllerTest extends WebTestCase
{
    const TERM_LABEL_NEW = 'net 100';
    const TERM_LABEL_UPDATED = 'net 142';
    const TERM_LABEL_TAG = '<script>alert(something)</script>';
    const TERM_LABEL_TAG_REMOVED = 'alert(something)';

    const SAVE_AND_CLOSE_BUTTON = 'Save and Close';
    const CREATE_UPDATE_SUCCESS_MESSAGE = 'Payment term has been saved';
    const BLANK_MESSAGE = 'This value should not be blank.';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData']);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_index'));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        static::assertStringContainsString('payment-terms-grid', $html);
        static::assertStringContainsString(LoadPaymentTermData::TERM_LABEL_NET_10, $html);
        static::assertStringContainsString(LoadPaymentTermData::TERM_LABEL_NET_20, $html);
        static::assertStringContainsString(LoadPaymentTermData::TERM_LABEL_NET_30, $html);
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_create'));

        /** @var Form $form */
        $createForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $createForm['oro_payment_term[label]'] = self::TERM_LABEL_NEW;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($createForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        static::assertStringContainsString(self::TERM_LABEL_NEW, $html);

        $paymentTerm = $this->getPaymentTermDataByLabel(self::TERM_LABEL_NEW);
        $this->assertNotEmpty($paymentTerm);

        return $paymentTerm->getId();
    }

    public function testCreateWithTag()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_create'));

        /** @var Form $form */
        $createForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $createForm['oro_payment_term[label]'] = self::TERM_LABEL_TAG;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($createForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        static::assertStringContainsString(self::TERM_LABEL_TAG_REMOVED, $html);
    }

    public function testCreateWithTagAndValid()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_payment_term_create'));

        /** @var Form $form */
        $createForm = $crawler->selectButton(self::SAVE_AND_CLOSE_BUTTON)->form();
        $createForm['oro_payment_term[label]'] = self::TERM_LABEL_TAG . self::TERM_LABEL_NEW;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($createForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        static::assertStringContainsString(self::TERM_LABEL_TAG_REMOVED, $html);
        static::assertStringContainsString(self::TERM_LABEL_NEW, $html);
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

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($updateForm);

        $html = $crawler->html();
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString(self::CREATE_UPDATE_SUCCESS_MESSAGE, $html);
        static::assertStringContainsString(self::TERM_LABEL_UPDATED, $html);

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
        static::assertStringContainsString(self::TERM_LABEL_UPDATED, $crawler->html());

        return $paymentTermId;
    }

    /**
     * @param string $label
     * @return PaymentTerm
     */
    private function getPaymentTermDataByLabel($label)
    {
        /** @var PaymentTerm $paymentTerm */
        $paymentTerm = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPaymentTermBundle:PaymentTerm')
            ->getRepository('OroPaymentTermBundle:PaymentTerm')
            ->findOneBy(['label' => $label]);

        return $paymentTerm;
    }
}
