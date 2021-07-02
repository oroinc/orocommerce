<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\InventoryStatusStub;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerListener;
use Oro\Bundle\ProductBundle\Search\ProductIndexDataModel;
use Oro\Bundle\ProductBundle\Search\ProductIndexDataProviderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductWithInventoryStatus;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchProductIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const DESCRIPTION_DEFAULT_LOCALE = 'description default';
    const DESCRIPTION_CUSTOM_LOCALE = 'description custom';

    /**
     * @var WebsiteSearchProductIndexerListener
     */
    private $listener;

    /** @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteContextManager;

    /** @var AbstractWebsiteLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteLocalizationProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    /** @var ProductIndexDataProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $this->websiteLocalizationProvider = $this->createMock(AbstractWebsiteLocalizationProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->dataProvider = $this->createMock(ProductIndexDataProviderInterface::class);

        $this->listener = new WebsiteSearchProductIndexerListener(
            $this->websiteLocalizationProvider,
            $this->websiteContextManager,
            $this->registry,
            $this->attachmentManager,
            $this->attributeManager,
            $this->dataProvider
        );
    }

    /**
     * @param Localization $localization
     * @param string|null $string
     * @param string|null $text
     *
     * @return LocalizedFallbackValue
     */
    private function prepareLocalizedValue($localization = null, $string = null, $text = null)
    {
        $value = new LocalizedFallbackValue();
        $value
            ->setString($string)
            ->setText($text)
            ->setLocalization($localization);

        return $value;
    }

    /**
     * * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnWebsiteSearchIndexProductClass()
    {
        $organization = new Organization();
        $website = new Website();
        $website->setOrganization($organization);

        /** @var Localization $firstLocale */
        $firstLocale = $this->getEntity(Localization::class, ['id' => 1]);

        /** @var Localization $secondLocale */
        $secondLocale = $this->getEntity(Localization::class, ['id' => 2]);

        $this->websiteLocalizationProvider
            ->expects($this->once())
            ->method('getLocalizationsByWebsiteId')
            ->willReturn([$firstLocale, $secondLocale]);

        $attributeFamilyId = 42;
        $attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => $attributeFamilyId]);

        $product = $this->getEntity(
            ProductWithInventoryStatus::class,
            [
                'id' => 777,
                'sku' => 'sku123Абв',
                'status' => Product::STATUS_ENABLED,
                'type' => Product::TYPE_CONFIGURABLE,
                'inventoryStatus' => new InventoryStatusStub('in_stock', 'In Stock'),
                'newArrival' => true,
                'createdAt' => new \DateTime('2017-09-09 00:00:00'),
                'attributeFamily' => $attributeFamily,
            ]
        );

        $event = new IndexEntityEvent(Product::class, [$product], []);

        $this->websiteContextManager
            ->expects($this->once())
            ->method('getWebsiteId')
            ->with([])
            ->willReturn(1);

        $productRepository = $this->createMock(ProductRepository::class);
        $unitRepository = $this->createMock(ProductUnitRepository::class);
        $attributeFamilyRepository = $this->createMock(AttributeFamilyRepository::class);

        $entity = $this->getImageFileEntities();

        $productRepository->expects($this->once())
            ->method('getListingImagesFilesByProductIds')
            ->with([777])
            ->willReturn([777 => $entity]);

        $unitRepository->expects($this->once())
            ->method('getProductsUnits')
            ->with([777])
            ->willReturn([777 => ['item', 'set']]);

        $unitRepository->expects($this->once())
            ->method('getPrimaryProductsUnits')
            ->with([777])
            ->willReturn([777 => 'item']);

        $attribute1 = $this->getEntity(FieldConfigModel::class, ['id' => 1001, 'fieldName' => 'sku']);
        $attribute2 = $this->getEntity(FieldConfigModel::class, ['id' => 1002, 'fieldName' => 'newArrival']);
        $attribute3 = $this->getEntity(FieldConfigModel::class, ['id' => 1003, 'fieldName' => 'descriptions']);
        $attribute4 = $this->getEntity(FieldConfigModel::class, ['id' => 1004, 'fieldName' => 'createdAt']);
        $attribute5 = $this->getEntity(FieldConfigModel::class, ['id' => 1005, 'fieldName' => 'skipped']);
        $attribute6 = $this->getEntity(FieldConfigModel::class, ['id' => 1006, 'fieldName' => 'system']);

        $attributeFamilyRepository->expects($this->once())
            ->method('getFamilyIdsForAttributesByOrganization')
            ->with([$attribute1, $attribute2, $attribute3, $attribute4, $attribute5, $attribute6], $organization)
            ->willReturn(
                [
                    $attribute1->getId() => [$attributeFamilyId],
                    $attribute2->getId() => [$attributeFamilyId],
                    $attribute3->getId() => [$attributeFamilyId],
                    $attribute4->getId() => [$attributeFamilyId],
                    $attribute5->getId() => [500],
                ]
            );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Website::class, 1)
            ->willReturn($website);
        $em->expects($this->exactly(4))
            ->method('getRepository')
            ->withConsecutive(
                ['OroProductBundle:Product'],
                ['OroProductBundle:ProductUnit'],
                ['OroProductBundle:ProductUnit'],
                [AttributeFamily::class]
            )
            ->willReturnOnConsecutiveCalls(
                $productRepository,
                $unitRepository,
                $unitRepository,
                $attributeFamilyRepository
            );
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->attachmentManager->expects($this->exactly(3))
            ->method('getFilteredImageUrl')
            ->withConsecutive([$entity], [$entity])
            ->willReturnOnConsecutiveCalls(
                '/large/image',
                '/medium/image',
                '/small/image'
            );

        $this->attributeManager
            ->expects($this->once())
            ->method('getActiveAttributesByClassForOrganization')
            ->with(Product::class, $organization)
            ->willReturn([$attribute1, $attribute2, $attribute3, $attribute4, $attribute5, $attribute6]);
        $this->attributeManager
            ->expects($this->any())
            ->method('isSystem')
            ->willReturnCallback(
                function (FieldConfigModel $attribute) use ($attribute6) {
                    return $attribute === $attribute6;
                }
            );

        $model1 = new ProductIndexDataModel('sku', $product->getSku(), [], false, true);
        $model2 = new ProductIndexDataModel('newArrival', $product->isNewArrival(), [], false, false);
        $model3 = new ProductIndexDataModel(
            IndexDataProvider::ALL_TEXT_L10N_FIELD,
            self::DESCRIPTION_DEFAULT_LOCALE,
            [LocalizationIdPlaceholder::NAME => $firstLocale->getId()],
            true,
            true
        );
        $model4 = new ProductIndexDataModel(
            IndexDataProvider::ALL_TEXT_L10N_FIELD,
            self::DESCRIPTION_CUSTOM_LOCALE,
            [LocalizationIdPlaceholder::NAME => $secondLocale->getId()],
            true,
            true
        );
        $model5 = new ProductIndexDataModel('createdAt', $product->getCreatedAt(), [], false, false);
        $model6 = new ProductIndexDataModel('system', 'system', [], false, false);
        $model7 = new ProductIndexDataModel('descriptions', $this->createMultiControlCharString(), [], true, false);

        $this->dataProvider
            ->expects($this->exactly(5))
            ->method('getIndexData')
            ->willReturnMap(
                [
                    [$product, $attribute1, [$firstLocale, $secondLocale], [$model1]],
                    [$product, $attribute2, [$firstLocale, $secondLocale], [$model2]],
                    [$product, $attribute3, [$firstLocale, $secondLocale], [$model3, $model4, $model7]],
                    [$product, $attribute4, [$firstLocale, $secondLocale], [$model5]],
                    [$product, $attribute6, [$firstLocale, $secondLocale], [$model6]],
                ]
            );

        $this->listener->onWebsiteSearchIndex($event);

        $expected[$product->getId()] = [
            'product_id' => [['value' => $product->getId(), 'all_text' => false]],
            'sku' => [['value' => 'sku123Абв', 'all_text' => true]],
            'sku_uppercase' => [['value' => 'SKU123АБВ', 'all_text' => true]],
            'status' => [['value' => Product::STATUS_ENABLED, 'all_text' => false]],
            'type' => [['value' => Product::TYPE_CONFIGURABLE, 'all_text' => false]],
            'inventory_status' => [['value' => 'in_stock', 'all_text' => false]],
            'is_variant' => [['value' => 0, 'all_text' => false]],
            'newArrival' => [['value' => 1, 'all_text' => false]],
            'all_text_LOCALIZATION_ID' => [
                [
                    'value' => new PlaceholderValue(
                        $this->prepareLocalizedValue($firstLocale, null, self::DESCRIPTION_DEFAULT_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $firstLocale->getId()]
                    ),
                    'all_text' => true,
                ],
                [
                    'value' => new PlaceholderValue(
                        $this->prepareLocalizedValue($secondLocale, null, self::DESCRIPTION_CUSTOM_LOCALE),
                        [LocalizationIdPlaceholder::NAME => $secondLocale->getId()]
                    ),
                    'all_text' => true,
                ],
            ],
            'image_product_small' => [
                [
                    'value' => '/small/image',
                    'all_text' => false
                ]
            ],
            'image_product_medium' => [
                [
                    'value' => '/medium/image',
                    'all_text' => false
                ]
            ],
            'image_product_large' => [
                [
                    'value' => '/large/image',
                    'all_text' => false
                ]
            ],
            'product_units' => [
                [
                    'value' => serialize(['item', 'set']),
                    'all_text' => false
                ]
            ],
            'createdAt' => [
                [
                    'value' => new \DateTime('2017-09-09 00:00:00'),
                    'all_text' => false
                ]
            ],
            'system' => [
                [
                    'value' => 'system',
                    'all_text' => false
                ]
            ],
            'primary_unit' => [
                [
                    'value' => 'item',
                    'all_text' => false
                ]
            ],
            'descriptions' => [
                [
                    'value' => new PlaceholderValue(
                        'ASDF123 ASDF456 ASDF789',
                        []
                    ),
                    'all_text' => false
                ]
            ],
            'attribute_family_id' => [
                [
                    'value' => $attributeFamilyId,
                    'all_text' => false
                ]
            ]
        ];

        $this->assertEquals($expected, $event->getEntitiesData());
    }

    /**
     * Create string with not printable control symbols
     *
     * @return string
     */
    private function createMultiControlCharString()
    {
        $multicharText = 'ASDF123';

        for ($control = 0; $control < 32; $control++) {
            $multicharText .= chr($control);
        }

        return $multicharText . 'ASDF456' . chr(127) . 'ASDF789';
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getImageFileEntities()
    {
        $file = $this->createMock(File::class);

        $file->method('getId')
            ->willReturn(1);

        $file->method('getFilename')
            ->willReturn('/image/filename');

        return $file;
    }
}
