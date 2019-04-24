<?php

namespace Oro\Bundle\ShoppingListBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\CustomerBundle\DependencyInjection\Configuration;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron command to clear all expired customer visitors
 */
class ClearExpiredCustomerVisitorsCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:customer-visitors:clear-expired';
    const CHUNK_SIZE = 10000;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Clear expired customer visitors.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine.orm.entity_manager')->getConnection();
        /** @var ExtendDbIdentifierNameGenerator $dbIdentifierNameGenerator */
        $dbIdentifierNameGenerator = $this->getContainer()->get('oro_entity_extend.db_id_name_generator');
        $customerVisitorToShoppingListRelationTableName = $dbIdentifierNameGenerator->generateManyToManyJoinTableName(
            CustomerVisitor::class,
            'shoppingLists',
            ShoppingList::class
        );

        $expiredLastVisitDate = $this->getExpiredLastVisitDate();
        do {
            $visitorsQB = $connection->createQueryBuilder();
            $visitorsQB->select('cv.id')
                ->from('oro_customer_visitor', 'cv')
                ->leftJoin(
                    'cv',
                    $customerVisitorToShoppingListRelationTableName,
                    'rel',
                    'cv.id = rel.customervisitor_id'
                )
                ->where($visitorsQB->expr()->andX(
                    $visitorsQB->expr()->lte('cv.last_visit', ':expiredLastVisitDate'),
                    $visitorsQB->expr()->isNull('rel.customervisitor_id')
                ))
                ->setParameter('expiredLastVisitDate', $expiredLastVisitDate, Type::DATETIME)
                ->setMaxResults(self::CHUNK_SIZE);

            $visitorIds = $visitorsQB->execute()->fetchAll(\PDO::FETCH_COLUMN);

            $deleteQB = $connection->createQueryBuilder();
            $deleteQB->delete('oro_customer_visitor')
                ->where('id IN (:visitorIds)')
                ->setParameter('visitorIds', $visitorIds, Connection::PARAM_INT_ARRAY);

            $deletedCount = $deleteQB->execute();
        } while ($deletedCount > 0);

        $output->writeln('<info>Clear expired customer visitors completed</info>');
    }

    /**
     * @return \DateTime
     */
    protected function getExpiredLastVisitDate()
    {
        $expiredLastVisitDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $cookieLifetime = $this->getContainer()
            ->get('oro_config.manager')
            ->get('oro_customer.customer_visitor_cookie_lifetime_days');

        $expiredLastVisitDate->modify(
            sprintf(
                '-%d seconds',
                $cookieLifetime * Configuration::SECONDS_IN_DAY
            )
        );

        return $expiredLastVisitDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return true;
    }
}
