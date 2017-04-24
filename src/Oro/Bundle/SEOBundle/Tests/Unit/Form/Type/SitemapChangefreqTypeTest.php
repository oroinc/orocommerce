<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SEOBundle\DependencyInjection\Configuration;
use Oro\Bundle\SEOBundle\Form\Type\SitemapChangefreqType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SitemapChangefreqTypeTest extends FormIntegrationTestCase
{
    /**
     * @var SitemapChangefreqType
     */
    private $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->type = new SitemapChangefreqType();

        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals(SitemapChangefreqType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(SitemapChangefreqType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'choices' => [
                        Configuration::CHANGEFREQ_ALWAYS
                            => 'oro.seo.system_configuration.fields.changefreq.choice.always.label',
                        Configuration::CHANGEFREQ_HOURLY
                            => 'oro.seo.system_configuration.fields.changefreq.choice.hourly.label',
                        Configuration::CHANGEFREQ_DAILY
                            => 'oro.seo.system_configuration.fields.changefreq.choice.daily.label',
                        Configuration::CHANGEFREQ_WEEKLY
                            => 'oro.seo.system_configuration.fields.changefreq.choice.weekly.label',
                        Configuration::CHANGEFREQ_MONTHLY
                            => 'oro.seo.system_configuration.fields.changefreq.choice.monthly.label',
                        Configuration::CHANGEFREQ_YEARLY
                            => 'oro.seo.system_configuration.fields.changefreq.choice.yearly.label',
                        Configuration::CHANGEFREQ_NEVER
                            => 'oro.seo.system_configuration.fields.changefreq.choice.never.label',
                    ],
                ]
            );

        $this->type->configureOptions($resolver);
    }
}
