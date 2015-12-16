<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class ProductVisibilityWebsiteResolverTest extends WebTestCase
{
    const TEST_WEBSITE_NAME = 'test website' ;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData']);

    }

    public function testVisibilityToNewWebsite()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_website_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['orob2b_website_type[name]'] = self::TEST_WEBSITE_NAME;
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();
        $this->assertContains('Website has been saved', $html);
        $this->assertContains(self::TEST_WEBSITE_NAME, $html);
        $website = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(['name' => self::TEST_WEBSITE_NAME]);

        $resolvedVisibility = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findBy(['website' => $website]);
        $this->assertNotEmpty($resolvedVisibility);

        $visibility = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->findBy(['website' => $website]);

        $this->assertEmpty($visibility);
    }
}
