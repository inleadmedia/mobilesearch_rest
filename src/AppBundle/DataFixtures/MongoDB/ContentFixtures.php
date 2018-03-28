<?php

namespace AppBundle\DataFixtures\MongoDB;

use AppBundle\Document\Content;
use AppBundle\Services\FixtureLoader;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;

class ContentFixtures extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var FixtureLoader $fixtureLoader */
        $fixtureLoader = $this->container->get('fixture_loader');
        $newsDefinitions = $fixtureLoader->load('news.yml');
        $eventDefitions = $fixtureLoader->load('events.yml');

        $faker = Factory::create();
        $now = time();

        foreach (array_merge($newsDefinitions, $eventDefitions) as $fixture) {
            $content = new Content();

            // Set some random fields which are defined as null.
            $content->setNid($fixture['nid'] ?? mt_rand());
            $content->setAgency($fixture['agency'] ?? mt_rand(100000, 999999));
            $content->setType($fixture['type'] ?? 'ding_'.$faker->word);

            foreach ($fixture['fields'] as $field => &$values) {
                if (is_null($values['value'])) {
                    switch ($field) {
                        case 'title':
                            $value = $faker->sentence;
                            break;
                        case 'author':
                            $value = $faker->name;
                            break;
                        case 'created':
                            // Assume creation time couple of hours ago.
                            $value = gmdate('c', $now - mt_rand(1, 5) * 86400);
                            break;
                        case 'changed':
                            // Assume update time couple of minutes ago.
                            $value = gmdate('c', $now - mt_rand(1, 59) * 60);
                            break;
                        default:
                            $value = null;
                    }

                    $values['value'] = $value;
                }
            }

            $content->setFields($fixture['fields'] ?? []);
            $content->setTaxonomy($fixture['taxonomy'] ?? []);
            $content->setList($fixture['list'] ?? []);

            $manager->persist($content);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            AgencyFixtures::class,
        ];
    }
}
