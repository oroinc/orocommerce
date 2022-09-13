<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RequestControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadRequestData::class
            ]
        );
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_rfp_request_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('rfp-requests-grid', $crawler->html());

        $this->assertContainsRequestData(
            LoadRequestData::FIRST_NAME,
            LoadRequestData::LAST_NAME,
            LoadRequestData::EMAIL,
            LoadRequestData::PO_NUMBER,
            $this->getFormatDate('Y-m-d'),
            $result->getContent()
        );
    }

    /**
     * @return integer
     */
    public function testView()
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST1);
        $id = $request->getId();

        $this->client->request(
            'GET',
            $this->getUrl('oro_rfp_request_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString(
            sprintf('%s %s - Requests For Quote - Sales', LoadRequestData::FIRST_NAME, LoadRequestData::LAST_NAME),
            $result->getContent()
        );

        $this->assertContainsRequestData(
            LoadRequestData::FIRST_NAME,
            LoadRequestData::LAST_NAME,
            LoadRequestData::EMAIL,
            LoadRequestData::PO_NUMBER,
            $this->getFormatDate('M j, Y'),
            $result->getContent()
        );

        return $id;
    }

    /**
     * @depends testView
     * @param integer $id
     */
    public function testUpdate($id)
    {
        $updatedFirstName = LoadRequestData::FIRST_NAME . '_update';
        $updatedLastName = LoadRequestData::LAST_NAME . '_update';
        $updatedEmail = LoadRequestData::EMAIL . '_update';
        $updatedPoNumber = LoadRequestData::PO_NUMBER . '_update';

        $crawler = $this->client->request('GET', $this->getUrl('oro_rfp_request_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $action = $crawler->selectButton('Save and Close')->attr('data-action');
        $form->setValues(['input_action' => $action]);

        $form->remove('oro_rfp_request[requestProducts][0]');

        $form['oro_rfp_request[firstName]'] = $updatedFirstName;
        $form['oro_rfp_request[lastName]'] = $updatedLastName;
        $form['oro_rfp_request[email]'] = $updatedEmail;
        $form['oro_rfp_request[poNumber]'] = $updatedPoNumber;

        $form['oro_rfp_request[assignedUsers]'] = $this->getReference(LoadUserData::USER1)->getId();
        $form['oro_rfp_request[assignedCustomerUsers]'] = implode(',', [
            $this->getReference(LoadUserData::ACCOUNT1_USER1)->getId(),
            $this->getReference(LoadUserData::ACCOUNT1_USER2)->getId()
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('Request has been saved', $crawler->html());

        $this->assertContainsRequestData(
            $updatedFirstName,
            $updatedLastName,
            $updatedEmail,
            $updatedPoNumber,
            $this->getFormatDate('M j, Y'),
            $result->getContent()
        );

        static::assertStringContainsString(
            $this->getReference(LoadUserData::USER1)->getFullName(),
            $result->getContent()
        );
        static::assertStringContainsString(
            $this->getReference(LoadUserData::ACCOUNT1_USER1)->getFullName(),
            $result->getContent()
        );
        static::assertStringContainsString(
            $this->getReference(LoadUserData::ACCOUNT1_USER2)->getFullName(),
            $result->getContent()
        );
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $poNumber
     * @param string $date
     * @param string $html
     */
    protected function assertContainsRequestData($firstName, $lastName, $email, $poNumber, $date, $html)
    {
        static::assertStringContainsString($firstName, $html);
        static::assertStringContainsString($lastName, $html);
        static::assertStringContainsString($email, $html);
        static::assertStringContainsString($poNumber, $html);
        static::assertStringContainsString($date, $html);
    }

    /**
     * @param string $format
     * @return string
     */
    private function getFormatDate($format)
    {
        $dateObj = new \DateTime('now', new \DateTimeZone('UTC'));
        $date = $dateObj->format($format);

        return $date;
    }
}
