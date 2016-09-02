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

    const RESTRCTED_PRODUCT = 'RestrictedProduct';
    const PRODUCT1 = 'Product 1';
    const PRODUCT2 = 'Product 2';

    /** @var array */
    protected $data = [
        'product1' => [
            'name' => self::PRODUCT1,

        ],
        'product2' => [
            'name' => self::PRODUCT2,

        ],
        'product3' => [
            'name' => self::RESTRCTED_PRODUCT,
        ],

    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $obj) {
            $item = new Product();
            $item->setName($obj['name']);
            $manager->persist($item);
        }

        $manager->flush();
        $manager->clear();
    }
}
