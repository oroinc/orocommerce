<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Clears expired, not completed guest checkouts and their sources.
 */
#[AsCommand(
    name: 'oro:cron:checkout:clear-expired-guest-checkouts',
    description: 'Clears expired, not completed guest checkouts and their sources.'
)]
class ClearExpiredGuestCheckoutsCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    private const int CHUNK_SIZE = 10000;

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly ConfigManager $configManager
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command clears not completed guest checkouts (and their sources) that have not been
updated for the timeframe defined by "Customer visitor cookie lifetime (days)" system configuration setting.

HELP
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        /** @var Connection $connection */
        $connection = $this->doctrine->getManagerForClass(Checkout::class)->getConnection();

        $expiredDate = $this->getExpiredDate();
        do {
            $selectQB = $connection->createQueryBuilder();
            $rows = $selectQB
                ->select('c.id AS checkout_id, c.source_id AS source_id')
                ->from('oro_checkout', 'c')
                ->leftJoin('c', 'oro_customer_user', 'cu', 'cu.id = c.customer_user_id')
                ->where($selectQB->expr()->or(
                    $selectQB->expr()->isNull('c.customer_user_id'),
                    $selectQB->expr()->eq('cu.is_guest', 'true')
                ))
                ->andWhere($selectQB->expr()->eq('c.completed', 'false'))
                ->andWhere($selectQB->expr()->isNull('c.order_id'))
                ->andWhere($selectQB->expr()->lte('c.updated_at', ':expiredDate'))
                ->setParameter('expiredDate', $expiredDate, Types::DATETIME_MUTABLE)
                ->setMaxResults(self::CHUNK_SIZE)
                ->execute()
                ->fetchAllAssociative();

            if (!$rows) {
                break;
            }

            $checkoutIds = array_column($rows, 'checkout_id');
            $sourceIds = array_column($rows, 'source_id');

            $connection->beginTransaction();
            try {
                // Cascades at DB level: oro_checkout_line_item, oro_checkout_subtotal,
                // and oro_checkout_product_kit_item_line_item are all CASCADE from checkout.
                $connection->createQueryBuilder()
                    ->delete('oro_checkout')
                    ->where('id IN (:ids)')
                    ->setParameter('ids', $checkoutIds, Connection::PARAM_INT_ARRAY)
                    ->execute();

                // oro_checkout.source_id FK has no ON DELETE, so source must be removed after checkout.
                $connection->createQueryBuilder()
                    ->delete('oro_checkout_source')
                    ->where('id IN (:ids)')
                    ->setParameter('ids', $sourceIds, Connection::PARAM_INT_ARRAY)
                    ->execute();

                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();

                throw $e;
            }
        } while (\count($rows) === self::CHUNK_SIZE);

        $symfonyStyle->success('Clear expired guest checkouts completed');

        return Command::SUCCESS;
    }

    protected function getExpiredDate(): \DateTime
    {
        $cookieLifetime = $this->configManager->get('oro_customer.customer_visitor_cookie_lifetime_days');

        $expiredDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $expiredDate->modify(\sprintf('-%d seconds', $cookieLifetime * 86400));

        return $expiredDate;
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 2 * * *';
    }
}
