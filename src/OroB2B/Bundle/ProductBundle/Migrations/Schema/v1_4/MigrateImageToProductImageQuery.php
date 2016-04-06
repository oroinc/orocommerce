<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_4;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\Hydration\IterableResult;

use Psr\Log\LoggerInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class MigrateImageToProductImageQuery extends ParametrizedMigrationQuery
{
    const BATCH_SIZE = 500;

    /**
     * @var array
     */
    protected $allImageTypes;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var EntityManager
     */
    protected $objectManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ImageTypeProvider $imageTypeProvider
     * @param string $productClass
     * @param string $productImageClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ImageTypeProvider $imageTypeProvider,
        $productClass,
        $productImageClass
    ) {
        $this->allImageTypes = array_keys($imageTypeProvider->getImageTypes());
        $this->productRepository = $doctrineHelper->getEntityRepositoryForClass($productClass);
        $this->objectManager = $doctrineHelper->getEntityManagerForClass($productImageClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->migrateData($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->migrateData($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    public function migrateData(LoggerInterface $logger, $dryRun = false)
    {
        $i = 0;
        $productIterable = $this->getProductsWithImageIterable($this->productRepository);

        foreach ($productIterable as $row) {
            /** @var Product $product */
            $product = $row[0];

            $logger->info(
                sprintf(
                    'Image "%s" from product %s (ID:%d) will be migrated to new product image',
                    $product->getImage(),
                    $product->getSku(),
                    $product->getId()
                )
            );

            if ($dryRun) {//log only
                continue;
            }

            $productImage = $this->createProductImage($product, $product->getImage(), $this->allImageTypes);
            $this->objectManager->persist($productImage);

            if (($i % self::BATCH_SIZE) === 0) {
                $this->objectManager->flush();
                $this->objectManager->clear();
            }

            ++$i;
        }

        if (!$dryRun) {
            $this->objectManager->flush();
        }
    }

    /**
     * @param ProductRepository $productRepository
     *
     * @return IterableResult
     */
    protected function getProductsWithImageIterable(ProductRepository $productRepository)
    {
        $queryBuilder = $productRepository->getProductsQueryBuilder();
        $queryBuilder->where($queryBuilder->expr()->isNotNull('p.image'));

        return $queryBuilder->getQuery()->iterate();
    }

    /**
     * @param Product $product
     * @param File $image
     * @param array $types
     *
     * @return ProductImage
     */
    protected function createProductImage(Product $product, File $image, array $types)
    {
        $productImage = new ProductImage();

        $productImage->setProduct($product);
        $productImage->setImage($image);
        $productImage->setTypes($types);

        return $productImage;
    }
}
