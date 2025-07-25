<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears old data in shopping list database table.
 */
#[AsCommand(
    name: 'oro:cron:shopping-list:clear-expired',
    description: 'Clears old data in shopping list database tables.'
)]
class ClearExpiredShoppingListsCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    private const CHUNK_SIZE = 10000;

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

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears old data in shopping list database tables for customer visitors with
the last visit past the timeframe defined by "Customer visitor cookie lifetime (days)" system configuration setting.

HELP
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Connection $connection */
        $connection = $this->doctrine->getManagerForClass(ShoppingList::class)->getConnection();
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
                ->setParameter('expiredLastVisitDate', $expiredLastVisitDate, Types::DATETIME_MUTABLE)
                ->setMaxResults(self::CHUNK_SIZE);
            $visitorIds = $visitorsQB->execute()->fetchFirstColumn();

            $deleteQB = $connection->createQueryBuilder();
            $deleteQB->delete('oro_shopping_list')
                ->where('id IN (:visitorIds)');
            $deleteQB->setParameter('visitorIds', $visitorIds, Connection::PARAM_INT_ARRAY);

            $deletedCount = $deleteQB->execute();
        } while ($deletedCount > 0);

        $output->writeln('<info>Clear expired guest shopping lists completed</info>');

        return Command::SUCCESS;
    }

    protected function getExpiredLastVisitDate(): \DateTime
    {
        $cookieLifetime = $this->configManager->get('oro_customer.customer_visitor_cookie_lifetime_days');
        $expiredLastVisitDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $expiredLastVisitDate->modify(\sprintf('-%d seconds', $cookieLifetime * 86400 /* seconds in day */));

        return $expiredLastVisitDate;
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 0 * * *';
    }
}
