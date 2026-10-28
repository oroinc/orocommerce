<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\Form\DataTransformer;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductKitConfigurationType;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;

/**
 * @dbIsolationPerTest
 */
final class FrontendProductKitToIdTransformerTest extends FrontendWebTestCase
{
    use ConfigManagerAwareTestTrait;
    use FormAwareTestTrait;

    private const string PRODUCT_SIMPLE1 = 'product_simple1';
    private const string PRODUCT_KIT_ALLOWED = 'product_kit_allowed';
    private const string RFP_ALLOWED_INVENTORY_STATUSES = 'oro_rfp.frontend_product_visibility';
    private const float KIT_ITEM_QUANTITY = 2.0;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->loadFixtures([
            '@OroRFPBundle/Tests/Functional/Form/DataTransformer/DataFixtures/FrontendProductKitToIdTransformer.yml',
        ]);

        self::getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();

        $this->setCurrentWebsite('default');
        self::getContainer()->get('oro_frontend.request.frontend_helper')->emulateFrontendRequest();
    }

    #[\Override]
    protected function tearDown(): void
    {
        self::getContainer()->get('oro_frontend.request.frontend_helper')->resetRequestEmulation();

        parent::tearDown();
    }

    public function testSubmitWhenProductAllowedKeepsProductAndKitItemLineItems(): void
    {
        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);

        /** @var Product $productKit */
        $productKit = $this->getReference(self::PRODUCT_KIT_ALLOWED);
        /** @var ProductKitItem $productKitItem */
        $productKitItem = $this->getReference(self::PRODUCT_KIT_ALLOWED . '_item');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference(self::PRODUCT_SIMPLE1);

        $form = self::createForm(RequestProductKitConfigurationType::class, null, ['validation_groups' => false]);
        $form->submit([
            'product' => (string)$productKit->getId(),
            'kitItemLineItems' => [
                $productKitItem->getId() => [
                    'product' => $productSimple1->getId(),
                    'quantity' => self::KIT_ITEM_QUANTITY,
                ],
            ],
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->get('product')->isSynchronized());

        /** @var RequestProduct $requestProduct */
        $requestProduct = $form->getData();
        self::assertSame($productKit->getId(), $requestProduct->getProduct()?->getId());
        self::assertCount(1, $requestProduct->getKitItemLineItems());

        /** @var RequestProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $requestProduct->getKitItemLineItems()->first();
        self::assertSame($productSimple1->getId(), $kitItemLineItem->getProduct()?->getId());
        self::assertSame(self::KIT_ITEM_QUANTITY, $kitItemLineItem->getQuantity());
    }

    /**
     * @dataProvider submitWhenProductNotAllowedFailsProvider
     */
    public function testSubmitWhenProductNotAllowedFails(string $productReference): void
    {
        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);

        /** @var Product $productKit */
        $productKit = $this->getReference($productReference);
        /** @var ProductKitItem $productKitItem */
        $productKitItem = $this->getReference($productReference . '_item');
        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference(self::PRODUCT_SIMPLE1);

        $form = self::createForm(RequestProductKitConfigurationType::class, null, ['validation_groups' => false]);
        $form->submit([
            'product' => (string)$productKit->getId(),
            'kitItemLineItems' => [
                $productKitItem->getId() => [
                    'product' => $productSimple1->getId(),
                    'quantity' => self::KIT_ITEM_QUANTITY,
                ],
            ],
        ]);

        self::assertFalse($form->get('product')->isSynchronized());

        /** @var RequestProduct $requestProduct */
        $requestProduct = $form->getData();
        self::assertNull($requestProduct->getProduct());
        self::assertCount(0, $requestProduct->getKitItemLineItems());
    }

    public static function submitWhenProductNotAllowedFailsProvider(): iterable
    {
        yield 'disabled product kit' => ['product_kit_disabled'];
        yield 'hidden product kit' => ['product_kit_hidden'];
    }

    public function testSubmitWhenProductNotKitFails(): void
    {
        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);

        /** @var Product $productSimple1 */
        $productSimple1 = $this->getReference(self::PRODUCT_SIMPLE1);

        $form = self::createForm(RequestProductKitConfigurationType::class, null, ['validation_groups' => false]);
        $form->submit(['product' => (string)$productSimple1->getId()]);

        self::assertFalse($form->get('product')->isSynchronized());

        /** @var RequestProduct $requestProduct */
        $requestProduct = $form->getData();
        self::assertNull($requestProduct->getProduct());
    }

    public function testSubmitWhenProductInventoryStatusNotAllowedFails(): void
    {
        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);

        /** @var Product $productKit */
        $productKit = $this->getReference(self::PRODUCT_KIT_ALLOWED);

        $configManager = self::getConfigManager();
        $initialAllowedInventoryStatuses = $configManager->get(self::RFP_ALLOWED_INVENTORY_STATUSES);
        $configManager->set(
            self::RFP_ALLOWED_INVENTORY_STATUSES,
            [ExtendHelper::buildEnumOptionId(
                Product::INVENTORY_STATUS_ENUM_CODE,
                Product::INVENTORY_STATUS_DISCONTINUED
            )]
        );
        $configManager->flush();
        try {
            $form = self::createForm(
                RequestProductKitConfigurationType::class,
                null,
                ['validation_groups' => false]
            );
            $form->submit(['product' => (string)$productKit->getId()]);
        } finally {
            $configManager->set(self::RFP_ALLOWED_INVENTORY_STATUSES, $initialAllowedInventoryStatuses);
            $configManager->flush();
        }

        self::assertFalse($form->get('product')->isSynchronized());

        /** @var RequestProduct $requestProduct */
        $requestProduct = $form->getData();
        self::assertNull($requestProduct->getProduct());
    }

    /**
     * @dataProvider submitWhenProductIdMalformedFailsProvider
     */
    public function testSubmitWhenProductIdMalformedFails(string $productIdFormat): void
    {
        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);

        /** @var Product $productKit */
        $productKit = $this->getReference(self::PRODUCT_KIT_ALLOWED);

        $form = self::createForm(RequestProductKitConfigurationType::class, null, ['validation_groups' => false]);
        $form->submit(['product' => sprintf($productIdFormat, $productKit->getId())]);

        self::assertFalse($form->get('product')->isSynchronized());

        /** @var RequestProduct $requestProduct */
        $requestProduct = $form->getData();
        self::assertNull($requestProduct->getProduct());
    }

    public static function submitWhenProductIdMalformedFailsProvider(): iterable
    {
        yield 'allowed id followed by letters' => ['%dabc'];
        yield 'allowed id with a fraction' => ['%d.5'];
    }

    public function testSubmitWhenProductIdIsArrayFails(): void
    {
        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);

        /** @var Product $productKit */
        $productKit = $this->getReference(self::PRODUCT_KIT_ALLOWED);

        $form = self::createForm(RequestProductKitConfigurationType::class, null, ['validation_groups' => false]);
        $form->submit(['product' => [$productKit->getId()]]);

        self::assertFalse($form->get('product')->isSynchronized());

        /** @var RequestProduct $requestProduct */
        $requestProduct = $form->getData();
        self::assertNull($requestProduct->getProduct());
    }
}
