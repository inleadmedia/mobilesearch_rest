<?php

namespace AppBundle\Resources\MigrationsMongoDB;

use AntiMattr\MongoDB\Migrations\AbstractMigration;
use Doctrine\MongoDB\Database;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20190207151615 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return "Updates content taxonomy structure.";
    }

    public function up(Database $db)
    {
        // this up() migration is auto-generated, please modify it to your needs

    }

    public function down(Database $db)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
