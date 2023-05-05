<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesTopic;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\AuditMessageBodyProvider;
use Oro\Bundle\DataAuditBundle\Service\EntityToEntityChangeArrayConverter;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPriceRemove;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdated;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Audit listener for the ProductPrice entity.
 */
class SendChangedProductPricesToMessageQueueListener implements OptionalListenerInterface
{
    private const BATCH_SIZE = 100;

    /** @var MessageProducerInterface */
    private $messageProducer;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var EntityToEntityChangeArrayConverter */
    private $entityToArrayConverter;

    /** @var AuditConfigProvider */
    private $auditConfigProvider;

    /** @var EntityNameResolver */
    private $entityNameResolver;

    /** @var AuditMessageBodyProvider */
    private $auditMessageBodyProvider;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var LoggerInterface */
    private $logger;

    /** @var \SplObjectStorage */
    private $allInsertions;

    /** @var \SplObjectStorage */
    private $allUpdates;

    /** @var \SplObjectStorage */
    private $allDeletions;

    /** @var \SplObjectStorage */
    private $allTokens;

    /** @var bool */
    private $enabled = true;

    private ApplicationState $applicationState;

    public function __construct(
        MessageProducerInterface $messageProducer,
        TokenStorageInterface $tokenStorage,
        EntityToEntityChangeArrayConverter $entityToArrayConverter,
        AuditConfigProvider $auditConfigProvider,
        EntityNameResolver $entityNameResolver,
        AuditMessageBodyProvider $auditMessageBodyProvider,
        PropertyAccessorInterface $propertyAccessor,
        LoggerInterface $logger,
        ApplicationState $applicationState
    ) {
        $this->messageProducer = $messageProducer;
        $this->tokenStorage = $tokenStorage;
        $this->entityToArrayConverter = $entityToArrayConverter;
        $this->auditConfigProvider = $auditConfigProvider;
        $this->entityNameResolver = $entityNameResolver;
        $this->auditMessageBodyProvider = $auditMessageBodyProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->logger = $logger;
        $this->applicationState = $applicationState;

        $this->allInsertions = new \SplObjectStorage;
        $this->allUpdates = new \SplObjectStorage;
        $this->allDeletions = new \SplObjectStorage;
        $this->allTokens = new \SplObjectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = $enabled;
    }

    private function isEnabled(): bool
    {
        if (!$this->applicationState->isInstalled()) {
            return false;
        }
        return $this->enabled;
    }

    public function onSave(ProductPriceSaveAfterEvent $event)
    {
        if (!$this->isEnabled() || !$this->auditConfigProvider->isAuditableEntity(ProductPrice::class)) {
            return;
        }

        /** @var ProductPrice $price */
        $args = $event->getEventArgs();

        /** @var EntityManager $em */
        $em = $args->getEntityManager();

        $uow = $em->getUnitOfWork();
        $price = $args->getEntity();

        $idChanged = $args->hasChangedField('id');
        if ($uow->getOriginalEntityData($price) && (!$idChanged || ($idChanged && $args->getOldValue('id')))) {
            $updates = new \SplObjectStorage();
            $updates[$price] = $uow->getEntityChangeSet($price);

            $this->saveChanges($this->allUpdates, $em, $updates);
        } else {
            $insertions = new \SplObjectStorage();
            $insertions[$price] = $this->getChangeSet($em, $price, true);

            $this->saveChanges($this->allInsertions, $em, $insertions);
        }

        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $this->allTokens[$em] = $token;
        }
    }

    public function onRemove(ProductPriceRemove $event)
    {
        if (!$this->isEnabled() || !$this->auditConfigProvider->isAuditableEntity(ProductPrice::class)) {
            return;
        }

        /** @var EntityManager $em */
        $em = $event->getEntityManager();

        $price = $event->getPrice();

        $changeSet = $this->getChangeSet($em, $price, false);

        $entityName = $this->entityNameResolver->getName($price);
        $deletion = $this->convertEntityToArray($em, $price, $changeSet, $entityName);

        $deletions = new \SplObjectStorage();
        $deletions[$price] = $deletion;

        if (null === $deletion['entity_id']) {
            $this->logger->error(
                sprintf('The entity "%s" has an empty id.', $deletion['entity_class']),
                ['entity' => $price, 'deletion' => $deletion]
            );
        }

        $this->saveChanges($this->allDeletions, $em, $deletions);

        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $this->allTokens[$em] = $token;
        }
    }

    public function onUpdated(ProductPricesUpdated $eventArgs)
    {
        if (!$this->isEnabled()) {
            return;
        }

        $em = $eventArgs->getEntityManager();

        try {
            $insertions = $this->processInsertions($em);
            $updates = $this->processUpdates($em);
            $deletes = $this->processDeletions($em);

            do {
                $body = $this->auditMessageBodyProvider->prepareMessageBody(
                    array_splice($insertions, 0, self::BATCH_SIZE),
                    array_splice($updates, 0, self::BATCH_SIZE),
                    array_splice($deletes, 0, self::BATCH_SIZE),
                    [],
                    $this->getSecurityToken($em)
                );

                if (!empty($body)) {
                    $this->messageProducer->send(
                        AuditChangedEntitiesTopic::getName(),
                        new Message($body, MessagePriority::VERY_LOW)
                    );
                }
            } while ($body);
        } finally {
            $this->allInsertions->detach($em);
            $this->allUpdates->detach($em);
            $this->allDeletions->detach($em);
            $this->allTokens->detach($em);
        }
    }

    private function getSecurityToken(EntityManager $em): ?TokenInterface
    {
        return $this->allTokens->contains($em) ? $this->allTokens[$em] : $this->tokenStorage->getToken();
    }

    private function saveChanges(\SplObjectStorage $storage, EntityManager $em, \SplObjectStorage $changes)
    {
        if ($changes->count() > 0) {
            if (!$storage->contains($em)) {
                $storage[$em] = $changes;
            } else {
                $previousChangesInCurrentTransaction = $storage[$em];
                $changes->addAll($previousChangesInCurrentTransaction);
                $storage[$em] = $changes;
            }
        }
    }

    /**
     * @param EntityManager $em
     *
     * @return array
     */
    private function processInsertions(EntityManager $em)
    {
        if (!$this->allInsertions->contains($em)) {
            return [];
        }

        $insertions = [];
        foreach ($this->allInsertions[$em] as $entity) {
            $changeSet = $this->allInsertions[$em][$entity];
            $insertions[$this->getEntityHash($entity)] = $this->convertEntityToArray($em, $entity, $changeSet);
        }

        return $insertions;
    }

    /**
     * @param EntityManager $em
     *
     * @return array
     */
    private function processUpdates(EntityManager $em)
    {
        $updates = [];
        if ($this->allUpdates->contains($em)) {
            foreach ($this->allUpdates[$em] as $entity) {
                $changeSet = $this->allUpdates[$em][$entity];
                $update = $this->processUpdate($em, $entity, $changeSet);
                if (!$update) {
                    continue;
                }

                $updates[$this->getEntityHash($entity)] = $update;
            }
        }

        return $updates;
    }

    /**
     * @param EntityManager $entityManager
     * @param object        $entity
     * @param array         $changeSet
     *
     * @return array|null
     */
    private function processUpdate(EntityManager $entityManager, $entity, array $changeSet)
    {
        $update = $this->convertEntityToArray($entityManager, $entity, $changeSet);
        if (null !== $update['entity_id']) {
            return $update;
        }

        $this->logger->error(
            sprintf('The entity "%s" has an empty id.', $update['entity_class']),
            ['entity' => $entity, 'update' => $update]
        );

        return null;
    }

    /**
     * @param EntityManager $em
     *
     * @return array
     */
    private function processDeletions(EntityManager $em)
    {
        if (!$this->allDeletions->contains($em)) {
            return [];
        }

        $deletions = [];
        foreach ($this->allDeletions[$em] as $entity) {
            $deletions[$this->getEntityHash($entity)] = $this->allDeletions[$em][$entity];
        }

        return $deletions;
    }

    /**
     * @param EntityManager $em
     * @param object $entity
     * @param array $changeSet
     * @param string|null $entityName
     * @return array
     */
    private function convertEntityToArray(EntityManager $em, $entity, array $changeSet, $entityName = null)
    {
        return $this->entityToArrayConverter->convertNamedEntityToArray($em, $entity, $changeSet, $entityName);
    }

    /**
     * @param object $entity
     * @return string
     */
    private function getEntityHash($entity): string
    {
        return spl_object_hash($entity);
    }

    private function getChangeSet(EntityManager $manager, ProductPrice $price, bool $asNewValues): array
    {
        $changeSet = [];

        $uow = $manager->getUnitOfWork();
        $classMetadata = $manager->getClassMetadata(ProductPrice::class);

        $fields = $this->auditConfigProvider->getAuditableFields(ProductPrice::class);
        $fields[] = $classMetadata->getSingleIdentifierFieldName();

        $originalData = $uow->getOriginalEntityData($price);
        foreach ($fields as $field) {
            try {
                if ($field === 'value') {
                    $priceModel = $price->getPrice();

                    $value = $priceModel ? $priceModel->getValue() : null;
                } elseif ($field === 'currency') {
                    $priceModel = $price->getPrice();

                    $value = $priceModel ? $priceModel->getCurrency() : null;
                } else {
                    $value = $this->propertyAccessor->getValue($price, $field) ?? $originalData[$field] ?? null;
                }
                $changeSet[$field] = [$asNewValues ? null : $value, $asNewValues ? $value : null];
            } catch (NoSuchPropertyException $e) {
            }
        }

        return $changeSet;
    }
}
