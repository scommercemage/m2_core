<?php

namespace Scommerce\Core\Model;

use Scommerce\Core\Model\Config;
use Scommerce\Core\Model\SendVerify;
use Scommerce\Core\Model\InstalledModules;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;
use Magento\Framework\Serialize\Serializer\Json;

class VerifyLicenses
{
    /**
     * @var InstalledModules
     */
    protected $installedModules;

    /**
     * @var CollectionFactory
     */
    protected $websiteCollectionFactory;
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SendVerify
     */
    protected $sendVerify;

    /**
     * @var Json
     */
    protected $json;

    public function __construct(
        CollectionFactory   $collectionFactory,
        Config              $config,
        SendVerify          $sendVerify,
        Json                $json,
        InstalledModules    $installedModules
    ) {
        $this->websiteCollectionFactory = $collectionFactory;
        $this->config = $config;
        $this->sendVerify = $sendVerify;
        $this->json = $json;
        $this->installedModules = $installedModules;
    }

    public function execute($isCron = false)
    {
        $websites = $this->websiteCollectionFactory->create();

        foreach ($websites as $website) {
            $websiteId = $website->getId();
            $websiteModules = $this->json->unserialize($this->config->getModules($websiteId));
            if (count($websiteModules) == 0) {
                $websiteModules = $this->installedModules->getModuleList($websiteId);
            }
            foreach ($websiteModules as $moduleName => $moduleData) {
                if ($isCron) {
                    $lastVerifyDatePath = $this->config->getLastVerifyDatePath($moduleName);
                    $lastVerifyDate = $this->config->getValueByPath($lastVerifyDatePath, $websiteId);
                    if ($lastVerifyDate) {
                        $lastVerifyDateObject = \DateTime::createFromFormat('Ymd', $lastVerifyDate);
                        $todayDateObject = \DateTime::createFromFormat('Ymd', date('Ymd'));
                        $diff = $lastVerifyDateObject->diff($todayDateObject);
                        if ($diff->days > 7) {
                            $this->sendVerify->sendVerify($moduleName, $websiteId);
                        }
                    } else {
                        $this->sendVerify->sendVerify($moduleName, $websiteId);
                    }

                } else {
                    $this->sendVerify->sendVerify($moduleName, $websiteId);
                }
            }
        }
    }
}
