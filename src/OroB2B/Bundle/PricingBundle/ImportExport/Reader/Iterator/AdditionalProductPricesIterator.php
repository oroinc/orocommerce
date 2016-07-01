<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\Reader\Iterator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class AdditionalProductPricesIterator implements \Iterator
{
    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var PriceList
     */
    protected $priceList;

    /**
     * @var array
     */
    protected $currencies = [];

    /**
     * @var \Iterator
     */
    protected $productIterator;

    /**
     * @var \Iterator|null
     */
    protected $additionalItemsIterator;

    /**
     * @var mixed
     */
    protected $current = null;

    /**
     * @var mixed
     */
    protected $offset = -1;

    /**
     * @param QueryBuilder $qb
     * @param PriceList $priceList
     * @param array $currencies
     */
    public function __construct(QueryBuilder $qb, PriceList $priceList, array $currencies)
    {
        $this->priceList = $priceList;
        $this->currencies = $currencies;
        $this->productIterator = new BufferedQueryResultIterator($qb);
        $this->manager = $qb->getEntityManager();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->current = $this->read();
        if ($this->valid()) {
            ++$this->offset;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->current !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->offset = -1;
        $this->current = null;
        $this->additionalItemsIterator = null;
        $this->productIterator->rewind();

        $this->next();
    }

    /**
     * Read next element from subordinate iterator, iterates to next element of main iterator when done.
     *
     * @return array|null
     */
    protected function read()
    {
        if (!$this->productIterator->valid()) {
            return null;
        }

        if (!$this->additionalItemsIterator) {
            $product = $this->productIterator->current();
            $this->additionalItemsIterator = $this->createAdditionalItemsIterator($product);
            if (!$this->additionalItemsIterator->valid()) {
                // Iterator could be already rewound
                $this->additionalItemsIterator->rewind();
            }
        }

        if (!$this->additionalItemsIterator->valid()) {
            // Read for next List
            $this->additionalItemsIterator = null;
            $this->productIterator->next();
            return $this->read();
        }

        $result = $this->additionalItemsIterator->current();
        $this->additionalItemsIterator->next();
        return $result;
    }

    /**
     * Creates additional items iterator.
     *
     * Will return Product Prices for each of available currencies in each unit supported by product
     *
     * @param Product $product
     * @return \Iterator
     */
    protected function createAdditionalItemsIterator(Product $product)
    {
        $data = [];
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            foreach ($this->currencies as $currency) {
                $data[] = $productPrice = (new ProductPrice())
                    ->setPriceList($this->priceList)
                    ->setProduct($product)
                    ->setUnit($unitPrecision->getUnit())
                    ->setPrice(Price::create(null, $currency));
            }
        }

        return new \ArrayIterator($data);
    }
}
