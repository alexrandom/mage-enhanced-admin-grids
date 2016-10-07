<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   BL
 * @package    BL_CustomGrid
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Model_Custom_Column_Shipment_Items_Abstract extends BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract
{
    public function addItemsToGridCollection(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $firstTime
    ) {
        if (!$firstTime && !$gridBlock->blcg_isExport()) {
            $shipmentsIds = array();
            $ordersIds = array();
            
            foreach ($collection as $shipment) {
                /** @var $shipment Mage_Sales_Model_Order_Shipment */
                $shipmentsIds[] = $shipment->getId();
                $ordersIds[] = $shipment->getOrderId();
            }
            
            /** @var $items Mage_Sales_Model_Mysql4_Order_Shipment_Item_Collection */
            $items = Mage::getResourceModel('sales/order_shipment_item_collection');
            $items->addFieldToFilter('parent_id', array('in' => $shipmentsIds));
            $items->load();
            
            $orders = $this->_getOrdersCollection($ordersIds);
            $eventName = 'blcg_custom_column_shipment_items_list_order_items_collection';
            $ordersItems = $this->_getOrdersItemsCollection($ordersIds, false, $eventName);
            $propertyName = 'sales/order_shipment::_items';
            $itemsProperty = $this->_getReflectionHelper()->getModelReflectionProperty($propertyName, true);
            
            if ($itemsProperty) {
                foreach ($collection as $shipment) {
                    $orderId = $shipment->getOrderId();
                    $shipmentId = $shipment->getId();
                    $shipmentItems = clone $items;
                    
                    if ($order = $orders->getItemById($orderId)) {
                        /** @var $order Mage_Sales_Model_Order */
                        $shipment->setOrder($order);
                    }
                    
                    foreach ($shipmentItems as $item) {
                        /** @var $item Mage_Sales_Model_Order_Shipment_Item */
                        if ($item->getParentId() != $shipmentId) {
                            $shipmentItems->removeItemByKey($item->getId());
                        } else {
                            $item->setShipment($shipment);
                            
                            if ($orderItem = $ordersItems->getItemById($item->getOrderItemId())) {
                                /** @var $orderItem Mage_Sales_Model_Order_Item */
                                $item->setOrderItem($orderItem);
                                
                                if ($order) {
                                    $orderItem->setOrder($order);
                                }
                            }
                        }
                    }
                    
                    $itemsProperty->setValue($shipment, $shipmentItems);
                }
            } else {
                foreach ($collection as $shipment) {
                    /** @var $shipment Mage_Sales_Model_Order_Shipment */
                    $shipment->setData('_blcg_items_init_error', true);
                }
                
                /** @var $session BL_CustomGrid_Model_Session */
                $session = Mage::getSingleton('customgrid/session');
                $session->addError($this->_getBaseHelper()->__('An error occurred while initializing items'));
            }
        }
        return $this;
    }
    
    protected function _getItemsTableName()
    {
        return 'sales/shipment_item';
    }
    
    protected function _getParentFkFieldName()
    {
        return 'parent_id';
    }
    
    protected function _getParentPkFieldName()
    {
        return 'entity_id';
    }
}
