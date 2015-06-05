<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Entity;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class QuoteTest extends AbstractTest
{
    /**
     * @return QuoteProduct
     */
    public function testCreate()
    {
        $em = $this->entityManager;

        $quote = new Quote();

        $quote
            ->setOwner($this->findUser(LoadUserData::USER1))
        ;

        $this->assertNull($quote->getId());

        $em->persist($quote);
        $em->flush();

        $this->assertNotNull($quote->getId());

        $em->clear();

        $quote = $this->findQuote($quote->getId());

        $this->assertNotNull($quote);

        return $quote;
    }

    /**
     * @depends testCreate
     * @param Quote $quote
     */
    public function testQuoteProducts(Quote $quote)
    {
        $em = $this->entityManager;

        $product1 = $this->getQuoteProduct(LoadProductData::PRODUCT1);
        $product2 = $this->getQuoteProduct(LoadProductData::PRODUCT2);

        $quote
            ->addQuoteProduct($product1)
            ->addQuoteProduct($product2)
        ;

        $em->flush();
        $em->clear();

        $quote = $this->findQuote($quote->getId());

        $this->assertCount(2, $quote->getQuoteProducts());

        $quote->getQuoteProducts()->remove(0);

        $this->assertCount(1, $quote->getQuoteProducts());

        $em->flush();
        $em->clear();

        $quote = $this->findQuote($quote->getId());

        $this->assertCount(1, $quote->getQuoteProducts());

        /* @var $product QuoteProduct */
        $product = $quote->getQuoteProducts()->first();

        $this->assertEquals($product2->getId(), $product->getId());

        $quote->getQuoteProducts()->clear();

        $em->flush();
        $em->clear();

        $quote = $this->findQuote($quote->getId());

        $this->assertCount(0, $quote->getQuoteProducts());
    }

    /**
     * @param int $id
     * @return Quote
     */
    protected function findQuote($id)
    {
        /* @var $quote Quote */
        $quote = $this->entityManager->getRepository('OroB2BSaleBundle:Quote')->find($id);

        $this->assertNotNull($quote);

        return $quote;
    }
}
