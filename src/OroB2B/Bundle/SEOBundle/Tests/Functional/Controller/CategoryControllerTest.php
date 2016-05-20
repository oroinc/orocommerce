<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CategoryControllerTest extends WebTestCase
{
    use SEOViewSectionTrait;
    
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData']);
    }

    public function testEditCategory()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_catalog.entity.category.class')
        );

        $category = $repository->findOneBy(['id' => 1]);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_catalog_category_update', ['id' => $category->getId()]));

        $this->checkSeoSectionExistence($crawler);
    }
}
