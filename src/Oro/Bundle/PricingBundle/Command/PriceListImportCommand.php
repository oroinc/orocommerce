<?php

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\ImportExportBundle\Async\Topics;
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
 * Command for import price list
 */
class PriceListImportCommand extends Command
{
    const DEFAULT_PROCESSOR = 'oro_pricing_product_price.add_or_replace';
    const DEFAULT_JOB_NAME  = 'price_list_product_prices_entity_import_from_csv';
    const DEFAULT_VALIDATION_JOB_NAME = 'entity_import_validation_from_csv';

    /**
     * @var string
     */
    protected static $defaultName = 'oro:import:price-list:file';

    /**
     * @var FileManager
     */
    private $fileManager;

    /**
     * @var ImportHandler
     */
    private $importHandler;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @param FileManager $fileManager
     * @param ImportHandler $importHandler
     * @param MessageProducerInterface $messageProducer
     * @param UserManager $userManager
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription(
                'Import price list data from specified file. The import log is sent to the provided email.'
            )
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'File name, to import CSV data from'
            )
            ->addOption(
                'validation',
                null,
                InputOption::VALUE_NONE,
                'If adding this option then validation will be performed instead of import'
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED,
                'Email to send the log after the import is completed'
            )
            ->addOption(
                'priceListId',
                null,
                InputOption::VALUE_REQUIRED,
                'Price list identifier'
            );
    }

    /**
     * {@inheritdoc}
     */
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
            Topics::PRE_IMPORT,
            [
                'fileName'       => $fileName,
                'originFileName' => $originFileName,
                'userId'         => $importOwner->getId(),
                'jobName'        => $validation ? self::DEFAULT_VALIDATION_JOB_NAME : self::DEFAULT_JOB_NAME,
                'processorAlias' => self::DEFAULT_PROCESSOR,
                'process'        => $process,
                'options'        => [
                    'price_list_id' => $priceListId,
                    'unique_job_slug' => $priceListId,
                ]
            ]
        );

        $output->writeln('Scheduled successfully. The result will be sent to the email');
    }

    /**
     * @param InputInterface $input
     * @return User
     */
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
