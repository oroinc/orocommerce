<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\Product;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductsToIndex extends AbstractFixture implements ContainerAwareInterface
{
    const ALIAS_TEMP = 'some_tmp_alias';
    const ALIAS_REAL = 'some_real_alias';

    /** @var ContainerInterface */
    protected $container;

    /** @var array */
    protected $data = [
        'product1' => [
            'name' => 'Product1'
        ],
        'product2' => [
            'name' => 'Product2'
        ],
        'product3' => [
            'name' => 'Product3'
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
