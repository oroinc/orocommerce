<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @dbIsolationPerTest
 */
class ProductImportWarningLogTest extends WebTestCase
{
    use MessageQueueExtension;

    private const IMPORT_PROCESSOR_ALIAS = 'oro_product_image.add_or_replace';

    public function testLoggedDuplicateColumnExceptionOnProductImport(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->expectToLogImportCritical(
            'Imported file contains duplicate in next column names: \'duplicateColumnName\'.'
        );

        $this->assertImportOfInvalidFile(__DIR__ . '/data/import_with_duplicate_columns.csv');
    }

    public function testLoggedWrongColumnCountExceptionOnProductImport(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->expectToLogImportCritical(
            'Expecting to get 167 columns, actually got 169.'
        );

        $this->assertImportOfInvalidFile(__DIR__ . '/data/import_with_wrong_column_count.csv');
    }

    private function expectToLogImportCritical(string $expectedMessagePart): void
    {
        $preImportProcessor = self::getContainer()->get('oro_importexport.async.pre_import');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with($this->callback(function ($loggedMessage) use ($expectedMessagePart) {
                self::assertStringContainsString($expectedMessagePart, $loggedMessage);

                return true;
            }));

        $preImportProcessor->setLogger($logger);
    }

    private function assertImportOfInvalidFile(string $importFilePath): void
    {
        $this->assertPreImportActionExecuted($importFilePath);

        $preImportMessageData = $this->getOneSentMessageWithTopic(PreImportTopic::getName());
        self::clearMessageCollector();

        $this->assertMessageProcessorRejected('oro_importexport.async.pre_import', $preImportMessageData);

        self::assertMessagesEmpty(ImportTopic::getName());

        $this->deleteImportFile($preImportMessageData['fileName']);
    }

    private function assertPreImportActionExecuted(string $importCsvFilePath): void
    {
        $file = new UploadedFile($importCsvFilePath, basename($importCsvFilePath));
        $fileName = self::getContainer()
            ->get('oro_importexport.file.file_manager')
            ->saveImportingFile($file);

        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_importexport_import_process',
                [
                    'processorAlias' => self::IMPORT_PROCESSOR_ALIAS,
                    'fileName' => $fileName,
                    'originFileName' => $file->getClientOriginalName(),
                ]
            )
        );

        $response = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertTrue($response['success']);

        self::assertMessageSent(
            PreImportTopic::getName(),
            [
                'fileName' => $fileName,
                'process' => ProcessorRegistry::TYPE_IMPORT,
                'userId' => self::getContainer()->get('security.token_storage') ->getToken()->getUser()->getId(),
                'originFileName' => $file->getClientOriginalName(),
                'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
                'processorAlias' => self::IMPORT_PROCESSOR_ALIAS,
                'options' => []
            ]
        );
    }

    private function getOneSentMessageWithTopic(string $topic): array
    {
        $sentMessages = self::getSentMessages();
        foreach ($sentMessages as $messageData) {
            if ($messageData['topic'] === $topic) {
                return $messageData['message'];
            }
        }

        return [];
    }

    private function assertMessageProcessorRejected(string $processorServiceName, array $messageData): void
    {
        $message = new Message();
        $message->setMessageId('abc');
        $message->setBody($messageData);

        $processorResult = self::getContainer()
            ->get($processorServiceName)
            ->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $processorResult);
    }

    private function deleteImportFile(string $filename): void
    {
        self::assertFileDoesNotExist(FileManager::generateTmpFilePath($filename));

        self::getContainer()->get('oro_importexport.file.file_manager')->deleteFile($filename);
    }
}
