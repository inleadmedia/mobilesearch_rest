<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class EnsureIndexCommand.
 *
 * Command to create default Content collection index.
 */
class EnsureIndexCommand extends ContainerAwareCommand {
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('mos:index:create')
            ->setDescription('Ensures index on Content collection.');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexes = $this->getContainer()->getParameter('mongo_content_index');

        $connection = $this->getContainer()->get('doctrine_mongodb')->getConnection();
        /** @var \MongoClient $mongo */
        $mongo = $connection->getMongo();
        /** @var \MongoDB $db */
        $db = $mongo->selectDB($this->getContainer()->getParameter('mongo_db'));
        /** @var \MongoCollection $collection */
        $collection = $db->selectCollection('Content');
        $collection->deleteIndexes();

        foreach ($indexes as $indexDefinition) {
            $createIndexResult = $collection->ensureIndex($indexDefinition[0], $indexDefinition[1]);
            if (array_key_exists('note', $createIndexResult)) {
                $output->writeln($createIndexResult['note']);
            }
        }

    }
}

