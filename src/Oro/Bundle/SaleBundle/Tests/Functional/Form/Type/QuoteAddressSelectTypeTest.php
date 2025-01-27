<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Tests\Functional\Form\Type;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\SaleBundle\Form\Type\QuoteAddressSelectType;
use Oro\Bundle\SaleBundle\Provider\QuoteAddressProvider;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteAddressData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\FormType;

final class QuoteAddressSelectTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
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
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertNull($form->getData());
    }

    public function testCanBeCreatedWithCustomerUserAddressInitialData(): void
    {
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');
        $form = self::createForm(
            FormType::class,
            ['customerAddress' => $customerUserAddress]
        );
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertSame(['customerAddress' => $customerUserAddress], $form->getData());
    }

    public function testCanBeCreatedWithEnterManuallyInitialData(): void
    {
        $form = self::createForm(
            FormType::class,
            ['customerAddress' => QuoteAddressSelectType::ENTER_MANUALLY]
        );
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        self::assertSame(['customerAddress' => QuoteAddressSelectType::ENTER_MANUALLY], $form->getData());
    }

    public function testHasOptions(): void
    {
        $form = self::createForm();
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $addressManager = self::getContainer()->get('oro_sale.manager.quote_address');
        $addressCollection = $addressManager->getGroupedAddresses(
            $quote,
            QuoteAddressProvider::ADDRESS_TYPE_SHIPPING,
            'oro.sale.quote.'
        );

        self::assertFormHasField($form, 'customerAddress', QuoteAddressSelectType::class, [
            'data_class' => null,
            'label' => false,
            'placeholder' => false,
            'address_collection' => $addressCollection,
        ]);
    }

    public function testSubmitWithEmptyDataWhenEmptyInitialData(): void
    {
        $form = self::createForm();
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit([]);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertSame(['customerAddress' => null], $form->getData());
    }

    public function testSubmitWithCustomerUserAddressWhenEmptyInitialData(): void
    {
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $form = self::createForm();
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit(['customerAddress' => 'au_' . $customerUserAddress->getId()]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertSame(['customerAddress' => $customerUserAddress], $form->getData());
    }

    public function testSubmitWithEmptyDataWhenNotEmptyInitialData(): void
    {
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $form = self::createForm(FormType::class, ['customerAddress' => $customerUserAddress]);
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit(['customerAddress' => null]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertSame(['customerAddress' => null], $form->getData());
    }

    public function testSubmitWithCustomerUserAddressWhenNotEmptyInitialData(): void
    {
        $customerUserAddress = $this->getReference('sale.grzegorz.brzeczyszczykiewicz@example.com.address_1');

        $form = self::createForm(FormType::class, ['customerAddress' => QuoteAddressSelectType::ENTER_MANUALLY]);
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $form->submit(['customerAddress' => 'au_' . $customerUserAddress->getId()]);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true));
        self::assertTrue($form->isSynchronized());

        self::assertSame(['customerAddress' => $customerUserAddress], $form->getData());
    }

    public function testHasChoices(): void
    {
        $form = self::createForm();
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $formView = $form->createView();

        $choices = $formView['customerAddress']->vars['choices'];
        self::assertContainsEquals(
            new ChoiceView(0, '0', 'oro.sale.quote.form.address.manual', [], []),
            $choices
        );

        self::assertArrayHasKey(
            'oro.sale.quote.form.address.group_label.customer_user',
            $choices
        );

        self::assertEquals(
            'oro.sale.quote.form.address.group_label.customer_user',
            $choices['oro.sale.quote.form.address.group_label.customer_user']->label
        );

        $addressManager = self::getContainer()->get('oro_sale.manager.quote_address');
        /** @var AddressFormatter $addressFormatter */
        $addressFormatter = self::getContainer()->get('oro_locale.formatter.address');

        $addressCollection = $addressManager->getGroupedAddresses(
            $quote,
            QuoteAddressProvider::ADDRESS_TYPE_SHIPPING,
            'oro.sale.quote.'
        );
        $addressCollectionArray = $addressCollection->toArray();
        $customerUserAddresses = $addressCollectionArray['oro.sale.quote.form.address.group_label.customer_user'];

        self::assertCount(
            count($customerUserAddresses),
            $choices['oro.sale.quote.form.address.group_label.customer_user']->choices
        );

        foreach ($choices['oro.sale.quote.form.address.group_label.customer_user']->choices as $choiceView) {
            self::assertArrayHasKey($choiceView->value, $customerUserAddresses);
            self::assertEquals($choiceView->data, $customerUserAddresses[$choiceView->value]);
            self::assertEquals(
                $choiceView->label,
                $addressFormatter->format($customerUserAddresses[$choiceView->value], null, ', ')
            );
        }
    }

    public function testHasViewVars(): void
    {
        $form = self::createForm();
        $quote = $this->getReference(LoadQuoteData::QUOTE3);
        $form->add(
            'customerAddress',
            QuoteAddressSelectType::class,
            ['quote' => $quote, 'address_type' => QuoteAddressProvider::ADDRESS_TYPE_SHIPPING]
        );

        $formView = $form->createView();

        $addressManager = self::getContainer()->get('oro_sale.manager.quote_address');
        $serializer = self::getContainer()->get('oro_importexport.serializer');

        $addressCollection = $addressManager->getGroupedAddresses(
            $quote,
            QuoteAddressProvider::ADDRESS_TYPE_SHIPPING,
            'oro.sale.quote.'
        );

        $plainAddresses = [];
        array_walk_recursive($addressCollection, function ($item, $key) use (&$plainAddresses, $serializer) {
            if ($item instanceof AbstractAddress) {
                $plainAddresses[$key] = $serializer->normalize($item);
            }
        });

        self::assertEquals(json_encode($plainAddresses), $formView['customerAddress']->vars['attr']['data-addresses']);
        self::assertEquals(
            $addressCollection->getDefaultAddressKey(),
            $formView['customerAddress']->vars['attr']['data-default']
        );
    }
}
