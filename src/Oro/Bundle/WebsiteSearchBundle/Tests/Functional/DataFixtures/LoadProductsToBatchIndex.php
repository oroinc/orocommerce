<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadProductsToBatchIndex extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const AMOUNT = 20;

    public const REFERENCE = 'product';
    public const NAME = 'Product ';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= self::AMOUNT; ++ $i) {
            $product = new TestProduct();
            $product->setName(self::NAME.$i);
            $manager->persist($product);

            $this->addReference(self::REFERENCE.$i, $product);
        }

        $manager->flush();
        $manager->clear();
    }
}
