<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;

trait IndexTextTrait
{
    /**
     * Workaround to clear MyISAM table as it's not rolled back by transaction.
     */
    public function truncateIndexTextTable()
    {
        /** @var EntityRepository $repository */
        /** @var WebTestCase $this */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(IndexText::class)
            ->getRepository(IndexText::class);

        $repository->createQueryBuilder('t')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
