<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CatalogBundle\Entity\Category;

abstract class AbstractProductResolvedCacheBuilderTest extends WebTestCase
{
    const ROOT = 'root';

    /** @var Registry */
    protected $registry;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
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
            ->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')
            ->getMasterCatalogRoot();
    }
}
