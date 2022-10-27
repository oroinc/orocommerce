<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class QuoteNotificationTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Skipped due to issue with DOMDocument https://bugs.php.net/bug.php?id=52012');

        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
                'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData'
            ]
        );
    }

    public function testEmail()
    {
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_widget_form',
                [
                    '_wid' => 'test-uuid',
                    '_widgetContainer' => 'dialog',
                    'operationName' => 'oro_sale_notify_customer_by_email',
                    'entityClass' => 'Oro\Bundle\SaleBundle\Entity\Quote',
                    'entityId' => $quote->getId()
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Send')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('The email was sent', $crawler->html());
    }
}
