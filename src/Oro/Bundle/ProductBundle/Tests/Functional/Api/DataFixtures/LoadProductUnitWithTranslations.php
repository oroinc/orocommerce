<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadProductUnitWithTranslations extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->addTranslations();

        $productUnit = new ProductUnit();
        $productUnit->setCode('day');
        $productUnit->setDefaultPrecision(1);
        $manager->persist($productUnit);
        $manager->flush();

        $this->addReference('day', $productUnit);
    }

    private function addTranslations(): void
    {
        $translations = [
            'messages'   => [
                'oro.product_unit.day.label.full'         => 'day',
                'oro.product_unit.day.label.full_plural'  => 'days',
                'oro.product_unit.day.label.short'        => 'd',
                'oro.product_unit.day.label.short_plural' => 'ds',

                'oro.product_unit.day.value.full'                => '{0} none|{1} %count% day|]1,Inf] %count% days',
                'oro.product_unit.day.value.full_fraction'       => '%count% day',
                'oro.product_unit.day.value.full_fraction_gt_1'  => '%count% days',
                'oro.product_unit.day.value.short'               => '{0} none|{1} %count% d|]1,Inf] %count% ds',
                'oro.product_unit.day.value.short_fraction'      => '%count% d',
                'oro.product_unit.day.value.short_fraction_gt_1' => '%count% ds',
            ],
            'jsmessages' => [
                'oro.product.product_unit.day.label.full'         => 'day',
                'oro.product.product_unit.day.label.full_plural'  => 'days',
                'oro.product.product_unit.day.label.short'        => 'd',
                'oro.product.product_unit.day.label.short_plural' => 'ds',

                'oro.product.product_unit.day.value.full'  => '{0} none|]0,1] {{ count }} day|]1,Inf]{{ count }} days',
                'oro.product.product_unit.day.value.short' => '{0} none|]0,1] {{ count }} d|]1,Inf]{{ count }} ds',
                'oro.product.product_unit.day.value.label' => '{0} none|]0,1] d|]1,Inf] ds',
            ]
        ];

        $translator = $this->container->get('translator');
        $translationManager = $this->container->get('oro_translation.manager.translation');
        $locale = $translator->getLocale();
        foreach ($translations as $domain => $translates) {
            foreach ($translates as $key => $translate) {
                $translationManager->saveTranslation($key, $translate, $locale, $domain, Translation::SCOPE_UI);
            }
        }
        $translationManager->flush();
    }
}
