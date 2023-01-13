<?php
declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Imports prices from a CSV file to a specified price list.
 */
class PriceListImportCommand extends Command
{
    public const DEFAULT_PROCESSOR = 'oro_pricing_product_price.add_or_replace';
    public const DEFAULT_JOB_NAME  = 'price_list_product_prices_entity_import_from_csv';
    public const DEFAULT_VALIDATION_JOB_NAME = 'entity_import_validation_from_csv';

    /** * @var string */
    protected static $defaultName = 'oro:import:price-list:file';

    private FileManager $fileManager;
    private ImportHandler $importHandler;
    private MessageProducerInterface $messageProducer;
    private UserManager $userManager;

    public function __construct(
        FileManager $fileManager,
        ImportHandler $importHandler,
        MessageProducerInterface $messageProducer,
        UserManager $userManager
    ) {
        $this->fileManager = $fileManager;
        $this->importHandler = $importHandler;
        $this->messageProducer = $messageProducer;
        $this->userManager = $userManager;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'CSV file name')
            ->addOption('priceListId', null, InputOption::VALUE_REQUIRED, 'Price list ID')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email address of the user to notify')
            ->addOption('validation', null, InputOption::VALUE_NONE, 'Perform data validation instead of import')
            ->setDescription('Imports prices from a CSV file to a specified price list.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command imports prices from a CSV file
to a specified price list. Upon import completion the import log is sent
to the user whose email address is provided in the <info>--email</info> option.

  <info>php %command.full_name% --priceListId=<ID> --email=<email> <file></info>

The <info>--validation</info> option can be used to perform data validation instead of actual import:

  <info>php %command.full_name% --priceListId=<ID> --email=<email> --validation <file></info>

HELP
            )
            ->addUsage('--priceListId=<ID> --email=<email> <file>')
            ->addUsage('--priceListId=<ID> --email=<email> --validation <file>')
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! is_file($sourceFile = $input->getArgument('file'))) {
            throw new \InvalidArgumentException(sprintf('File not found: %s', $sourceFile));
        }

        $originFileName = basename($sourceFile);
        $fileName = FileManager::generateUniqueFileName(pathinfo($sourceFile, PATHINFO_EXTENSION));
        $this->fileManager->writeFileToStorage($sourceFile, $fileName);

        $validation = $input->hasOption('validation') && $input->getOption('validation');

        $importOwner = $this->getImportOwner($input);

        $priceListId = $input->hasOption('priceListId') ? $input->getOption('priceListId') : false;
        if (!$priceListId) {
            throw new \InvalidArgumentException('Price list ID is required.');
        }

        $this->importHandler->setImportingFileName($sourceFile);

        $process = $validation ? ProcessorRegistry::TYPE_IMPORT_VALIDATION : ProcessorRegistry::TYPE_IMPORT;

        $this->messageProducer->send(
            PreImportTopic::getName(),
            [
                'fileName'       => $fileName,
                'originFileName' => $originFileName,
                'userId'         => $importOwner->getId(),
                'jobName'        => $validation ? self::DEFAULT_VALIDATION_JOB_NAME : self::DEFAULT_JOB_NAME,
                'processorAlias' => self::DEFAULT_PROCESSOR,
                'process'        => $process,
                'options'        => [
                    'price_list_id' => $priceListId
                ]
            ]
        );

        $output->writeln('Scheduled successfully. The result will be sent to the email');

        return self::SUCCESS;
    }

    private function getImportOwner(InputInterface $input): User
    {
        $email = $input->hasOption('email') ? $input->getOption('email') : '';
        if (!$email) {
            throw new \InvalidArgumentException('The --email option is required.');
        }

        $importOwner = $this->userManager->findUserByEmail($email);
        if (!$importOwner instanceof User) {
            throw new \InvalidArgumentException(sprintf('Invalid email. There is no user with %s email!', $email));
        }

        return $importOwner;
    }
}
