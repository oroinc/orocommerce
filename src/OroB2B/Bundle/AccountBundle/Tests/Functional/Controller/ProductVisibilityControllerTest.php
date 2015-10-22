<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts;
use OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData as TestFixturesLoadWebsiteData;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @dbIsolation
 */
class ProductVisibilityControllerTest extends WebTestCase
{
    /** @var  Product */
    protected $product;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            ]
        );

        $this->product = $this->getReference(LoadProducts::PRODUCT_1);
    }

    public function testView()
    {
        $this->submitForm();
    }

    protected function submitForm()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_account_product_visibility_edit', ['id' => $this->product->getId()])
        );
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $websiteTitles = [
            LoadWebsiteData::DEFAULT_WEBSITE_NAME,
            TestFixturesLoadWebsiteData::WEBSITE1,
            TestFixturesLoadWebsiteData::WEBSITE2,
        ];
        $websiteIds = [];
        $counter = 0;
        // Test exits tabs
        $crawler->filterXPath('//div[@class="oro-tabs"]//li')->each(
            function (Crawler $node) use (&$counter, &$websiteIds, $websiteTitles) {
                $this->assertContains($websiteTitles[$counter], $node->text());
                $options = json_decode($node->filterXPath('//a/@data-page-component-options')->text(), true);
                $websiteIds[] = $options['options']['alias'];
                $counter++;
            }
        );

        $form = $crawler->selectButton('Save and Close')->form();
        $parameters = $this->explodeArrayPaths($form->getValues());
        $token = $crawler->filterXPath('//input[@name="orob2b_account_website_scoped_data_type[_token]"]/@value')
            ->text();


    }

    /**
     * @param array $values
     * @return array
     */
    protected function explodeArrayPaths($values)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $parameters = [];
        foreach ($values as $key => $val) {
            if (!$pos = strpos($key, '[')) {
                continue;
            }
            $key = '['.substr($key, 0, $pos).']'.substr($key, $pos);
            $accessor->setValue($parameters, $key, $val);
        }

        return $parameters;
    }
}
