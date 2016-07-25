<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class QuoteNotificationTest extends WebTestCase
{
    protected function setUp()
    {
        $this->markTestSkipped('Skipped due to issue with DOMDocument https://bugs.php.net/bug.php?id=52012');

        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData'
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
                    'operationName' => 'orob2b_sale_notify_customer_by_email',
                    'entityClass' => 'OroB2B\Bundle\SaleBundle\Entity\Quote',
                    'entityId' => $quote->getId()
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /* @var $form Form */
        $form = $crawler->selectButton('Notify and Lock')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('The email was sent', $crawler->html());
    }
}
