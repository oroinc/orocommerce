<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @dbIsolationPerTest
 */
class ProductImageImportTest extends WebTestCase
{
    use MessageQueueExtension;

    private const IMPORT_PROCESSOR_ALIAS = 'oro_product_image.add_or_replace';
    private const EXPORT_TEMPLATE_PROCESSOR_ALIAS = 'oro_product_image_export_template';

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        // copy fixture files to the storage
        $fileManager = self::getContainer()->get('oro_product.tests.importexport.file_manager.product_images');
        $finder = new Finder();
        $files = $finder->files()->in(__DIR__ . '/data/product_image/images/');
        /** @var \SplFileInfo[] $files */
        foreach ($files as $file) {
            $fileManager->writeFileToStorage($file->getPathname(), $file->getFilename());
        }

        $this->loadFixtures([LoadProductData::class]);
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

    public function testImportAddAndReplaceStrategyWithTypes()
    {
        $this->assertImportWorks(
            __DIR__ . '/data/product_image/product_image_import_with_types.csv'
        );

        /** @var  EntityRepository $productRepo */
        $productRepo = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class);

        /** @var Product $product */
        $product = $productRepo->findOneById($this->getReference(LoadProductData::PRODUCT_1));

        $this->assertCount(4, $product->getImages());

        $this->assertProductImageTypes(['main', 'additional'], 'product-1_1.jpg', $product);
        $this->assertProductImageTypes(['listing', 'additional'], 'product-1_2.jpg', $product);
        $this->assertProductImageTypes(['additional'], 'product-1_3.jpg', $product);
    }

    /**
     * @param array $expected
     * @param string $imageName
     * @param Product $product
     */
    private function assertProductImageTypes(array $expected, string $imageName, Product $product): void
    {
        $productImage = null;
        foreach ($product->getImages() as $image) {
            if ($image->getImage() && $image->getImage()->getOriginalFilename() === $imageName) {
                $productImage = $image;

                break;
            }
        }

        $this->assertNotNull($productImage);

        $types = array_map(
            static function (ProductImageType $productImageType) {
                return $productImageType->getType();
            },
            $productImage->getTypes()->toArray()
        );

        $this->assertEquals(array_combine($expected, $expected), $types);
    }

    /**
     * @param string $expectedCsvFilePath
     */
    private function assertExportTemplateWorks(string $expectedCsvFilePath)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_template', [
                'processorAlias' => self::EXPORT_TEMPLATE_PROCESSOR_ALIAS
            ])
        );

        // Take the name of the file from the header because there is no alternative way to know the filename
        $contentDisposition = $this->client->getResponse()->headers->get('Content-Disposition');
        preg_match('/^.*"?(export_template_[a-z0-9_]+.csv)"?$/', $contentDisposition, $matches);

        ob_start();
        $this->client->getResponse()->sendContent();
        $actualExportContent = ob_get_clean();

        self::assertStringContainsString(
            $this->getFileContent($expectedCsvFilePath),
            $actualExportContent
        );

        $this->deleteImportExportFile($matches[1]);
    }

    /**
     * @param string $filename
     */
    private function deleteImportExportFile(string $filename)
    {
        self::getContainer()
            ->get('oro_importexport.file.file_manager')
            ->deleteFile($filename);
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    private function getFileContent(string $filePath)
    {
        return file_get_contents($filePath);
    }

    /**
     * @param string $importFilePath
     */
    private function assertImportWorks(string $importFilePath)
    {
        $this->assertPreImportActionExecuted($importFilePath);

        $preImportMessageData = $this->getOneSentMessageWithTopic(Topics::PRE_IMPORT);
        $this->clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.pre_import',
            $preImportMessageData
        );

        self::assertMessageSent(Topics::IMPORT);

        $importMessageData = $this->getOneSentMessageWithTopic(Topics::IMPORT);
        $this->clearMessageCollector();

        $this->assertMessageProcessorExecuted(
            'oro_importexport.async.import',
            $importMessageData
        );

        $this->deleteTmpFile($preImportMessageData['fileName']);
        $this->deleteTmpFile($importMessageData['fileName']);
    }

    /**
     * @param string $filename
     */
    private function deleteTmpFile(string $filename)
    {
        unlink(FileManager::generateTmpFilePath($filename));
    }

    /**
     * @param string $processorServiceName
     * @param array $messageData
     */
    private function assertMessageProcessorExecuted(string $processorServiceName, array $messageData)
    {
        $processorResult = self::getContainer()
            ->get($processorServiceName)
            ->process(
                $this->createMessage($messageData),
                $this->createSessionInterfaceMock()
            );

        self::assertEquals(MessageProcessorInterface::ACK, $processorResult);
    }

    /**
     * @return SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSessionInterfaceMock()
    {
        return $this->getMockBuilder(SessionInterface::class)->getMock();
    }

    /**
     * @param array $messageData
     *
     * @return Message
     */
    private function createMessage(array $messageData)
    {
        $message = new Message();

        $message->setMessageId('abc');
        $message->setBody(json_encode($messageData));

        return $message;
    }

    /**
     * @param string $topic
     *
     * @return array
     */
    private function getOneSentMessageWithTopic(string $topic)
    {
        $sentMessages = self::getSentMessages();

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
    private function assertPreImportActionExecuted(string $importCsvFilePath)
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
            Topics::PRE_IMPORT,
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
     * @return User
     */
    private function getCurrentUser()
    {
        return $this->getSecurityToken()->getUser();
    }

    /**
     * @return UsernamePasswordOrganizationToken
     */
    private function getSecurityToken()
    {
        return self::getContainer()
            ->get('security.token_storage')
            ->getToken();
    }

    private function assertImportedDataValid()
    {
        /** @var EntityRepository $productRepo */
        $productRepo = self::getContainer()
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
