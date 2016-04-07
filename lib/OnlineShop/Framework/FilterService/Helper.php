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


namespace OnlineShop\Framework\FilterService;

/**
 * Class \OnlineShop\Framework\FilterService\Helper
 *
 * Helper Class for setting up a product list utilizing the filter service
 * based on a filter definition and set filter parameters
 */
class Helper
{
    /**
     * @param \Pimcore\Model\Object\FilterDefinition $filterDefinition
     * @param \OnlineShop\Framework\IndexService\ProductList\IProductList $productList
     * @param $params
     * @param \Zend_View $view
     * @param FilterService $filterService
     * @param $loadFullPage
     * @param bool $excludeLimitOfFirstpage
     */
    public static function setupProductList(\Pimcore\Model\Object\FilterDefinition $filterDefinition,
                                            \OnlineShop\Framework\IndexService\ProductList\IProductList $productList,
                                            $params, \Zend_View $view,
                                            FilterService $filterService,
                                            $loadFullPage, $excludeLimitOfFirstpage = false) {

        $orderByOptions = array();
        $orderKeysAsc = explode(",", $filterDefinition->getOrderByAsc());
        if(!empty($orderKeysAsc)) {
            foreach($orderKeysAsc as $orderByEntry) {
                if(!empty($orderByEntry)) {
                    $orderByOptions[$orderByEntry]["asc"] = true;
                }
            }
        }

        $orderKeysDesc = explode(",", $filterDefinition->getOrderByDesc());
        if(!empty($orderKeysDesc)) {
            foreach($orderKeysDesc as $orderByEntry) {
                if(!empty($orderByEntry)) {
                    $orderByOptions[$orderByEntry]["desc"] = true;
                }
            }
        }


        $offset = 0;

        $pageLimit = intval($params["perPage"]);
        if (!$pageLimit) {
            $pageLimit = $filterDefinition->getPageLimit();
        }
        if(!$pageLimit) {
            $pageLimit = 50;
        }
        $limitOnFirstLoad = $filterDefinition->getLimitOnFirstLoad();
        if(!$limitOnFirstLoad) {
            $limitOnFirstLoad = 6;
        }

        if($params["page"]) {
            $view->currentPage = intval($params["page"]);
            $offset = $pageLimit * ($params["page"]-1);
        }
        if($filterDefinition->getAjaxReload()) {
            if($loadFullPage && !$excludeLimitOfFirstpage) {
                $productList->setLimit($pageLimit);
            } else if($loadFullPage && $excludeLimitOfFirstpage) {
                $offset += $limitOnFirstLoad;
                $productList->setLimit($pageLimit - $limitOnFirstLoad);
            } else {
                $productList->setLimit($limitOnFirstLoad);
            }
        } else {
            $productList->setLimit($pageLimit);
        }
        $productList->setOffset($offset);

        $view->pageLimit = $pageLimit;



        $orderBy = $params["orderBy"];
        $orderBy = explode("#", $orderBy);
        $orderByField = $orderBy[0];
        $orderByDirection = $orderBy[1];

        if(array_key_exists($orderByField, $orderByOptions)) {
            $view->currentOrderBy = htmlentities($params["orderBy"]);

            $productList->setOrderKey($orderByField);
            $productList->setOrder($orderByDirection);
        } else {
            $orderByCollection = $filterDefinition->getDefaultOrderBy();
            $orderByList = array();
            if($orderByCollection) {
                foreach($orderByCollection as $orderBy) {
                    if($orderBy->getField()) {
                        $orderByList[] = array($orderBy->getField(), $orderBy->getDirection());
                    }
                }
                
                $view->currentOrderBy = implode("#", reset($orderByList));
            }
            $productList->setOrderKey($orderByList);
            $productList->setOrder("ASC");
        }

        if($filterService) {
            $view->currentFilter = $filterService->initFilterService($filterDefinition, $productList, $params);
        }


        $view->orderByOptions = $orderByOptions;

    }

    /**
     * @param $page
     * @return string
     */
    public static function createPagingQuerystring($page) {
        $params = $_REQUEST;
        $params['page'] = $page;
        unset($params['fullpage']);

        $string = "?";
        foreach($params as $k => $p) {
            if(is_array($p)) {
                foreach($p as $subKey => $subValue) {
                    $string .= $k . "[" . $subKey . "]" . "=" . urlencode($subValue) . "&";
                }
            } else {
                $string .= $k . "=" . urlencode($p) . "&";
            }
        }
        return $string;
    }


    /**
     * @param $conditions
     * @return \OnlineShop\Framework\Model\AbstractCategory
     */
    public static function getFirstFilteredCategory($conditions) {
        if(!empty($conditions)) {
            foreach($conditions as $c) {
                if($c instanceof \Pimcore\Model\Object\Fieldcollection\Data\FilterCategory) {
                    return $c->getPreSelect();
                }
            }
        }
    }

}
