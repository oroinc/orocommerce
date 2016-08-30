<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\Product;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductsToIndex extends AbstractFixture implements ContainerAwareInterface
{
    const RESTRCTED_PRODUCT = 'RestrictedProduct';
    const PRODUCT1 = 'Product 1';
    const PRODUCT2 = 'Product 2';

    /** @var ContainerInterface */
    protected $container;

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
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $helper = $this->container->get('oro_entity.doctrine_helper');
        $em = $helper->getEntityManager(Product::class);

        foreach ($this->data as $i) {
            $item = new Product();
            $item->setName($i['name']);
            $em->persist($item);
        }

        $em->flush();
        $em->clear();
    }
}
