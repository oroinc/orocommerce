<?php

namespace Oro\Bundle\AccountBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class ProductVisibilityMessageFactory implements MessageFactoryInterface
{
    const ID = 'id';
    const ENTITY_CLASS_NAME = 'entity_class_name';
    const PRODUCT_ID = 'product';
    const WEBSITE_ID = 'website';
    const ACCOUNT_ID = 'account';
    const ACCOUNT_GROUP_ID = 'account_group';

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
        $entityClass = ClassUtils::getClass($visibility);
        switch ($entityClass) {
            case ProductVisibility::class:
                $message = $this->productVisibilityToArray($visibility);
                break;
            case AccountProductVisibility::class:
                $message = $this->accountProductVisibilityToArray($visibility);
                break;
            case AccountGroupProductVisibility::class:
                $message = $this->accountGroupProductVisibilityToArray($visibility);
                break;
            default:
                throw new InvalidArgumentException('Unsupported entity class');
        }

        return $message;
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
        $website = $this->registry->getManagerForClass(Website::class)
            ->getRepository(Website::class)
            ->find($data[self::WEBSITE_ID]);
        if (!$product) {
            throw new InvalidArgumentException('Product object was not found.');
        }
        if (!$website) {
            throw new InvalidArgumentException('Website object was not found.');
        }
        $visibility = new ProductVisibility();
        $visibility->setProduct($product);
        $visibility->setWebsite($website);
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
        $website = $this->registry->getManagerForClass(Website::class)
            ->getRepository(Website::class)
            ->find($data[self::WEBSITE_ID]);
        $account = $this->registry->getManagerForClass(Account::class)
            ->getRepository(Account::class)
            ->find($data[self::ACCOUNT_ID]);
        if (!$product) {
            throw new InvalidArgumentException('Product object was not found.');
        }
        if (!$website) {
            throw new InvalidArgumentException('Website object was not found.');
        }
        if (!$account) {
            throw new InvalidArgumentException('Account object was not found.');
        }
        $visibility = new AccountProductVisibility();
        $visibility->setProduct($product);
        $visibility->setWebsite($website);
        $visibility->setAccount($account);
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
        $website = $this->registry->getManagerForClass(Website::class)
            ->getRepository(Website::class)
            ->find($data[self::WEBSITE_ID]);
        $accountGroup = $this->registry->getManagerForClass(AccountGroup::class)
            ->getRepository(AccountGroup::class)
            ->find($data[self::ACCOUNT_GROUP_ID]);
        if (!$product) {
            throw new InvalidArgumentException('Product object was not found.');
        }
        if (!$website) {
            throw new InvalidArgumentException('Website object was not found.');
        }
        if (!$accountGroup) {
            throw new InvalidArgumentException('AccountGroup object was not found.');
        }
        $visibility = new AccountGroupProductVisibility();
        $visibility->setProduct($product);
        $visibility->setWebsite($website);
        $visibility->setAccountGroup($accountGroup);
        $visibility->setVisibility(AccountGroupProductVisibility::getDefault($product));

        return $visibility;
    }

    /**
     * @param VisibilityInterface|ProductVisibility $visibility
     * @return array
     */
    protected function productVisibilityToArray(VisibilityInterface $visibility)
    {
        return [
            self::ID => $visibility->getId(),
            self::ENTITY_CLASS_NAME => ClassUtils::getClass($visibility),
            self::PRODUCT_ID => $visibility->getProduct()->getId(),
            self::WEBSITE_ID => $visibility->getWebsite()->getId(),
        ];
    }

    /**
     * @param VisibilityInterface|AccountProductVisibility $visibility
     * @return array
     */
    protected function accountProductVisibilityToArray(VisibilityInterface $visibility)
    {
        $data = $this->productVisibilityToArray($visibility);
        $data[self::ACCOUNT_ID] = $visibility->getAccount()->getId();

        return $data;
    }

    /**
     * @param VisibilityInterface|AccountGroupProductVisibility $visibility
     * @return array
     */
    protected function accountGroupProductVisibilityToArray(VisibilityInterface $visibility)
    {
        $data = $this->productVisibilityToArray($visibility);
        $data[self::ACCOUNT_GROUP_ID] = $visibility->getAccountGroup()->getId();

        return $data;
    }
}
