<?php

namespace OroB2B\Bundle\PricingBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;

use OroB2B\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceRuleChangeTriggerRepository;

class ProductPricesRecalculationCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const NAME = 'oro:cron:product-price:recalculate';
    const FORCE = 'force';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addOption(self::FORCE)
            ->setDescription('Recalculate product prices by rules');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ProductPriceBuilder $builer */
        $priceBuilder = $this->getContainer()->get('orob2b_pricing.builder.product_price_builder');

        /** @var PriceListProductAssignmentBuilder $assignmentBuilder */
        $assignmentBuilder = $this->getContainer()
            ->get('orob2b_pricing.ebuilder.price_list_product_assignment_builder');

        /** @var PriceListRepository $priceListRepository */
        $priceListRepository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceList::class)
            ->getRepository(PriceList::class);

        /** @var PriceRuleChangeTriggerRepository $triggerRepository */
        $triggerRepository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(PriceRuleChangeTrigger::class)
            ->getRepository(PriceRuleChangeTrigger::class);

        if ((bool)$input->getOption(self::FORCE)) {
            $triggerRepository->deleteAll();
            $priceListIterator = $priceListRepository->getPriceListsWithRules();
            foreach ($priceListIterator as $priceList) {
                $assignmentBuilder->buildByPriceList($priceList);
                $priceBuilder->buildByPriceList($priceList);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }
}
