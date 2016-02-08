<?php
//delete from setup_module where module='Swissup_Email';
namespace Swissup\Email\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        $table = $installer->getConnection()
            ->newTable($installer->getTable('swissup_email_service'))
            ->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10, [
                'identity'  => true,
                'unsigned'  => true,
                'nullable'  => false,
                'primary'   => true,
            ], 'Id')
            ->addColumn('name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 45, [
                'nullable'  => true,
                'default'   => null,
            ], 'Name')
            ->addColumn('status', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 6, [
                'nullable'  => false,
                'default'   => 1,
            ], 'Status')
            ->addColumn('type', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 6, [
                'nullable'  => false,
                'default'   => 1,
            ], 'Type')
            ->addColumn('email', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 45, [
                'nullable'  => false,
                'default'   => '',
            ], 'email from')
            ->addColumn('user', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 45, [
                'nullable'  => false,
                'default'   => '',
            ], 'User')
            ->addColumn('password', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 45, [
                'nullable'  => false,
                'default'  => '',
            ], 'Password')
            ->addColumn('host', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 128, [
                'nullable'  => false,
                'default'   => '',
            ], 'Host')
            ->addColumn('port', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5, [
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => 110,
            ], 'Port')
            ->addColumn('secure', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5, [
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => 0,
            ], 'Secure')
            ->addColumn('auth', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 7, [
                'nullable'  => false,
                'default'   => '',
            ], 'Auth')
            // ->addColumn('key', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, null, [
            //     'nullable'  => false,
            //     'default'   => ''
            // ], 'Key')
            // ->addColumn('remove', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 6, [
            //     'nullable'  => false,
            //     'default'  => 0,
            // ], 'Remove')

        ;
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
