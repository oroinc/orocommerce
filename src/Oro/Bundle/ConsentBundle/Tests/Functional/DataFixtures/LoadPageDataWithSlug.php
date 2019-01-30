<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadPageDataWithSlug extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const PAGE_1 = 'page.1';
    const PAGE_2 = 'page.2';
    const PAGE_3 = 'page.3';
    const PAGE_1_SLUG = 'page1';
    const PAGE_2_SLUG = 'page2';
    const PAGE_3_SLUG = 'page3';

    /**
     * @var array
     */
    protected static $page = [
        self::PAGE_1 => [
            'slug' => 'page1'
        ],
        self::PAGE_2 => [
            'slug' => 'page2'
        ],
        self::PAGE_3 => [
            'slug' => 'page3'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $slugGenerator = $this->container->get('oro_redirect.generator.slug_entity');

        foreach (self::$page as $pageReference => $data) {
            $entity = new Page();
            $entity->addTitle((new LocalizedFallbackValue())->setString($pageReference));
            $entity->setContent($pageReference);
            $slugGenerator->generate($entity);

            $cache = $this->container->get('oro_redirect.url_cache');
            if ($cache instanceof FlushableCache) {
                $cache->flushAll();
            }

            $this->setReference($pageReference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
