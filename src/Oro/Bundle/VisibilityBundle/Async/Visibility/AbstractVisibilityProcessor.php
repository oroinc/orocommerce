<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * The base processor to resolve visibility by an entity.
 */
abstract class AbstractVisibilityProcessor implements MessageProcessorInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var CacheBuilderInterface */
    protected $cacheBuilder;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        CacheBuilderInterface $cacheBuilder
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        if (!\is_array($body) || !$this->isMessageValid($body)) {
            $this->logger->critical('Got invalid message.');

            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass($this->getResolvedVisibilityClassName());
        $em->beginTransaction();
        try {
            $this->resolveVisibility($body);
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $this->logger->error(
                'Unexpected exception occurred during Product Visibility resolve.',
                ['exception' => $e]
            );

            if ($e instanceof RetryableException) {
                return self::REQUEUE;
            }

            return self::REJECT;
        }

        return self::ACK;
    }

    abstract protected function getResolvedVisibilityClassName(): string;

    protected function isMessageValid(array $body): bool
    {
        $result =
            isset($body['entity_class_name'], $body['id'])
            || isset(
                $body['entity_class_name'],
                $body['target_class_name'],
                $body['target_id'],
                $body['scope_id']
            );
        if ($result && !class_exists($body['entity_class_name'])) {
            $result = false;
        }

        return $result;
    }

    /**
     * @throws EntityNotFoundException if a visibility entity does not exist
     */
    abstract protected function resolveVisibility(array $body): void;

    /**
     * @throws EntityNotFoundException if a visibility entity does not exist
     */
    protected function getVisibility(array $body): VisibilityInterface
    {
        $entityClassName = $body['entity_class_name'];
        if (isset($body['id'])) {
            /** @var VisibilityInterface|null $visibility */
            $visibility = $this->doctrine->getManagerForClass($entityClassName)
                ->find($entityClassName, $body['id']);
            if (null === $visibility) {
                throw new EntityNotFoundException('Entity object was not found.');
            }

            return $visibility;
        }

        $targetClassName = $body['target_class_name'];
        $target = $this->doctrine->getManagerForClass($targetClassName)
            ->find($targetClassName, $body['target_id']);
        if (null === $target) {
            throw new EntityNotFoundException('Target object was not found.');
        }

        /** @var Scope|null $scope */
        $scope = $this->doctrine->getManagerForClass(Scope::class)
            ->find(Scope::class, $body['scope_id']);
        if (null === $scope) {
            throw new EntityNotFoundException('Scope object object was not found.');
        }

        /** @var VisibilityInterface $visibility */
        $visibility = new $entityClassName();
        $visibility->setScope($scope);
        $visibility->setTargetEntity($target);
        $visibility->setVisibility($visibility::getDefault($target));

        return $visibility;
    }
}
