<?php

namespace Oro\Bundle\ApruveBundle\Tests\Functional\Controller;

use Oro\Bundle\ApruveBundle\Tests\Functional\DataFixtures\LoadApruveChannelData;
use Oro\Bundle\ApruveBundle\Tests\Functional\DataFixtures\LoadApruvePaymentTransactionData;
use Oro\Bundle\ApruveBundle\Tests\Functional\DataFixtures\LoadApruveSettingsData;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class WebhookControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            LoadApruveChannelData::class,
            LoadApruvePaymentTransactionData::class,
        ]);
    }

    public function testNotifyAccessDenied()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_apruve_webhook_notify', [
                'token' => 'test'
            ])
        );
        $response = $this->client->getResponse();
        static::assertEquals('Access denied.', $response->getContent());
        static::assertHtmlResponseStatusCodeEquals($response, 403);
    }

    public function testNotifyMethodDisabled()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_apruve_webhook_notify', [
                'token' => LoadApruveSettingsData::WEBHOOK_TOKEN_3
            ])
        );
        $response = $this->client->getResponse();
        static::assertEquals('Payment method is disabled.', $response->getContent());
        static::assertHtmlResponseStatusCodeEquals($response, 404);
    }

    public function testNotifyBadBody()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_apruve_webhook_notify', [
                'token' => LoadApruveSettingsData::WEBHOOK_TOKEN_1
            ])
        );
        $response = $this->client->getResponse();
        static::assertEquals('Request body can\'t be decoded.', $response->getContent());
        static::assertHtmlResponseStatusCodeEquals($response, 400);
    }

    public function testNotifyInvoiceClosedInvalidEventBody()
    {
        $event = [
            'event' => 'invoice.closed',
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_apruve_webhook_notify', [
                'token' => LoadApruveSettingsData::WEBHOOK_TOKEN_1
            ]),
            [],
            [],
            [],
            $this->createContent($event)
        );
        $response = $this->client->getResponse();
        static::assertEquals('Invalid event body.', $response->getContent());
        static::assertHtmlResponseStatusCodeEquals($response, 400);
    }

    public function testNotifyInvoiceClosedInvoiceNotFound()
    {
        $event = [
            'event' => 'invoice.closed',
            'entity' => [
                'id' => 'unknown_id',
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_apruve_webhook_notify', [
                'token' => LoadApruveSettingsData::WEBHOOK_TOKEN_1
            ]),
            [],
            [],
            [],
            $this->createContent($event)
        );
        $response = $this->client->getResponse();
        static::assertEquals('Invoice was not found.', $response->getContent());
        static::assertHtmlResponseStatusCodeEquals($response, 404);
    }

    public function testNotifyInvoiceClosedEventAlreadyHandled()
    {
        $event = [
            'event' => 'invoice.closed',
            'entity' => [
                'id' => 'invoice_2',
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_apruve_webhook_notify', [
                'token' => LoadApruveSettingsData::WEBHOOK_TOKEN_2
            ]),
            [],
            [],
            [],
            $this->createContent($event)
        );
        $response = $this->client->getResponse();
        static::assertEquals('This event already handled.', $response->getContent());
        static::assertHtmlResponseStatusCodeEquals($response, 409);
    }

    public function testNotifyInvoiceClosedSuccess()
    {
        $event = [
            'event' => 'invoice.closed',
            'entity' => [
                'id' => 'invoice_1',
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_apruve_webhook_notify', [
                'token' => LoadApruveSettingsData::WEBHOOK_TOKEN_1
            ]),
            [],
            [],
            [],
            $this->createContent($event)
        );
        $response = $this->client->getResponse();
        static::assertEquals('', $response->getContent());
        static::assertHtmlResponseStatusCodeEquals($response, 200);

        $paymentTransactions = $this->getContainer()->get('oro_payment.repository.payment_transaction')->findBy([
            'action' => PaymentMethodInterface::CAPTURE,
            'reference' => 'invoice_1',
        ]);

        static::assertCount(1, $paymentTransactions);
    }

    /**
     * @dataProvider notifyIgnoredEventsDataProvider
     *
     * @param string $eventName
     */
    public function testNotifyIgnoredEvents($eventName)
    {
        $event = [
            'event' => $eventName,
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_apruve_webhook_notify', [
                'token' => LoadApruveSettingsData::WEBHOOK_TOKEN_1
            ]),
            [],
            [],
            [],
            $this->createContent($event)
        );
        $response = $this->client->getResponse();
        static::assertEquals('', $response->getContent());
        static::assertHtmlResponseStatusCodeEquals($response, 200);
    }

    /**
     * @return array
     */
    public function notifyIgnoredEventsDataProvider()
    {
        return [
            ['eventName' => 'order.approved'],
            ['eventName' => 'order.cancled'],
            ['eventName' => 'payment_term.approved'],
        ];
    }

    /**
     * @param array $body
     *
     * @return string
     */
    private function createContent(array $body)
    {
        return json_encode($body);
    }
}
