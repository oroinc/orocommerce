<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Entity;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;

/**
 * @dbIsolation
 */
class QuoteProductItemTest extends AbstractTest
{
    /**
     * @return QuoteProduct
     */
    public function testCreate()
    {
        $em = $this->entityManager;

        $item = new QuoteProductItem();

        $price = new Price();
        $price
            ->setValue(2.2)
            ->setCurrency('USD')
        ;

        $item
            ->setQuantity(1.1)
            ->setPrice($price)
        ;

        $this->assertNull($item->getId());

        $em->persist($item);
        $em->flush();

        $this->assertNotNull($item->getId());

        $em->clear();

        $item = $this->findQuoteProductItem($item->getId());

        $this->assertNotNull($item);
        $this->assertEquals(1.1, $item->getQuantity());
        $this->assertEquals($price, $item->getPrice());

        return $item;
    }

    /**
     * @param int $id
     * @return QuoteProductItem
     */
    protected function findQuoteProductItem($id)
    {
        /* @var $item QuoteProductItem */
        $item = $this->entityManager->getRepository('OroB2BSaleBundle:QuoteProductItem')->find($id);

        $this->assertNotNull($item);

        return $item;
    }
}
