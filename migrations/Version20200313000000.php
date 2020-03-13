<?php

declare(strict_types=1);

namespace OpenCFP\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use phpDocumentor\Reflection\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200313000000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add "prerecorded" boolean to talks table.';
    }

    public function up(Schema $schema) : void
    {
         $table = $schema->getTable('talks');
         $table->addColumn('prerecorded', 'boolean', ['notnull' => true, 'default' => false]);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('talks');
        $table->removeColumn('prerecorded');
    }
}
