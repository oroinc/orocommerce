<?php

namespace Oro\Bundle\PricingBundle\Command;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\CliImportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PriceListImportCommand extends ContainerAwareCommand
{
    const NAME              = 'oro:import:price-list:file';

    const DEFAULT_PROCESSOR = 'oro_pricing_product_price.add_or_replace';
    const DEFAULT_JOB_NAME  = 'price_list_product_prices_entity_import_from_csv';
    const DEFAULT_VALIDATION_JOB_NAME = 'entity_import_validation_from_csv';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
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
        $this->getFileManager()->writeFileToStorage($sourceFile, $fileName);

        $validation = $input->hasOption('validation') && $input->getOption('validation');
        $email = $input->hasOption('email') ? $input->getOption('email') : false;
        if (!$email) {
            throw new \InvalidArgumentException('Email is required for the validation!');
        }

        $priceListId = $input->hasOption('priceListId') ? $input->getOption('priceListId') : false;
        if (!$priceListId) {
            throw new \InvalidArgumentException('Price list ID is required.');
        }

        $this->getImportHandler()->setImportingFileName($sourceFile);

        $process = $validation ? ProcessorRegistry::TYPE_IMPORT_VALIDATION : ProcessorRegistry::TYPE_IMPORT;

        $this->getMessageProducer()->send(
            Topics::PRE_CLI_IMPORT,
            [
                'fileName'       => $fileName,
                'originFileName' => $originFileName,
                'notifyEmail'    => $email,
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
     * @return object|FileManager
     */
    protected function getFileManager()
    {
        return $this->getContainer()->get('oro_importexport.file.file_manager');
    }

    /**
     * @return CliImportHandler
     */
    protected function getImportHandler()
    {
        return $this->getContainer()->get('oro_importexport.handler.import.cli');
    }

    /**
     * @return MessageProducerInterface
     */
    protected function getMessageProducer()
    {
        return $this->getContainer()->get('oro_message_queue.client.message_producer');
    }
}
