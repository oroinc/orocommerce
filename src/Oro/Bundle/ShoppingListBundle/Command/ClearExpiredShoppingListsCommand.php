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
 * Cron command to clear all expired shopping lists of customer visitors
 */
class ClearExpiredShoppingListsCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:shopping-list:clear-expired';
    const CHUNK_SIZE = 10000;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Clear expired guest shopping lists.');
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

        do {
            $visitorsQB = $connection->createQueryBuilder();
            $visitorsQB->select('rel.shoppinglist_id')
                ->from('oro_customer_visitor', 'cv')
                ->innerJoin(
                    'cv',
                    $customerVisitorToShoppingListRelationTableName,
                    'rel',
                    'cv.id = rel.customervisitor_id'
                )
                ->where($visitorsQB->expr()->lte('cv.last_visit', ':expiredLastVisitDate'))
                ->setParameter('expiredLastVisitDate', $this->getExpiredLastVisitDate(), Type::DATETIME)
                ->setMaxResults(self::CHUNK_SIZE);
            $visitorIds = $visitorsQB->execute()->fetchAll(\PDO::FETCH_COLUMN);

            $deleteQB = $connection->createQueryBuilder();
            $deleteQB->delete('oro_shopping_list')
                ->where('id IN (:visitorIds)');
            $deleteQB->setParameter('visitorIds', $visitorIds, Connection::PARAM_INT_ARRAY);

            $deletedCount = $deleteQB->execute();
        } while ($deletedCount > 0);

        $output->writeln('<info>Clear expired guest shopping lists completed</info>');
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
        return '0 0 * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return true;
    }
}
