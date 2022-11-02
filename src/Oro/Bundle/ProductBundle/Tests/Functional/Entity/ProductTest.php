<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testDenormalizedDefaultNameField()
    {
        /** @var EntityManager $manager */
        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(
            Product::class
        );

        $defaultName = (new ProductName())->setString('Table chair');

        $product = new Product();
        $product->addName($defaultName);
        $product->setSku('ABC123');
        $manager->persist($product);
        $manager->flush();

        $this->assertEquals($product->getDenormalizedDefaultName(), $defaultName);
        $this->assertEquals($product->getDenormalizedDefaultName(), $product->getDefaultName());
        $this->assertEquals($product->getDenormalizedDefaultNameUppercase(), mb_strtoupper($product->getDefaultName()));

        $product->removeName($defaultName);
        $otherName = (new ProductName)->setString('Light lamp');
        $product->addName($otherName);
        $manager->persist($product);
        $manager->flush();

        $this->assertEquals($product->getDenormalizedDefaultName(), $otherName);
        $this->assertEquals($product->getDenormalizedDefaultName(), $product->getDefaultName());
        $this->assertEquals($product->getDenormalizedDefaultNameUppercase(), mb_strtoupper($product->getDefaultName()));
    }
}
