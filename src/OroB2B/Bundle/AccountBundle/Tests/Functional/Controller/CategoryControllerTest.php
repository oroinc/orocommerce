<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Controller;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @dbIsolation
 */
class CategoryControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->loadFixtures(['OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData']);
    }

    public function testEdit()
    {
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $this->client->followRedirects();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_catalog_category_update', ['id' => $category->getId()])
        );
        $response = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($response, 200);
        $result = [];

        $form = $crawler->selectButton('Save and Close')->form();
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('orob2b_catalog_category_update', ['id' => $category->getId()]),
            $form->getValues()
        );
        $x = $crawler->html();
        $resp = $this->client->getResponse();
        $html = $crawler->html();
        $this->client->submit($form0);
        $x = $form0['orob2b_catalog_category[categoryVisibility]'] = 12;
        $crawler->filterXPath("//form")->each(
            function (Crawler $node) use (&$result) {
                $form = $node->form();
                $result[] = $form->getValues();
            }
        );
        $el = $crawler->filterXPath('//select[@name="orob2b_catalog_category[categoryVisibility]"]');
        $el1 = $crawler->filterXPath('//input[@name="orob2b_catalog_category[parentCategory]"]');
        $x = $el->html();

        $form1 = $el1->form();
        $vals = $form1->getValues();
        $form = $el->form();
        $form['orob2b_catalog_category[categoryVisibility]'] = 'config';
        $crawler = $this->client->submit($form);
        $resp = $this->client->getResponse();
    }
}