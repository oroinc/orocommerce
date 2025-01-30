<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemEntityInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base class for fixtures that load demo data for related products.
 */
abstract class AbstractLoadRelatedItemDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @return RelatedItemEntityInterface
     */
    abstract protected function getModel();

    /**
     * @return string
     */
    abstract protected function getFixtures();

    /**
     * @var ContainerInterface
     */
    protected $container;

    #[\Override]
    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    #[\Override]
    public function getDependencies()
    {
        return [LoadProductKitDemoData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $this->container->get('oro_product.repository.product');
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate($this->getFixtures());
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'rb');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $productFrom = $productRepository->findOneBy(['sku' => $data[0]]);
            $productTo = $productRepository->findOneBy(['sku' => $data[1]]);

            $relationBetweenProducts = $this->getModel();
            $relationBetweenProducts->setProduct($productFrom)
                ->setRelatedItem($productTo);

            $manager->persist($relationBetweenProducts);
        }

        fclose($handler);
        $manager->flush();
    }
}
