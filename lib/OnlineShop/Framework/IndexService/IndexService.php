<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    EcommerceFramework
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace OnlineShop\Framework\IndexService;

class IndexService {

    /**
     * @var \OnlineShop\Framework\IndexService\Worker\IWorker
     */
    protected $defaultWorker;

    /**
     * @var \OnlineShop\Framework\IndexService\Worker\IWorker[]
     */
    protected $tenantWorkers;

    public function __construct($config) {
        if(!(string)$config->disableDefaultTenant) {
            $this->defaultWorker = new \OnlineShop\Framework\IndexService\Worker\DefaultMysql(new \OnlineShop\Framework\IndexService\Config\DefaultMysql("default", $config));
        }

        $this->tenantWorkers = array();
        if($config->tenants && $config->tenants instanceof \Zend_Config) {
            foreach($config->tenants as $name => $tenant) {
                $tenantConfigClass = (string) $tenant->class;
                $cachekey = "onlineshop_config_assortment_tenant_" . str_replace('\\','_', (string) $tenantConfigClass);

                $tenantConfig = $tenant;
                if($tenant->file) {
                    if(!$tenantConfig = \Pimcore\Model\Cache::load($cachekey)) {
                        $tenantConfig = new \Zend_Config_Xml(PIMCORE_DOCUMENT_ROOT . ((string)$tenant->file), null, true);
                        $tenantConfig = $tenantConfig->tenant;
                        \Pimcore\Model\Cache::save($tenantConfig, $cachekey, array("ecommerceconfig"), 9999);
                    }
                }

                /**
                 * @var $tenantConfig \OnlineShop\Framework\IndexService\Config\IConfig
                 */
                $tenantConfig = new $tenantConfigClass($name, $tenantConfig, $config);
                $worker = $tenantConfig->getTenantWorker();
                $this->tenantWorkers[$name] = $worker;
            }
        }
    }

    /**
     * Returns a specific Tenant Worker
     *
     * @param string $name
     *
     * @return \OnlineShop\Framework\IndexService\Worker\IWorker
     */
    public function getTenantWorker($name){
        return $this->tenantWorkers[$name];
    }

    /**
     * @deprecated
     *
     * @param null $tenant
     * @return array
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getGeneralSearchColumns($tenant = null) {
        return $this->getGeneralSearchAttributes($tenant);
    }

    /**
     * returns all attributes marked as general search attributes for full text search
     *
     * @param string $tenant
     * @return array
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getGeneralSearchAttributes($tenant = null) {
        if(empty($tenant)) {
            $tenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getGeneralSearchAttributes();
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        if($this->defaultWorker) {
            return $this->defaultWorker->getGeneralSearchAttributes();
        } else {
            return array();
        }
    }

    /**
     * @deprecated
     */
    public function createOrUpdateTable() {
        $this->createOrUpdateIndexStructures();
    }

    /**
     *  creates or updates necessary index structures (like database tables and so on)
     */
    public function createOrUpdateIndexStructures() {
        if($this->defaultWorker) {
            $this->defaultWorker->createOrUpdateIndexStructures();
        }

        foreach($this->tenantWorkers as $name => $tenant) {
            $tenant->createOrUpdateIndexStructures();
        }
    }

    /**
     * deletes given element from index
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     */
    public function deleteFromIndex(\OnlineShop\Framework\Model\IIndexable $object){
        if($this->defaultWorker) {
            $this->defaultWorker->deleteFromIndex($object);
        }
        foreach($this->tenantWorkers as $name => $tenant) {
            $tenant->deleteFromIndex($object);
        }
    }

    /**
     * updates given element in index
     *
     * @param \OnlineShop\Framework\Model\IIndexable $object
     */
    public function updateIndex(\OnlineShop\Framework\Model\IIndexable $object) {
        if($this->defaultWorker) {
            $this->defaultWorker->updateIndex($object);
        }
        foreach($this->tenantWorkers as $name => $tenant) {
            $tenant->updateIndex($object);
        }
    }

    /**
     * returns all index attributes
     *
     * @param bool $considerHideInFieldList
     * @param string $tenant
     * @return array
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getIndexAttributes($considerHideInFieldList = false, $tenant = null) {
        if(empty($tenant)) {
            $tenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getIndexAttributes($considerHideInFieldList);
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        if($this->defaultWorker) {
            return $this->defaultWorker->getIndexAttributes($considerHideInFieldList);
        } else {
            return array();
        }
    }

    /**
     * @deprecated
     *
     * @param bool $considerHideInFieldList
     * @param null $tenant
     * @return mixed
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getIndexColumns($considerHideInFieldList = false, $tenant = null) {
        return $this->getIndexAttributes($considerHideInFieldList, $tenant);
    }

    /**
     * returns all filter groups
     *
     * @param string $tenant
     * @return array
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getAllFilterGroups($tenant = null) {
        if(empty($tenant)) {
            $tenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getAllFilterGroups();
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        if($this->defaultWorker) {
            return $this->defaultWorker->getAllFilterGroups();
        } else {
            return array();
        }
    }


    /**
     * retruns all index attributes for a given filter group
     *
     * @param $filterType
     * @param string $tenant
     * @return array
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getIndexAttributesByFilterGroup($filterType, $tenant = null) {
        if(empty($tenant)) {
            $tenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();
        }

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getIndexAttributesByFilterGroup($filterType);
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        }

        if($this->defaultWorker) {
            return $this->defaultWorker->getIndexAttributesByFilterGroup($filterType);
        } else {
            return array();
        }
    }

    /**
     * @deprecated
     *
     * @param $filterType
     * @param null $tenant
     * @return mixed
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getIndexColumnsByFilterGroup($filterType, $tenant = null) {
        return $this->getIndexAttributesByFilterGroup($filterType, $tenant);
    }


    /**
     * returns current tenant configuration
     *
     * @return \OnlineShop\Framework\IndexService\Config\IConfig
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getCurrentTenantConfig() {
        return $this->getCurrentTenantWorker()->getTenantConfig();
    }

    /**
     * @return \OnlineShop\Framework\IndexService\Worker\IWorker
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getCurrentTenantWorker() {
        $tenant = \OnlineShop\Framework\Factory::getInstance()->getEnvironment()->getCurrentAssortmentTenant();

        if($tenant) {
            if(array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant];
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        } else {
            return $this->defaultWorker;
        }
    }

    /**
     * @return \OnlineShop\Framework\IndexService\ProductList\IProductList
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getProductListForCurrentTenant() {
        return $this->getCurrentTenantWorker()->getProductList();
    }


    /**
     * @return \OnlineShop\Framework\IndexService\ProductList\IProductList
     * @throws \OnlineShop\Framework\Exception\InvalidConfigException
     */
    public function getProductListForTenant($tenant) {
        if($tenant) {
            if (array_key_exists($tenant, $this->tenantWorkers)) {
                return $this->tenantWorkers[$tenant]->getProductList();
            } else {
                throw new \OnlineShop\Framework\Exception\InvalidConfigException("Tenant $tenant doesn't exist.");
            }
        } else {
            return $this->defaultWorker->getProductList();
        }
    }
}