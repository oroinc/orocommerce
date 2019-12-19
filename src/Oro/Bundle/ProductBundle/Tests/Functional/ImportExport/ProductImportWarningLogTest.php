<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;
use Oro\Bundle\ImportExportBundle\Async\Import\PreImportMessageProcessorAbstract;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @dbIsolationPerTest
 */
class ProductImportWarningLogTest extends WebTestCase
{
    use ReflectionHelperTrait;
    use MessageQueueExtension;

    const IMPORT_PROCESSOR_ALIAS = 'oro_product_image.add_or_replace';

    /** @var PreImportMessageProcessorAbstract */
    private $preImportProcessor;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    public function testLoggedDuplicateColumnExceptionOnProductImport()
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

    public function testLoggedWrongColumnCountExceptionOnProductImport()
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

    private function expectToLogImportCritical(string $expectedMessagePart)
    {
        $this->preImportProcessor = self::getContainer()->get('oro_importexport.async.pre_http_import');

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->writeAttribute($this->preImportProcessor, 'logger', $this->logger);

        $this->logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->callback(function ($loggedMessage) use ($expectedMessagePart) {
                $this->assertContains(
                    $expectedMessagePart,
                    $loggedMessage
                );

                return $loggedMessage;
            }));
    }

    /**
     * @param string $importFilePath
     */
    private function assertImportOfInvalidFile(string $importFilePath)
    {
        $this->assertPreImportActionExecuted($importFilePath);

        $preImportMessageData = $this->getOneSentMessageWithTopic(Topics::PRE_HTTP_IMPORT);
        $this->clearMessageCollector();

        $this->assertMessageProcessorRejected(
            'oro_importexport.async.pre_http_import',
            $preImportMessageData
        );

        static::assertMessagesEmpty(Topics::HTTP_IMPORT);

        $this->deleteImportFile($preImportMessageData['fileName']);
    }

    /**
     * @param string $importCsvFilePath
     */
    private function assertPreImportActionExecuted(string $importCsvFilePath)
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
            Topics::PRE_HTTP_IMPORT,
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

    /**
     * @param string $topic
     * @return array
     */
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

    /**
     * @param string $processorServiceName
     * @param array $messageData
     */
    private function assertMessageProcessorRejected(string $processorServiceName, array $messageData)
    {
        $processorResult = static::getContainer()
            ->get($processorServiceName)
            ->process(
                $this->createNullMessage($messageData),
                $this->createSessionInterfaceMock()
            );

        static::assertEquals(MessageProcessorInterface::REJECT, $processorResult);
    }

    /**
     * @param string $filename
     */
    private function deleteImportFile(string $filename)
    {
        unlink(FileManager::generateTmpFilePath($filename));

        static::getContainer()
            ->get('oro_importexport.file.file_manager')
            ->deleteFile($filename);
    }

    /**
     * @return User
     */
    private function getCurrentUser(): User
    {
        return $this->getSecurityToken()->getUser();
    }

    /**
     * @param array $messageData
     * @return NullMessage
     */
    private function createNullMessage(array $messageData): NullMessage
    {
        $message = new NullMessage();

        $message->setMessageId('abc');
        $message->setBody(json_encode($messageData));

        return $message;
    }

    /**
     * @return SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSessionInterfaceMock(): SessionInterface
    {
        return $this->getMockBuilder(SessionInterface::class)->getMock();
    }

    /**
     * @return UsernamePasswordOrganizationToken
     */
    private function getSecurityToken(): UsernamePasswordOrganizationToken
    {
        return static::getContainer()
            ->get('security.token_storage')
            ->getToken();
    }
}
