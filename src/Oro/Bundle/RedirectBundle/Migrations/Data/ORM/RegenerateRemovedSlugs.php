<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\DBAL\ForwardCompatibility\DriverResultStatement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Regenerate removed slugs.
 */
class RegenerateRemovedSlugs extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager)
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $this->sendMissedSlugOnRegenerate($manager);
    }

    private function sendMissedSlugOnRegenerate(EntityManagerInterface $manager)
    {
        foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
            if (is_a($metadata->getName(), SluggableInterface::class, true)) {
                $this->prepareAndSendMessageOnSlugRegeneration($manager, $metadata);
            }
        }
    }

    private function prepareAndSendMessageOnSlugRegeneration(
        EntityManagerInterface $manager,
        ClassMetadata $metadata
    ): void {
        $relationMapping = $metadata->getAssociationMapping('slugs');
        $relationTable = $relationMapping['joinTable']['name'];
        $entityRelation = $relationMapping['joinTable']['joinColumns'][0]['name'];

        $expr = $manager->getExpressionBuilder();
        $rsm = ResultSetMappingUtil::createResultSetMapping(
            $manager->getConnection()->getDatabasePlatform()
        );
        $selectQB = new SqlQueryBuilder($manager, $rsm);
        $selectQB
            ->select('entity.id')
            ->from($metadata->getTableName(), 'entity')
            ->leftJoin(
                'entity',
                $relationTable,
                'slug_entity',
                $expr->eq(QueryBuilderUtil::getField('slug_entity', $entityRelation), 'entity.id')
            )
            ->where($selectQB->expr()->isNull(QueryBuilderUtil::getField('slug_entity', $entityRelation)));

        /** @var DriverResultStatement $result */
        $result = $selectQB->execute();
        $entityIds = $result->fetchFirstColumn();
        if ($entityIds) {
            /** @var MessageProducerInterface $messageProducer */
            $messageProducer = $this->container->get('oro_message_queue.message_producer');
            $messageFactory = $this->container->get('oro_redirect.direct_url_message_factory');
            $chunks = \array_chunk($entityIds, 100);
            foreach ($chunks as $chunk) {
                /**
                 * At this moment we expect to have empty MQ,
                 * so no need to worry about messages with option redirect=true.
                 * All removed slugs must be re-generated without any redirect,
                 * because they were invalid and don't work at all.
                 */
                $message = $messageFactory->createMassMessage(
                    $metadata->getName(),
                    $chunk,
                    false
                );
                $messageProducer->send(GenerateDirectUrlForEntitiesTopic::getName(), $message);
            }
        }
    }
}
