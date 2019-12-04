<?php

namespace Oro\Bundle\ShoppingListBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\CustomerBundle\DependencyInjection\Configuration;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cron command to clear all expired shopping lists of customer visitors
 */
class ClearExpiredShoppingListsCommand extends Command implements CronCommandInterface
{
    private const CHUNK_SIZE = 10000;

    /** @var string */
    protected static $defaultName = 'oro:cron:shopping-list:clear-expired';

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ExtendDbIdentifierNameGenerator */
    private $dbIdentifierNameGenerator;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ManagerRegistry $doctrine
     * @param ExtendDbIdentifierNameGenerator $dbIdentifierNameGenerator
     * @param ConfigManager $configManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ExtendDbIdentifierNameGenerator $dbIdentifierNameGenerator,
        ConfigManager $configManager
    ) {
        $this->doctrine = $doctrine;
        $this->dbIdentifierNameGenerator = $dbIdentifierNameGenerator;
        $this->configManager = $configManager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Clear expired guest shopping lists.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $connection */
        $connection = $this->doctrine->getEntityManager()->getConnection();
        $customerVisitorToShoppingListRelationTableName = $this->dbIdentifierNameGenerator
            ->generateManyToManyJoinTableName(CustomerVisitor::class, 'shoppingLists', ShoppingList::class);

        $expiredLastVisitDate = $this->getExpiredLastVisitDate();
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
                ->setParameter('expiredLastVisitDate', $expiredLastVisitDate, Type::DATETIME)
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
        $cookieLifetime = $this->configManager->get('oro_customer.customer_visitor_cookie_lifetime_days');

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
