<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class ProductVisibilityMessageFactory implements MessageFactoryInterface
{
    const PRODUCT_ID = 'product';
    const WEBSITE_ID = 'website';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param object|ProductVisibility $visibility
     * @return array
     */
    public function createMessage($visibility)
    {
        return [
            self::PRODUCT_ID => $visibility->getProduct()->getId(),
            self::WEBSITE_ID => $visibility->getWebsite()->getId(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityFromMessage($data)
    {
        if (!is_array($data) || empty($data)) {
            throw new InvalidArgumentException('Message should not be empty.');
        }

        $visibility = $this->registry->getManagerForClass(ProductVisibility::class)
            ->getRepository(ProductVisibility::class)
            ->findOneBy(['product' => $data[self::PRODUCT_ID], 'website' => $data[self::WEBSITE_ID]]);

        if (!$visibility) {
            $visibility = $this->createDefaultVisibility($data);
        }

        return $visibility;
    }

    /**
     * @param array $data
     * @return ProductVisibility
     */
    protected function createDefaultVisibility(array $data)
    {
        $product = $this->registry->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->find($data[self::PRODUCT_ID]);
        $website = $this->registry->getManagerForClass(Website::class)
            ->getRepository(Website::class)
            ->find($data[self::WEBSITE_ID]);
        if (!$product || !$website) {
            throw new InvalidArgumentException('Required objects was not found.');
        }
        $visibility = new ProductVisibility();
        $visibility->setProduct($product);
        $visibility->setWebsite($website);
        $visibility->setVisibility(ProductVisibility::getDefault($product));

        return $visibility;
    }
}
