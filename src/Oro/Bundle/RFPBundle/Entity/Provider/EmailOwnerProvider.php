<?php

namespace Oro\Bundle\RFPBundle\Entity\Provider;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * The email address owner provider for RFP Request entity.
 */
class EmailOwnerProvider implements EmailOwnerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEmailOwnerClass(): string
    {
        return Request::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findEmailOwner(EntityManagerInterface $em, string $email): ?EmailOwnerInterface
    {
        $qb = $em->createQueryBuilder()
            ->from(Request::class, 'r')
            ->select('r')
            ->setParameter('email', $email)
            ->setMaxResults(1);
        if ($em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $qb->where('LOWER(r.email) = LOWER(:email)');
        } else {
            $qb->where('r.email = :email');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizations(EntityManagerInterface $em, string $email): array
    {
        $qb = $em->createQueryBuilder()
            ->from(Request::class, 'r')
            ->select('IDENTITY(r.organization) AS id')
            ->setParameter('email', $email);
        if ($em->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $qb->where('LOWER(r.email) = LOWER(:email)');
        } else {
            $qb->where('r.email = :email');
        }
        $rows = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (int)$row['id'];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmails(EntityManagerInterface $em, int $organizationId): iterable
    {
        $qb = $em->createQueryBuilder()
            ->from(Request::class, 'r')
            ->select('r.email')
            ->where('r.organization = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->orderBy('r.id');
        $iterator = new BufferedQueryResultIterator($qb, true);
        $iterator->setBufferSize(5000);
        foreach ($iterator as $row) {
            yield $row['email'];
        }
    }
}
