<?php
declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Model\PricingStorageSwitchHandlerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Switches pricing storage type.
 */
class SwitchPricingStorageCommand extends Command
{
    private const STORAGE_FLAT = 'flat';
    private const STORAGE_COMBINED = 'combined';
    private const SUPPORTED_STORAGES = [self::STORAGE_FLAT, self::STORAGE_COMBINED];

    /** @var string */
    protected static $defaultName = 'oro:price-lists:switch-pricing-storage';

    private ConfigManager $configManager;
    private PricingStorageSwitchHandlerInterface $pricingStorageSwitchHandler;

    public function __construct(
        ConfigManager $configManager,
        PricingStorageSwitchHandlerInterface $pricingStorageSwitchHandler
    ) {
        parent::__construct();
        $this->configManager = $configManager;
        $this->pricingStorageSwitchHandler = $pricingStorageSwitchHandler;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument(
                'storage',
                InputArgument::REQUIRED,
                'Storage type (' . \implode(', ', self::SUPPORTED_STORAGES) . ')'
            )
            ->setDescription('Switches pricing storage type.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command switches pricing store type.
Supported values: <comment>flat</comment>, <comment>combined</comment>.

  <info>php %command.full_name% <storage></info>

The flat price list storage allows no more than one price list association per record
(website, customer group, customer) but it consumes less space and computational resources
when you do not need the full power of price hierarchies and calculation formulas
provided by the calculated price lists.

HELP
            )
            ->addUsage('flat')
            ->addUsage('combined')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $storage = $input->getArgument('storage');

        if (!\in_array($storage, self::SUPPORTED_STORAGES, true)) {
            $output->writeln(sprintf(
                '<error>Unknown storage "%s". Possible storage options are: flat, combined</error>',
                $storage
            ));

            return self::FAILURE;
        }

        $currentStorage = $this->configManager->get('oro_pricing.price_storage');
        if ($currentStorage === $storage) {
            $output->writeln(sprintf(
                '<info>Pricing storage "%s" already selected.</info>',
                $storage
            ));

            return self::SUCCESS;
        }

        if ($storage === self::STORAGE_FLAT) {
            $output->writeln([
                '',
                '<comment>',
                'The flat price list storage allows no more than one price list association per record.',
                'All price list associations to websites, customer groups and customers',
                'except for the very first price list associated with a record will be removed.',
                '</comment>',
                ''
            ]);

            $question = new ConfirmationQuestion(
                'WARNING! Are you sure you wish to change pricing storage to flat? (Y/n) '
            );
            if ($input->isInteractive() && !$this->getHelper('question')->ask($input, $output, $question)) {
                $output->writeln('<error>Storage switching cancelled!</error>');

                return self::FAILURE;
            }

            $output->write('Reorganizing price lists associations');
            $this->pricingStorageSwitchHandler->moveAssociationsForFlatPricingStorage();
        } else {
            $output->write('Reorganizing price lists associations');
            $this->pricingStorageSwitchHandler->moveAssociationsForCombinedPricingStorage();
        }
        $output->writeln("\t<fg=green>Done</>");

        $this->switchStorage($storage);

        $output->writeln(['', sprintf('<info>Pricing storage was successfully switched to "%s"</info>', $storage)]);
        if ($storage === self::STORAGE_FLAT) {
            $output->writeln([
                '',
                '<comment>',
                'Website search index must be updated with new prices. Please execute:',
                'bin/console oro:website-search:reindex --env=prod --scheduled',
                '</comment>'
            ]);
        } else {
            $output->writeln([
                '',
                '<comment>',
                'Combined price lists recalculation required. Please execute:',
                'bin/console oro:price-lists:recalculate --env=prod --all',
                '</comment>'
            ]);
        }

        return self::SUCCESS;
    }

    protected function switchStorage(string $storage): void
    {
        $this->configManager->set('oro_pricing.price_storage', $storage);
        $this->configManager->flush();
    }
}
