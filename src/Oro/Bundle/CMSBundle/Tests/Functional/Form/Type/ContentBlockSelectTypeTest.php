<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\ContentBlockSelectType;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadTextContentVariantsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class ContentBlockSelectTypeTest extends WebTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        $this->initClient();

        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->loadFixtures([
            LoadTextContentVariantsData::class,
        ]);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?string $submitData, bool $isValid): void
    {
        $data = $submitData ? $this->getReference($submitData) : null;

        $form = $this->formFactory->create(ContentBlockSelectType::class, null);
        $form->submit($data ? $data->getId() : null);

        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized(), $form->getTransformationFailure()?->getMessage() ?? '');

        if ($isValid) {
            $this->assertEquals($data, $form->getData());
        }
    }

    public function submitDataProvider(): \Generator
    {
        yield 'empty data' => [
            'submitData' => null,
            'isValid' => true,
        ];

        yield 'valid data' => [
            'submitData' => 'content_block_1',
            'isValid' => true,
        ];
    }

    public function testSubmitWhenNoContentVariants(): void
    {
        $data = $this->getReference('content_block_2');

        $form = $this->formFactory->create(ContentBlockSelectType::class, null);
        $form->submit($data->getId());

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->isSynchronized(), $form->getTransformationFailure()?->getMessage() ?? '');
        self::assertStringContainsString('Please add at least one content variant', trim((string) $form->getErrors()));
    }
}
