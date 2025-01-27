<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Form\Type\AddressValidatedAtType;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Form\Type\QuoteAddressSelectType;
use Oro\Bundle\SaleBundle\Form\Type\QuoteAddressType;
use Oro\Bundle\SaleBundle\Model\QuoteAddressManager;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteAddressData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class QuoteAddressTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override] protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader()
        );
        $this->loginUser(self::AUTH_USER);
        $this->updateUserSecurityToken(self::AUTH_USER);

        $this->loadFixtures([LoadQuoteAddressData::class, LoadCustomerUserAddresses::class]);
    }

    public function testCanBeCreatedWithEmptyInitialData(): void
    {
        $form = self::createForm();
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertNull($form->getData());
    }

    public function testCanBeCreatedWithQuoteAddressInitialData(): void
    {
        $quoteAddress = $this->getReference(LoadQuoteAddressData::QUOTE_ADDRESS_1);
        $form = self::createForm(FormType::class, ['quoteAddress' => $quoteAddress]);
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertSame(['quoteAddress' => $quoteAddress], $form->getData());
        self::assertSame(
            QuoteAddressSelectType::ENTER_MANUALLY,
            $form->get('quoteAddress')->get('customerAddress')->getData()
        );
    }

    public function testCanBeCreatedWithCustomerUserAddressInitialData(): void
    {
        /** @var QuoteAddressManager $quoteAddressManager */
        $quoteAddressManager = self::getContainer()->get('oro_sale.manager.quote_address');
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');

        /** @var QuoteAddress $quoteAddress */
        $quoteAddress = $quoteAddressManager->updateFromAbstract($customerUserAddress);

        $form = self::createForm(FormType::class, ['quoteAddress' => $quoteAddress]);
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertSame(['quoteAddress' => $quoteAddress], $form->getData());
        self::assertSame(
            $customerUserAddress,
            $form->get('quoteAddress')->get('customerAddress')->getData()
        );
    }

    public function testHasFields(): void
    {
        $quoteAddress = $this->getReference(LoadQuoteAddressData::QUOTE_ADDRESS_1);
        $form = self::createForm(FormType::class, ['quoteAddress' => $quoteAddress]);
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertFormHasField($form->get('quoteAddress'), 'customerAddress', QuoteAddressSelectType::class, [
            'quote' => $quote,
            'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING,
            'required' => false,
            'mapped' => false,
        ]);

        self::assertFormHasField(
            $form->get('quoteAddress'),
            'phone',
            TextType::class,
            [
                'required' => false,
                StripTagsExtension::OPTION_NAME => true,
            ]
        );

        self::assertFormHasField($form->get('quoteAddress'), 'validatedAt', AddressValidatedAtType::class);
    }

    public function testIsDisabledWhenNotNewAddress(): void
    {
        /** @var QuoteAddress $quoteAddress */
        $quoteAddress = $this->getReference(LoadQuoteAddressData::QUOTE_ADDRESS_1);
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $quoteAddress->setCustomerUserAddress($customerUserAddress);

        $form = self::createForm(FormType::class, ['quoteAddress' => $quoteAddress]);
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertFormHasField($form->get('quoteAddress'), 'customerAddress', QuoteAddressSelectType::class, [
            'quote' => $quote,
            'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING,
            'required' => false,
            'mapped' => false,
        ]);

        /** @var FormInterface $child */
        foreach ($form->get('quoteAddress') as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'])) {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }

        // Reverts quoteAddress back to
        $quoteAddress->setCustomerUserAddress(null);
    }

    public function testSubmitWithEmptyDataWhenEmptyInitialData(): void
    {
        $form = self::createForm(FormType::class, null, ['validation_groups' => false]);
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit([]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(['quoteAddress' => new quoteAddress()], $form->getData());
    }

    public function testSubmitWithCustomerUserAddressDataWhenEmptyInitialData(): void
    {
        /** @var QuoteAddressManager $quoteAddressManager */
        $quoteAddressManager = self::getContainer()->get('oro_sale.manager.quote_address');
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $form = self::createForm(FormType::class, null, ['validation_groups' => false]);
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit(['quoteAddress' => ['customerAddress' => 'au_' . $customerUserAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertEquals(
            ['quoteAddress' => $quoteAddressManager->updateFromAbstract($customerUserAddress)],
            $form->getData()
        );

        /** @var FormInterface $child */
        foreach ($form->get('quoteAddress') as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'])) {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }
    }

    public function testSubmitWithManuallyEnteredAddressDataWhenEmptyInitialData(): void
    {
        /** @var QuoteAddressManager $quoteAddressManager */
        $quoteAddressManager = self::getContainer()->get('oro_sale.manager.quote_address');
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $form = self::createForm(FormType::class, null, ['validation_groups' => false]);
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        $form->submit([
            'quoteAddress' => [
                    'customerAddress' => QuoteAddressSelectType::ENTER_MANUALLY,
                    'country' => $customerUserAddress->getCountryIso2(),
                    'region' => $customerUserAddress->getRegion()->getCombinedCode(),
                ] + $serializer->normalize($customerUserAddress),
        ]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = $quoteAddressManager->updateFromAbstract($customerUserAddress);
        $expectedAddress->setCustomerUserAddress(null);
        $this->normalizeAddressEntity($expectedAddress);

        $actualAddress = $form->getData()['quoteAddress'];
        $this->normalizeAddressEntity($actualAddress);

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );

        /** @var FormInterface $child */
        foreach ($form->get('quoteAddress') as $child) {
            self::assertFalse(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected not to be disabled'
            );
        }
    }

    public function testSubmitWithCustomerUserAddressDataWhenManuallyEnteredInitialData(): void
    {
        /** @var QuoteAddressManager $quoteAddressManager */
        $quoteAddressManager = self::getContainer()->get('oro_sale.manager.quote_address');
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');
        $quoteAddress = $this->getReference(LoadQuoteAddressData::QUOTE_ADDRESS_1);

        $form = self::createForm(
            FormType::class,
            ['quoteAddress' => clone $quoteAddress],
            ['validation_groups' => false]
        );
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit(['quoteAddress' => ['customerAddress' => 'au_' . $customerUserAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = $quoteAddressManager->updateFromAbstract($customerUserAddress, clone $quoteAddress);
        $this->normalizeAddressEntity($expectedAddress);

        $actualAddress = $form->getData()['quoteAddress'];
        $this->normalizeAddressEntity($actualAddress);

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );

        /** @var FormInterface $child */
        foreach ($form->get('quoteAddress') as $child) {
            if (in_array($child->getName(), ['customerAddress', 'validatedAt'])) {
                continue;
            }

            self::assertTrue(
                $child->getConfig()->getOption('disabled'),
                $child->getName() . ' is expected to be disabled'
            );
        }
    }

    public function testSubmitWithCustomerUserAddressDataWhenCustomerUserAddressDataInitialData(): void
    {
        /** @var QuoteAddressManager $quoteAddressManager */
        $quoteAddressManager = self::getContainer()->get('oro_sale.manager.quote_address');
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');
        $quoteAddress = $quoteAddressManager->updateFromAbstract($customerUserAddress);

        $form = self::createForm(FormType::class, ['quoteAddress' => $quoteAddress], ['validation_groups' => false]);
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $otherAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_3');
        $form->submit(['quoteAddress' => ['customerAddress' => 'au_' . $otherAddress->getId()]]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = $quoteAddressManager->updateFromAbstract($otherAddress);
        $this->normalizeAddressEntity($expectedAddress);

        $actualAddress = $form->getData()['quoteAddress'];
        $this->normalizeAddressEntity($actualAddress);

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );
    }

    public function testSubmitAddressIsNotChangedIfCustomerUserAddressIdIsSame(): void
    {
        /** @var QuoteAddressManager $quoteAddressManager */
        $quoteAddressManager = self::getContainer()->get('oro_sale.manager.quote_address');
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');
        $quoteAddress = $quoteAddressManager->updateFromAbstract($customerUserAddress);
        $quoteAddress->setStreet('Overridden street that should not be changed');

        $form = self::createForm(FormType::class, ['quoteAddress' => $quoteAddress], ['validation_groups' => false]);
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'quoteAddress',
            QuoteAddressType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        /** @var Serializer $serializer */
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        $form->submit(
            [
                'quoteAddress' => ['customerAddress' => 'au_' . $customerUserAddress->getId()],
            ],
            false
        );

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        $expectedAddress = clone $quoteAddress;
        $this->normalizeAddressEntity($quoteAddress);

        $actualAddress = $form->getData()['quoteAddress'];
        $this->normalizeAddressEntity($actualAddress);

        self::assertEquals(
            $serializer->normalize($actualAddress),
            $serializer->normalize($expectedAddress)
        );

        self::assertEquals('Overridden street that should not be changed', $actualAddress->getStreet());
    }

    private function normalizeAddressEntity(AbstractAddress $address): void
    {
        ReflectionUtil::setPropertyValue($address, 'id', null);
        ReflectionUtil::setPropertyValue($address, 'extendEntityStorage', null);
        $address->setCreated(null);
        $address->setUpdated(null);
    }
}
