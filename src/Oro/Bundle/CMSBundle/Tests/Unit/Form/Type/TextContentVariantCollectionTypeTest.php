<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantCollectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextContentVariantCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var TextContentVariantCollectionType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->type = new TextContentVariantCollectionType();
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMockBuilder(OptionsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->any())
            ->method('setDefault')
            ->withConsecutive(
                ['prototype_name', '__variant_idx__']
            );
        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->type->getParent());
    }
}
