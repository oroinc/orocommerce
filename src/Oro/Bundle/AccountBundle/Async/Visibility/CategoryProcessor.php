<?php

namespace Oro\Bundle\AccountBundle\Async\Visibility;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Visibility\Cache\Product\Category\CacheBuilder;
use Oro\Bundle\CatalogBundle\Model\CategoryMessageFactory;
use Oro\Bundle\CatalogBundle\Model\Exception\InvalidArgumentException;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

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
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param LoggerInterface $logger
     * @param CategoryMessageFactory $messageFactory
     * @param CacheBuilder $cacheBuilder
     */
    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        LoggerInterface $logger,
        CategoryMessageFactory $messageFactory,
        CacheBuilder $cacheBuilder
    ) {
        $this->registry = $registry;
        $this->logger = $logger;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->messageFactory = $messageFactory;
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * {@inheritdoc}
     */
    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManagerForClass(CategoryVisibility::class);
        $em->beginTransaction();
        try {
            $messageData = JSON::decode($message->getBody());
            $category = $this->messageFactory->getCategoryFromMessage($messageData);
            if ($category) {
                $this->cacheBuilder->categoryPositionChanged($category);
            } else {
                $this->setToDefaultProductVisibilityWithoutCategory();
                $this->setToDefaultAccountGroupProductVisibilityWithoutCategory();
                $this->setToDefaultAccountProductVisibilityWithoutCategory();
            }
        } catch (InvalidArgumentException $e) {
            $em->rollback();
            $this->logger->error(
                sprintf(
                    'Message is invalid: %s. Original message: "%s"',
                    $e->getMessage(),
                    $message->getBody()
                )
            );

            return self::REJECT;
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                sprintf(
                    'Transaction aborted wit error: %s.',
                    $e->getMessage()
                )
            );

            return self::REQUEUE;
        }

        return self::ACK;
    }

    protected function setToDefaultProductVisibilityWithoutCategory()
    {
        $this->registry->getManagerForClass(ProductVisibility::class)
            ->getRepository(ProductVisibility::class)
            ->setToDefaultWithoutCategory($this->insertFromSelectQueryExecutor);
    }

    protected function setToDefaultAccountGroupProductVisibilityWithoutCategory()
    {
        $this->registry->getManagerForClass(AccountGroupProductVisibility::class)
            ->getRepository(AccountGroupProductVisibility::class)
            ->setToDefaultWithoutCategory();
    }

    protected function setToDefaultAccountProductVisibilityWithoutCategory()
    {
        $this->registry->getManagerForClass(AccountProductVisibility::class)
            ->getRepository(AccountProductVisibility::class)
            ->setToDefaultWithoutCategory();
    }
}
