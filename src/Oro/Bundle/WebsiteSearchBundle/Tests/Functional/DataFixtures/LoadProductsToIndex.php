<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\Product;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadProductsToIndex extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const REFERENCE_PRODUCT1 = 'product1';
    const REFERENCE_PRODUCT2 = 'product2';

    const PRODUCT1 = 'Product 1';
    const PRODUCT2 = 'Product 2';

    /** @var array */
    protected $data = [
        self::REFERENCE_PRODUCT1 => [
            'name' => self::PRODUCT1,
        ],
        self::REFERENCE_PRODUCT2 => [
            'name' => self::PRODUCT2,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $reference => $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $manager->persist($product);

            $this->addReference($reference, $product);
        }

        $manager->flush();
        $manager->clear();
    }
}
