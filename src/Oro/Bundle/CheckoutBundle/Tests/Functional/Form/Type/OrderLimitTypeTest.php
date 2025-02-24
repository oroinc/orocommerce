<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CheckoutBundle\Form\Type\OrderLimitType;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class OrderLimitTypeTest extends WebTestCase
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
    }

    public function testCanBeCreatedWithEmptyInitialData(): void
    {
        $form = self::createForm(OrderLimitType::class);

        self::assertNull($form->getData());
    }

    public function testCanBeCreatedWithInitialData(): void
    {
        $form = self::createForm(OrderLimitType::class, [
            'value' => 12.34,
            'currency' => 'EUR'
        ]);

        self::assertSame(
            [
                'value' => 12.34,
                'currency' => 'EUR'
            ],
            $form->getData()
        );
    }

    public function testHasFields(): void
    {
        $form = self::createForm(OrderLimitType::class);

        self::assertFormHasField($form, 'value', NumberType::class, [
            'scale' => Price::MAX_VALUE_SCALE,
        ]);
        self::assertFormHasField($form, 'currency', HiddenType::class);
    }

    public function testSubmitWithEmptyData(): void
    {
        $form = self::createForm(OrderLimitType::class);

        $form->submit([]);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertSame(
            [
                'value' => null,
                'currency' => null,
            ],
            $form->getData()
        );
    }

    public function testSubmitWithData(): void
    {
        $form = self::createForm(OrderLimitType::class);

        $form->submit([
            'value' => 12.34,
            'currency' => 'EUR',
        ]);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        self::assertSame(
            [
                'value' => 12.34,
                'currency' => 'EUR',
            ],
            $form->getData()
        );
    }

    public function testSubmitWithInvalidValueNegative(): void
    {
        $form = self::createForm(OrderLimitType::class);

        $form->submit([
            'value' => -12.34,
            'currency' => 'EUR',
        ]);

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertStringContainsString(
            'Only positive numeric values are allowed',
            (string)$form->getErrors(true)
        );
    }

    public function testSubmitWithInvalidValueNonFloat(): void
    {
        $form = self::createForm(OrderLimitType::class);

        $form->submit([
            'value' => 'qwe',
            'currency' => 'EUR',
        ]);

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertStringContainsString(
            'Please enter a number.',
            (string)$form->getErrors(true)
        );
    }
}
