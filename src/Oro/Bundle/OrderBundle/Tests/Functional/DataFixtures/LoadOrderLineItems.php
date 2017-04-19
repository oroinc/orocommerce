<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\Yaml\Yaml;

class LoadOrderLineItems extends AbstractFixture implements DependentFixtureInterface
{
    const ITEM_1 = 'order_line_item.1';
    const ITEM_2 = 'order_line_item.2';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadOrders::class,
            LoadProductData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $reference => $data) {
            $item = $this->buildOrderLineItem($data);

            $manager->persist($item);
            $this->addReference($reference, $item);
        }

        $manager->flush();
    }

    /**
     * @param array $data
     *
     * @return OrderLineItem
     */
    private function buildOrderLineItem($data)
    {
        $item = new OrderLineItem();

        $item->setOrder($this->getReference($data['order']))
            ->setProduct($this->getReference($data['product']))
            ->setQuantity($data['quantity'])
            ->setProductUnit($this->getReference($data['productUnit']))
            ->setValue($data['value'])
            ->setCurrency($data['currency']);

        if (array_key_exists('parentProduct', $data)) {
            $item->setParentProduct($this->getReference($data['parentProduct']));
        }

        return $item;
    }

    /**
     * @return array
     */
    private function getData()
    {
        return Yaml::parse(file_get_contents(__DIR__.'/data/order_line_items.yml'));
    }
}
