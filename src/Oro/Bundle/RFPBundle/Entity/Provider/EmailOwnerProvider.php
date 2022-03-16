<?php

namespace Oro\Bundle\RFPBundle\Entity\Provider;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
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
    public function getEmailOwnerClass()
    {
        return Request::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findEmailOwner(EntityManager $em, $email)
    {
        $qb = $em->createQueryBuilder()
            ->from(Request::class, 'r')
            ->select('r')
            ->setParameter('email', $email);
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
    public function getOrganizations(EntityManager $em, $email)
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
    public function getEmails(EntityManager $em, $organizationId)
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
