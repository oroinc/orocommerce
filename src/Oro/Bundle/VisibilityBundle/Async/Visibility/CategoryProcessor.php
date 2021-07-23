<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CustomerProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\ProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CacheBuilder;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updates visibility of a category.
 */
class CategoryProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var InsertFromSelectQueryExecutor */
    private $insertFromSelectQueryExecutor;

    /** @var LoggerInterface */
    private $logger;

    /** @var CacheBuilder */
    private $cacheBuilder;

    /** @var ScopeManager */
    private $scopeManager;

    public function __construct(
        ManagerRegistry $doctrine,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        LoggerInterface $logger,
        CacheBuilder $cacheBuilder,
        ScopeManager $scopeManager
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->cacheBuilder = $cacheBuilder;
        $this->scopeManager = $scopeManager;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::CATEGORY_POSITION_CHANGE, Topics::CATEGORY_REMOVE];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (!\is_array($body)) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(CategoryVisibilityResolved::class);
        $em->beginTransaction();
        try {
            $category = $this->getCategory($body);
            if ($category) {
                $this->cacheBuilder->categoryPositionChanged($category);
            } else {
                $this->setToDefaultProductVisibilityWithoutCategory();
                $this->setToDefaultCustomerGroupProductVisibilityWithoutCategory();
                $this->setToDefaultCustomerProductVisibilityWithoutCategory();
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during update Category Visibility.',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    /**
     * @throws EntityNotFoundException if a category with ID specified in the message body does not exist
     */
    public function getCategory(array $body): ?Category
    {
        if (!isset($body['id'])) {
            return null;
        }

        /** @var Category|null $category */
        $category = $this->doctrine->getManagerForClass(Category::class)
            ->find(Category::class, $body['id']);
        if (null === $category) {
            throw new EntityNotFoundException('Category was not found.');
        }

        return $category;
    }

    private function setToDefaultProductVisibilityWithoutCategory(): void
    {
        /** @var ProductVisibilityRepository $repository */
        $repository = $this->doctrine->getManagerForClass(ProductVisibility::class)
            ->getRepository(ProductVisibility::class);
        $scopes = $this->scopeManager->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE);
        foreach ($scopes as $scope) {
            $repository->setToDefaultWithoutCategory($this->insertFromSelectQueryExecutor, $scope);
        }
    }

    private function setToDefaultCustomerGroupProductVisibilityWithoutCategory(): void
    {
        /** @var CustomerProductVisibilityRepository $repository */
        $repository = $this->doctrine->getManagerForClass(CustomerGroupProductVisibility::class)
            ->getRepository(CustomerGroupProductVisibility::class);
        $repository->setToDefaultWithoutCategory();
    }

    private function setToDefaultCustomerProductVisibilityWithoutCategory(): void
    {
        /** @var CustomerProductVisibilityRepository $repository */
        $repository = $this->doctrine->getManagerForClass(CustomerProductVisibility::class)
            ->getRepository(CustomerProductVisibility::class);
        $repository->setToDefaultWithoutCategory();
    }
}
