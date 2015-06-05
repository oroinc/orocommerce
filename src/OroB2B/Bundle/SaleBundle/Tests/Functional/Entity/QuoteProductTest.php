<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Entity;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class QuoteProductTest extends AbstractTest
{
    /**
     * @return QuoteProduct
     */
    public function testCreate()
    {
        $em = $this->entityManager;

        $product = new QuoteProduct();

        $product
            ->setQuote($this->findQuote(LoadQuoteData::QUOTE1))
            ->setProduct($this->findProduct(LoadProductData::PRODUCT1))
        ;

        $this->assertNull($product->getId());

        $em->persist($product);
        $em->flush();

        $this->assertNotNull($product->getId());

        $em->clear();

        $product = $this->findQuoteProduct($product->getId());

        $this->assertNotNull($product);

        return $product;
    }

    /**
     * @depends testCreate
     * @param QuoteProduct $product
     */
    public function testQuoteProductItems(QuoteProduct $product)
    {
        $em = $this->entityManager;

        $item1 = $this->getQuoteProductItem(LoadProductData::PRODUCT1);
        $item2 = $this->getQuoteProductItem(LoadProductData::PRODUCT2);

        $product
            ->addQuoteProductItem($item1)
            ->addQuoteProductItem($item2)
        ;

        $em->flush();
        $em->clear();

        $product = $this->findQuoteProduct($product->getId());

        $this->assertCount(2, $product->getQuoteProductItems());

        $product->getQuoteProductItems()->remove(0);

        $this->assertCount(1, $product->getQuoteProductItems());

        $em->flush();
        $em->clear();

        $product = $this->findQuoteProduct($product->getId());

        $this->assertCount(1, $product->getQuoteProductItems());

        /* @var $item QuoteProductItem */
        $item = $product->getQuoteProductItems()->first();

        $this->assertEquals($item2->getId(), $item->getId());

        $product->getQuoteProductItems()->clear();

        $em->flush();
        $em->clear();

        $product = $this->findQuoteProduct($product->getId());

        $this->assertCount(0, $product->getQuoteProductItems());
    }

    /**
     * @param string $qid
     * @return Quote
     */
    protected function findQuote($qid)
    {
        /* @var $quote Quote */
        $quote = $this->entityManager->getRepository('OroB2BSaleBundle:Quote')->findOneByQid($qid);

        $this->assertNotNull($quote);

        return $quote;
    }

    /**
     * @param int $id
     * @return QuoteProduct
     */
    protected function findQuoteProduct($id)
    {
        /* @var $product QuoteProduct */
        $product = $this->entityManager->getRepository('OroB2BSaleBundle:QuoteProduct')->find($id);

        $this->assertNotNull($product);

        return $product;
    }

    /**
     * @param string $sku
     * @return QuoteProductItem
     */
    protected function getQuoteProductItem($sku)
    {
        $item = new QuoteProductItem();
        $item
            ->setQuoteProduct($this->getQuoteProduct($sku))
            ->setQuantity(10)
            ->setPrice((new Price)->setValue(20)->setCurrency('USD'))
            ->setProductUnit($this->getReference(LoadProductData::UNIT1));
        ;

        return $item;
    }
}
