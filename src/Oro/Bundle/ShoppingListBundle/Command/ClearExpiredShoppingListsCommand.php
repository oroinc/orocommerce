<?php

namespace Oro\Bundle\ShoppingListBundle\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\CustomerBundle\DependencyInjection\Configuration;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearExpiredShoppingListsCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:shopping-list:clear-expired';

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
        $ids = $this->getExpiredShoppingListIds();
        if ($ids) {
            $registry = $this->getContainer()->get('doctrine');
            $manager = $registry->getManagerForClass(ShoppingList::class);
            /** @var EntityRepository $repository */
            $repository = $manager->getRepository(ShoppingList::class);

            $qb = $repository->createQueryBuilder('sl');
            $qb->delete(ShoppingList::class, 'sl');
            $qb->where($qb->expr()->in('sl.id', ':ids'));
            $qb->setParameter('ids', $ids);

            $qb->getQuery()->execute();
        }

        $output->writeln('<info>Clear expired guest shopping lists completed</info>');
    }

    /**
     * @return array
     */
    private function getExpiredShoppingListIds()
    {
        $expiredLastVisitDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $cookieLifetime = $this->getContainer()
            ->get('oro_config.manager')
            ->get('oro_customer.customer_visitor_cookie_lifetime_days');

        $expiredLastVisitDate->modify(sprintf(
            '-%d seconds',
            $cookieLifetime * Configuration::SECONDS_IN_DAY
        ));

        $registry = $this->getContainer()->get('doctrine');
        $manager = $registry->getManagerForClass(CustomerVisitor::class);
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository(CustomerVisitor::class);

        $qb = $repository->createQueryBuilder('cv');
        $qb->select(['sl.id']);
        $qb->leftJoin('cv.shoppingLists', 'sl');
        $qb->where($qb->expr()->lte('cv.lastVisit', ':expiredLastVisitDate'));
        $qb->setParameter('expiredLastVisitDate', $expiredLastVisitDate);

        $ids = [];
        foreach ($qb->getQuery()->getArrayResult() as $item) {
            $ids[] = $item['id'];
        }

        return $ids;
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
