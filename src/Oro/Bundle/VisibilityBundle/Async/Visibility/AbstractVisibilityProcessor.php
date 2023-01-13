<?php

namespace Oro\Bundle\VisibilityBundle\Async\Visibility;

use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * The base processor to resolve visibility by an entity.
 */
abstract class AbstractVisibilityProcessor implements MessageProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ManagerRegistry $doctrine;

    protected CacheBuilderInterface $cacheBuilder;

    public function __construct(ManagerRegistry $doctrine, CacheBuilderInterface $cacheBuilder)
    {
        $this->doctrine = $doctrine;
        $this->cacheBuilder = $cacheBuilder;
        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $body = $message->getBody();

        $entityManager = $this->doctrine->getManagerForClass($this->getResolvedVisibilityClassName());
        $entityManager->beginTransaction();
        try {
            $this->resolveVisibility($body);
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
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
