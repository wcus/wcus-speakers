<?php

declare(strict_types=1);

namespace OpenCFP\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use phpDocumentor\Reflection\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200312193057 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add wporg username & speaker experience.';
    }

    public function up(Schema $schema) : void
    {
         $table = $schema->getTable('users');
         $table->addColumn('wporg', 'string', ['notnull' => false]);
         $table->addColumn('exp', 'text', ['notnull' => false]);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('users');
        $table->removeColumn('wporg');
        $table->removeColumn('exp');
    }
}
