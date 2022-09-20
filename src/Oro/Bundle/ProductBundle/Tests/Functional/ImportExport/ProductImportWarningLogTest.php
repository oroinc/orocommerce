<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Oro\Bundle\ImportExportBundle\Async\Topic\ImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
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

    const IMPORT_PROCESSOR_ALIAS = 'oro_product_image.add_or_replace';

    public function testLoggedDuplicateColumnExceptionOnProductImport(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->expectToLogImportCritical(
            'Imported file contains duplicate in next column names: \'duplicateColumnName\'.'
        );

        $this->assertImportOfInvalidFile(
            __DIR__ . '/data/import_with_duplicate_columns.csv'
        );
    }

    public function testLoggedWrongColumnCountExceptionOnProductImport(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->expectToLogImportCritical(
            'Expecting to get 167 columns, actually got 169.'
        );

        $this->assertImportOfInvalidFile(
            __DIR__ . '/data/import_with_wrong_column_count.csv'
        );
    }

    private function expectToLogImportCritical(string $expectedMessagePart): void
    {
        $preImportProcessor = self::getContainer()->get('oro_importexport.async.pre_import');

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->callback(function ($loggedMessage) use ($expectedMessagePart) {
                static::assertStringContainsString($expectedMessagePart, $loggedMessage);

                return true;
            }));

        $preImportProcessor->setLogger($logger);
    }

    private function assertImportOfInvalidFile(string $importFilePath): void
    {
        $this->assertPreImportActionExecuted($importFilePath);

        $preImportMessageData = $this->getOneSentMessageWithTopic(PreImportTopic::getName());
        self::clearMessageCollector();

        $this->assertMessageProcessorRejected(
            'oro_importexport.async.pre_import',
            $preImportMessageData
        );

        static::assertMessagesEmpty(ImportTopic::getName());

        $this->deleteImportFile($preImportMessageData['fileName']);
    }

    private function assertPreImportActionExecuted(string $importCsvFilePath): void
    {
        $file = new UploadedFile($importCsvFilePath, basename($importCsvFilePath));
        $fileName = static::getContainer()
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

        $response = static::getJsonResponseContent($this->client->getResponse(), 200);
        static::assertTrue($response['success']);

        static::assertMessageSent(
            PreImportTopic::getName(),
            [
                'fileName' => $fileName,
                'process' => ProcessorRegistry::TYPE_IMPORT,
                'userId' => $this->getCurrentUser()->getId(),
                'originFileName' => $file->getClientOriginalName(),
                'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
                'processorAlias' => self::IMPORT_PROCESSOR_ALIAS,
                'options' => []
            ]
        );
    }

    private function getOneSentMessageWithTopic(string $topic): array
    {
        $sentMessages = static::getSentMessages();

        foreach ($sentMessages as $messageData) {
            if ($messageData['topic'] === $topic) {
                return $messageData['message'];
            }
        }

        return [];
    }

    private function assertMessageProcessorRejected(string $processorServiceName, array $messageData): void
    {
        $processorResult = static::getContainer()
            ->get($processorServiceName)
            ->process(
                $this->createMessage($messageData),
                $this->createSessionInterfaceMock()
            );

        static::assertEquals(MessageProcessorInterface::REJECT, $processorResult);
    }

    private function deleteImportFile(string $filename): void
    {
        self::assertFileDoesNotExist(FileManager::generateTmpFilePath($filename));

        static::getContainer()
            ->get('oro_importexport.file.file_manager')
            ->deleteFile($filename);
    }

    private function getCurrentUser(): User
    {
        return $this->getSecurityToken()->getUser();
    }

    private function createMessage(array $messageData): Message
    {
        $message = new Message();

        $message->setMessageId('abc');
        $message->setBody($messageData);

        return $message;
    }

    /**
     * @return SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSessionInterfaceMock(): SessionInterface
    {
        return $this->getMockBuilder(SessionInterface::class)->getMock();
    }

    private function getSecurityToken(): UsernamePasswordOrganizationToken
    {
        return static::getContainer()
            ->get('security.token_storage')
            ->getToken();
    }
}
