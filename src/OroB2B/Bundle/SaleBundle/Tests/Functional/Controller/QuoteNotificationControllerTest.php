<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class QuoteNotificationControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->markTestIncomplete('Skipped due to issue with DOMDocument https://bugs.php.net/bug.php?id=52012');

        $this->initClient([], array_merge(static::generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
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
                'quote_notification_email',
                ['id' => $quote->getId()]
            )
        );

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /* @var $form Form */
        $form = $crawler->selectButton('Notify and Lock')->form();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('The email was sent', $crawler->html());
    }

    /**
     * @param string $username
     * @return User
     */
    protected function getUser($username)
    {
        return $this->getReference($username);
    }
}
