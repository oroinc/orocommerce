<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Entity;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;

abstract class AbstractTest extends WebTestCase
{
    /**
     * @var OroEntityManager
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->initClient();

        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();

        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData',
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
        ]);
    }

    /**
     * @param string $sku
     * @return Product
     */
    protected function findProduct($sku)
    {
        /* @var $product Product */
        $product = $this->entityManager->getRepository('OroB2BProductBundle:Product')->findOneBySku($sku);

        $this->assertNotNull($product);

        return $product;
    }

    /**
     * @param string $sku
     * @return QuoteProduct
     */
    protected function getQuoteProduct($sku)
    {
        $product = new QuoteProduct();

        $product
            ->setProduct($this->findProduct($sku))
        ;

        return $product;
    }

    /**
     * @param string $username
     * @return User
     */
    protected function findUser($username)
    {
        /* @var $repository \Oro\Bundle\UserBundle\Entity\Repository\UserRepository */
        $repository = $this->entityManager->getRepository('OroUserBundle:User');

        return $repository->findOneByUsername($username);
    }
}
