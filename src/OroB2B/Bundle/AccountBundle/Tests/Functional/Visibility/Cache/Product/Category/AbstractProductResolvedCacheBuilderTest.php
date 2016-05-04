<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

abstract class AbstractProductResolvedCacheBuilderTest extends WebTestCase
{
    const ROOT = 'root';

    /** @var Registry */
    protected $registry;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
        ]);

        $this->registry = $this->client->getContainer()->get('doctrine');
    }

    public function tearDown()
    {
        $this->getContainer()->get('doctrine')->getManager()->clear();
        parent::tearDown();
    }

    /**
     * @return Category
     */
    protected function getRootCategory()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
    }
}
