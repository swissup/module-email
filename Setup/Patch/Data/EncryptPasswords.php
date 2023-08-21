<?php

namespace Swissup\Email\Setup\Patch\Data;


use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class EncryptPasswords implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Swissup\Email\Model\Service\CollectionFactory
     */
    private $serviceCollectionFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Swissup\Email\Model\Service\CollectionFactory $serviceCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Swissup\Email\Model\Service\CollectionFactory $serviceCollectionFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->serviceCollectionFactory = $serviceCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $setup = $this->moduleDataSetup;
        $setup->startSetup();

        $collection = $this->serviceCollectionFactory->create();
        foreach ($collection as $service) {
            $service->load($service->getId());
            $service->setDataChanges(true);
            $service->save();
        }

        $setup->endSetup();

        return  $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.3.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
