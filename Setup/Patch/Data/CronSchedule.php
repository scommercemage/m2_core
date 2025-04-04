<?php

namespace Scommerce\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;

class CronSchedule implements DataPatchInterface
{
    private $moduleDataSetup;

    private $resourceConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ResourceConfig $resourceConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConfig $resourceConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resourceConfig = $resourceConfig;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        $min = rand(0, 59);
        $hour = rand(0, 3);
        $cronSchedule = "$min $hour * * *";
        $this->resourceConfig->saveConfig('scommerce_core/general/cron', $cronSchedule);
        $this->moduleDataSetup->endSetup();
    }
}
