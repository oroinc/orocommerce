<?php

namespace Oro\Bundle\CmsBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantCollectionType;

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
        $this->assertEquals(CollectionType::NAME, $this->type->getParent());
    }
}
