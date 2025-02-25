<?php

namespace Scommerce\Core\Model;

use Scommerce\Core\Model\Config;

class LicenseUpdater
{
    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function createOrUpdateLicense($websiteId, $moduleName, $version)
    {
        $registeredVersionPath = $this->config->getRegisteredVersionPath($moduleName);
        $lastVerifyDatePath = $this->config->getLastVerifyDatePath($moduleName);
        $verifyDate = date('Ymd');

        $this->config->setValueByPath($registeredVersionPath, $version, $websiteId);
        $this->config->setValueByPath($lastVerifyDatePath, $verifyDate, $websiteId);
    }

    public function removeLicense($websiteId, $sku)
    {
        $registeredVersionPath = $this->config->getRegisteredVersionPath($sku);

        $this->config->setValueByPath($registeredVersionPath, '0', $websiteId);
    }
}
