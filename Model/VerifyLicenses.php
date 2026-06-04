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

            // Fix: Prevent passing null to unserialize
            $modulesRaw = $this->config->getModules($websiteId);
            $websiteModules = [];

            if (!empty($modulesRaw)) {
                try {
                    $websiteModules = $this->json->unserialize((string)$modulesRaw);
                } catch (\InvalidArgumentException $e) {
                    $websiteModules = [];
                }
            }

            // Fix: Prevent calling count() on a non-countable variable
            if (!is_array($websiteModules) || count($websiteModules) == 0) {
                $websiteModules = $this->installedModules->getModuleList($websiteId);
            }

            if (!is_array($websiteModules)) {
                $websiteModules = [];
            }

            foreach ($websiteModules as $moduleName => $moduleData) {
                if ($isCron) {
                    $lastVerifyDatePath = $this->config->getLastVerifyDatePath($moduleName);
                    $lastVerifyDate = $this->config->getValueByPath($lastVerifyDatePath, $websiteId);

                    // Fix: Prevent passing null/false to DateTime and ensure methods don't fatal
                    if (!empty($lastVerifyDate)) {
                        $lastVerifyDateObject = \DateTime::createFromFormat('Ymd', (string)$lastVerifyDate);
                        $todayDateObject = \DateTime::createFromFormat('Ymd', date('Ymd'));

                        if ($lastVerifyDateObject && $todayDateObject) {
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

                } else {
                    $this->sendVerify->sendVerify($moduleName, $websiteId);
                }
            }
        }
    }
}