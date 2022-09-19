<?php
declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\CustomerBundle\DependencyInjection\Configuration;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears old data in customer visitor database table.
 */
class ClearExpiredCustomerVisitorsCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    private const CHUNK_SIZE = 10000;

    /** @var string */
    protected static $defaultName = 'oro:cron:customer-visitors:clear-expired';

    private ManagerRegistry $doctrine;
    private ExtendDbIdentifierNameGenerator $dbIdentifierNameGenerator;
    private ConfigManager $configManager;

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

    protected function configure()
    {
        $this->setDescription('Clears old data in customer visitor database table.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears old data in customer visitor database table for customer visitors with
the last visit past the timeframe defined by "Customer visitor cookie lifetime (days)" system configuration setting.

HELP
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $connection */
        $connection = $this->doctrine->getManager()->getConnection();
        $customerVisitorToShoppingListRelationTableName = $this->dbIdentifierNameGenerator
            ->generateManyToManyJoinTableName(CustomerVisitor::class, 'shoppingLists', ShoppingList::class);

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
                ->setParameter('expiredLastVisitDate', $expiredLastVisitDate, Types::DATETIME_MUTABLE)
                ->setMaxResults(self::CHUNK_SIZE);

            $visitorIds = $visitorsQB->execute()->fetchAll(\PDO::FETCH_COLUMN);

            $deleteQB = $connection->createQueryBuilder();
            $deleteQB->delete('oro_customer_visitor')
                ->where('id IN (:visitorIds)')
                ->setParameter('visitorIds', $visitorIds, Connection::PARAM_INT_ARRAY);

            $deletedCount = $deleteQB->execute();
        } while ($deletedCount > 0);

        $output->writeln('<info>Clear expired customer visitors completed</info>');

        return 0;
    }

    protected function getExpiredLastVisitDate(): \DateTime
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
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '0 1 * * *';
    }
}
