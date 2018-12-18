<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CatalogBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\CustomerProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Model\CategoryMessageFactory;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CacheBuilder;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * Updates visibility of the Category
 */
class CategoryProcessor implements MessageProcessorInterface
{
    /**
     * @var CategoryMessageFactory
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CategoryMessageFactory
     */
    protected $messageFactory;

    /**
     * @var CacheBuilder
     */
    protected $cacheBuilder;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param LoggerInterface $logger
     * @param CategoryMessageFactory $messageFactory
     * @param CacheBuilder $cacheBuilder
     * @param ScopeManager $scopeManager
     */
    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        LoggerInterface $logger,
        CategoryMessageFactory $messageFactory,
        CacheBuilder $cacheBuilder,
        ScopeManager $scopeManager
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->messageFactory = $messageFactory;
        $this->cacheBuilder = $cacheBuilder;
        $this->scopeManager = $scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(CategoryVisibilityResolved::class);
        $em->beginTransaction();
        try {
            $messageData = JSON::decode($message->getBody());
            $category = $this->messageFactory->getCategoryFromMessage($messageData);
            if ($category) {
                $this->cacheBuilder->categoryPositionChanged($category);
            } else {
                $this->setToDefaultProductVisibilityWithoutCategory();
                $this->setToDefaultCustomerGroupProductVisibilityWithoutCategory();
                $this->setToDefaultCustomerProductVisibilityWithoutCategory();
            }
            $em->commit();
        } catch (InvalidArgumentException $e) {
            $em->rollback();
            $this->logger->error(sprintf('Message is invalid: %s', $e->getMessage()));

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Category visibility resolve',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    protected function setToDefaultProductVisibilityWithoutCategory()
    {
        $scopes = $this->scopeManager->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE);
        $repository = $this->registry->getManagerForClass(ProductVisibility::class)
            ->getRepository(ProductVisibility::class);
        foreach ($scopes as $scope) {
            $repository->setToDefaultWithoutCategory($this->insertFromSelectQueryExecutor, $scope);
        }
    }

    protected function setToDefaultCustomerGroupProductVisibilityWithoutCategory()
    {
        /** @var CustomerProductVisibilityRepository $repository */
        $repository = $this->registry->getManagerForClass(CustomerGroupProductVisibility::class)
            ->getRepository(CustomerGroupProductVisibility::class);
        $repository->setToDefaultWithoutCategory();
    }

    protected function setToDefaultCustomerProductVisibilityWithoutCategory()
    {
        $this->registry->getManagerForClass(CustomerProductVisibility::class)
            ->getRepository(CustomerProductVisibility::class)
            ->setToDefaultWithoutCategory();
    }
}
