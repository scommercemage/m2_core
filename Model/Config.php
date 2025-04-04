<?php

namespace Scommerce\Core\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;

class Config
{
    protected $coreConfigTable = null;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var WriterInterface
     */
    protected $writer;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Json
     */
    protected $json;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer,
        TypeListInterface $cacheTypeList,
        ResourceConnection $resourceConnection,
        Json $json
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->writer = $writer;
        $this->cacheTypeList = $cacheTypeList;
        $this->resourceConnection = $resourceConnection->getConnection();
        $this->coreConfigTable = $resourceConnection->getTableName('core_config_data');
        $this->json = $json;
    }
    const MODULES           = 'scommerce_core/general/modules';

    const API_ENDPOINT      = 'scommerce_core/general/api_endpoint';

    const STORE_EMAIL       = 'trans_email/ident_support/email';

    const WEBSITE           = 'website';

    public function getStoreEmail($websiteId)
    {
        return $this->scopeConfig->getValue(self::STORE_EMAIL, self::WEBSITE, $websiteId);
    }

    public function getLicenseKey($path, $websiteId)
    {
        return $this->scopeConfig->getValue($path, self::WEBSITE, $websiteId);
    }

    public function getRegisteredVersion($sku, $websiteId)
    {
        $select = $this->resourceConnection
            ->select()
            ->from($this->coreConfigTable, 'value')
            ->where('`path`=:path and `scope`=:scope and `scope_id`=:scope_id');
        $result = $this->resourceConnection->fetchRow($select, [
            'path' => $this->getRegisteredVersionPath($sku),
            'scope' => self::WEBSITE,
            'scope_id' => (int)$websiteId
        ]);
        if (!$result) {
            $result = $this->scopeConfig->getValue($this->getRegisteredVersionPath($sku)) ?? "[]";
        } else {
            $result = $result['value'];
        }
        return $result;
    }

    public function getInstalledVersion($sku, $websiteId)
    {
        $modules = $this->json->unserialize($this->getModules($websiteId));
        if (isset($modules[$sku])) {
            return $modules[$sku]['installed_version'];
        } else {
            return null;
        }
    }

    public function getSkuByModuleName($moduleName)
    {
        return strtolower(str_replace('Scommerce_', '', $moduleName));
    }

    public function getModules($websiteId = null)
    {
        $select = $this->resourceConnection
            ->select()
            ->from($this->coreConfigTable, 'value')
            ->where('`path`=:path and `scope`=:scope and `scope_id`=:scope_id');
        $result = $this->resourceConnection->fetchRow($select, [
            'path' => self::MODULES,
            'scope' => self::WEBSITE,
            'scope_id' => (int)$websiteId
        ]);
        if (!$result) {
            $result = $this->scopeConfig->getValue(self::MODULES) ?? "[]";
        } else {
            $result = $result['value'];
        }
        return $result;
    }

    public function getApiEndpoint()
    {
        return $this->scopeConfig->getValue(self::API_ENDPOINT) ?? "";
    }

    public function setModules($modules, $websiteId = null)
    {
        $scope = is_null($websiteId) ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : self::WEBSITE;
        $websiteId = is_null($websiteId) ? 0 : $websiteId;
        $this->writer->save(self::MODULES, $modules, $scope, $websiteId);
        $this->cacheTypeList->cleanType('config');
    }

    public function getValueByPath($path, $websiteId)
    {
        return $this->scopeConfig->getValue($path, self::WEBSITE, $websiteId);
    }

    public function setValueByPath($path, $value, $websiteId = null)
    {
        $this->writer->save($path, $value, self::WEBSITE, $websiteId);
        $this->cacheTypeList->cleanType('config');
    }

    public function getRegisteredVersionPath($sku)
    {
        return $sku . "/registered/version";
    }

    public function getLastVerifyDatePath($sku)
    {
        return $sku . "/verify/date";
    }
}
