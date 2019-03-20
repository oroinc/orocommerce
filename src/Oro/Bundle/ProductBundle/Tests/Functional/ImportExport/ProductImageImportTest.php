<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Normalizer\ProductImageNormalizer;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @dbIsolationPerTest
 */
class ProductImageImportTest extends WebTestCase
{
    use MessageQueueExtension;

    const IMPORT_PROCESSOR_ALIAS = 'oro_product_image.add_or_replace';
    const EXPORT_TEMPLATE_PROCESSOR_ALIAS = 'oro_product_image_export_template';

    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        /** @var ProductImageNormalizer $normalizer */
        $normalizer = $this->getClientInstance()
            ->getContainer()
            ->get('oro_product.importexport.normalizer.product_image.test');

        $normalizer->setProductImageDir(__DIR__ . '/data/product_image/images/');

        $this->loadFixtures(
            [
                LoadProductData::class,
            ]
        );
    }


    public function testExportTemplate()
    {
        $this->assertExportTemplateWorks(
            __DIR__ . '/data/product_image/product_image_export_template.csv'
        );
    }

    public function testImportAddAndReplaceStrategy()
    {
        $this->assertImportWorks(
            __DIR__ . '/data/product_image/product_image_import.csv'
        );

        $this->assertImportedDataValid();
    }

    /**
     * @param string $expectedCsvFilePath
     */
    protected function assertExportTemplateWorks(string $expectedCsvFilePath)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_template', [
                'processorAlias' => self::EXPORT_TEMPLATE_PROCESSOR_ALIAS
            ])
        );
        $this->client->followRedirect();

        static::assertContains(
            $this->getFileContent($expectedCsvFilePath),
            $this->client->getResponse()->getContent()
        );

        $this->deleteImportExportFile($this->client->getRequest()->attributes->get('fileName'));
    }

    /**
     * @param string $filename
     */
    protected function deleteImportExportFile(string $filename)
    {
        static::getContainer()
            ->get('oro_importexport.file.file_manager')
            ->deleteFile($filename);
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    protected function getFileContent(string $filePath)
    {
        return file_get_contents($filePath);
    }

    /**
     * @param string $importFilePath
     */
    protected function assertImportWorks(string $importFilePath)
    {
        $this->assertPreImportActionExecuted($importFilePath);

        $preImportMessageData = $this->getOneSentMessageWithTopic(Topics::PRE_HTTP_IMPORT);
        $this->clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.pre_http_import',
            $preImportMessageData
        );

        static::assertMessageSent(Topics::HTTP_IMPORT);

        $importMessageData = $this->getOneSentMessageWithTopic(Topics::HTTP_IMPORT);
        $this->clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.http_import',
            $importMessageData
        );

        $this->deleteTmpFile($preImportMessageData['fileName']);
        $this->deleteTmpFile($importMessageData['fileName']);
    }

    /**
     * @param string $filename
     */
    protected function deleteTmpFile(string $filename)
    {
        unlink(FileManager::generateTmpFilePath($filename));
    }

    /**
     * @param string $processorServiceName
     * @param array $messageData
     */
    protected function assertMessageProcessorExecuted(string $processorServiceName, array $messageData)
    {
        $processorResult = static::getContainer()
            ->get($processorServiceName)
            ->process(
                $this->createNullMessage($messageData),
                $this->createSessionInterfaceMock()
            );

        static::assertEquals(MessageProcessorInterface::ACK, $processorResult);
    }

    /**
     * @return SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createSessionInterfaceMock()
    {
        return $this->getMockBuilder(SessionInterface::class)->getMock();
    }

    /**
     * @param array $messageData
     *
     * @return NullMessage
     */
    protected function createNullMessage(array $messageData)
    {
        $message = new NullMessage();

        $message->setMessageId('abc');
        $message->setBody(json_encode($messageData));

        return $message;
    }

    /**
     * @param string $topic
     *
     * @return array
     */
    protected function getOneSentMessageWithTopic(string $topic)
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
     * @param string $importCsvFilePath
     */
    protected function assertPreImportActionExecuted(string $importCsvFilePath)
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
     * @return string|null
     */
    protected function getSerializedSecurityToken()
    {
        return static::getContainer()
            ->get('oro_security.token_serializer')
            ->serialize($this->getSecurityToken());
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->getSecurityToken()->getUser();
    }

    /**
     * @return UsernamePasswordOrganizationToken
     */
    protected function getSecurityToken()
    {
        return static::getContainer()
            ->get('security.token_storage')
            ->getToken();
    }

    protected function assertImportedDataValid()
    {
        /** @var  EntityRepository $productRepo */
        $productRepo = static::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class);

        /** @var Product $product */
        $product = $productRepo->findOneById(
            $this->getReference(LoadProductData::PRODUCT_1)
        );

        $this->assertCount(2, $product->getImages());
    }
}
