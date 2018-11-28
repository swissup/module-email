<?php

namespace Swissup\Email\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Upgrade DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $setup->startSetup();
        $connection = $setup->getConnection();

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            /**
             * Create table 'swissup_email_history'
             */
            $tableName = $setup->getTable('swissup_email_history');
            if ($installer->tableExists($tableName)) {
                return;
            }
            $table = $connection->newTable($tableName)
                ->addColumn(
                    'entity_id',
                    Table::TYPE_BIGINT,
                    null,
                    [
                        'identity'  => true,
                        'unsigned'  => true,
                        'nullable'  => false,
                        'primary'   => true,
                    ],
                    'Entity ID'
                )
                ->addColumn(
                    'from',
                    Table::TYPE_TEXT,
                    128,
                    [],
                    'From'
                )
                ->addColumn(
                    'to',
                    Table::TYPE_TEXT,
                    128,
                    [],
                    'To'
                )
                ->addColumn(
                    'subject',
                    Table::TYPE_TEXT,
                    256,
                    [],
                    'Subject'
                )
                ->addColumn(
                    'body',
                    Table::TYPE_TEXT,
                    null,
                    [],
                    'Body'
                )
                ->addColumn(
                    'service_id',
                    Table::TYPE_INTEGER,
                    10,
                    [],
                    'Service Id'
                )
                // ->addColumn(
                //     'store_id',
                //     Table::TYPE_INTEGER,
                //     null,
                //     [],
                //     'Store Id'
                // )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                    'Created At'
                )->setComment(
                    'Emails History Table'
                );
            $connection->createTable($table);
        }

        $setup->endSetup();
    }
}
