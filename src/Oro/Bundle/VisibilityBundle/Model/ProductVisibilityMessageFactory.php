<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Model\Exception\InvalidArgumentException;

class ProductVisibilityMessageFactory implements MessageFactoryInterface
{
    const ID = 'id';
    const ENTITY_CLASS_NAME = 'entity_class_name';
    const PRODUCT_ID = 'product';
    const SCOPE_ID = 'scope';

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
     * @param object|VisibilityInterface $visibility
     * @return array
     */
    public function createMessage($visibility)
    {
        if ($visibility instanceof ProductVisibility
            || $visibility instanceof AccountProductVisibility
            || $visibility instanceof AccountGroupProductVisibility
        ) {
            return [
                self::ID => $visibility->getId(),
                self::ENTITY_CLASS_NAME => ClassUtils::getClass($visibility),
                self::PRODUCT_ID => $visibility->getTargetEntity()->getId(),
                self::SCOPE_ID => $visibility->getScope()->getId(),
            ];
        }
        throw new InvalidArgumentException('Unsupported entity class');
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityFromMessage($data)
    {
        if (!is_array($data) || empty($data)) {
            throw new InvalidArgumentException('Message should not be empty.');
        }
        if (empty($data[self::ENTITY_CLASS_NAME])) {
            throw new InvalidArgumentException('Message should contain entity name.');
        }
        if (empty($data[self::ID])) {
            throw new InvalidArgumentException('Message should contain entity id.');
        }

        $visibility = $this->registry->getManagerForClass($data[self::ENTITY_CLASS_NAME])
            ->getRepository($data[self::ENTITY_CLASS_NAME])
            ->find($data[self::ID]);
        if (!$visibility) {
            switch ($data[self::ENTITY_CLASS_NAME]) {
                case ProductVisibility::class:
                    $visibility = $this->createDefaultProductVisibility($data);
                    break;
                case AccountProductVisibility::class:
                    $visibility = $this->createDefaultAccountProductVisibility($data);
                    break;
                case AccountGroupProductVisibility::class:
                    $visibility = $this->createDefaultAccountGroupProductVisibility($data);
                    break;
            }
        }

        return $visibility;
    }

    /**
     * @param array $data
     * @return ProductVisibility
     */
    protected function createDefaultProductVisibility(array $data)
    {
        $product = $this->registry->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->find($data[self::PRODUCT_ID]);
        $scope = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class)
            ->find($data[self::SCOPE_ID]);
        if (!$product) {
            throw new InvalidArgumentException('Product object was not found.');
        }
        if (!$scope) {
            throw new InvalidArgumentException('Scope object was not found.');
        }
        $visibility = new ProductVisibility();
        $visibility->setScope($scope);
        $visibility->setProduct($product);
        $visibility->setVisibility(ProductVisibility::getDefault($product));

        return $visibility;
    }

    /**
     * @param array $data
     * @return AccountProductVisibility
     */
    protected function createDefaultAccountProductVisibility(array $data)
    {
        $product = $this->registry->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->find($data[self::PRODUCT_ID]);
        $scope = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class)
            ->find($data[self::SCOPE_ID]);

        if (!$product) {
            throw new InvalidArgumentException('Product object was not found.');
        }
        if (!$scope) {
            throw new InvalidArgumentException('Scope object was not found.');
        }
        $visibility = new AccountProductVisibility();
        $visibility->setScope($scope);
        $visibility->setProduct($product);
        $visibility->setVisibility(AccountProductVisibility::getDefault($product));

        return $visibility;
    }

    /**
     * @param array $data
     * @return AccountGroupProductVisibility
     */
    protected function createDefaultAccountGroupProductVisibility(array $data)
    {
        $product = $this->registry->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->find($data[self::PRODUCT_ID]);
        $scope = $this->registry->getManagerForClass(Scope::class)
            ->getRepository(Scope::class)
            ->find($data[self::SCOPE_ID]);
        if (!$scope) {
            throw new InvalidArgumentException('Scope object was not found.');
        }
        $visibility = new AccountGroupProductVisibility();
        $visibility->setProduct($product);
        $visibility->setScope($scope);
        $visibility->setVisibility(AccountGroupProductVisibility::getDefault($product));

        return $visibility;
    }
}
