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

class BL_CustomGrid_Model_Reflection_Accessible_Sales_Order_Creditmemo extends Mage_Sales_Model_Order_Creditmemo
{
    /**
     * Return the items value from the given creditmemo
     * 
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo Creditmemo from which to get items
     */
    public function blcgGetItemsValue(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        return $creditmemo->_items;
    }
    
    /**
     * Set the given items value on the given creditmemo
     * 
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo Creditmemo on which to set items
     * @param mixed $value Items value
     */
    public function blcgSetItemsValue(Mage_Sales_Model_Order_Creditmemo $creditmemo, $value)
    {
        $creditmemo->_items = $value;
    }
}
