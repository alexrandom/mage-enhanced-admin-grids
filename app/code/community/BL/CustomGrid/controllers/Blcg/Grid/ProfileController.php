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

class BL_CustomGrid_Blcg_Grid_ProfileController extends BL_CustomGrid_Controller_Grid_Action
{
    protected function _setActionSuccessJsonResponse(array $actions = array())
    {
        return parent::_setActionSuccessJsonResponse(array('actions' => $actions));
    }
    
    /**
     * Initialize the current grid model and profile, check the given permissions,
     * then prepare the layout for the given profile action
     * 
     * @param string $actionCode Profile action
     * @param string|array $permissions Required user permission(s)
     * @param bool $anyPermission Whether all the given permissions are required, or just one of them
     * @return BL_CustomGrid_Grid_ProfileController
     */
    protected function _prepareFormLayout($actionCode, $permissions = null, $anyPermission = true)
    {
        $handles = array('blcg_empty');
        $error = false;
        
        try {
            $gridModel = $this->_initGridModel();
            $gridProfile = $this->_initGridProfile();
            
            if (!is_null($permissions)) {
                if (!$gridModel->checkUserPermissions($permissions, null, $anyPermission)) {
                    Mage::throwException($this->__('You are not allowed to use this action'));
                }
            }
            
            $handles[] = 'adminhtml_blcg_grid_profile_form_window_action'; 
            
        } catch (Mage_Core_Exception $e) {
            $handles[] = 'adminhtml_blcg_grid_profile_form_window_error';
            $error = $e->getMessage();
        }
        
        $this->loadLayout($handles);
        
        if ($error !== false) {
            if ($errorBlock = $this->getLayout()->getBlock('blcg.grid_profile.form_error')) {
                /** @var $errorBlock Mage_Adminhtml_Block_Template */
                $errorBlock->setErrorText($error);
            }
        } elseif ($containerBlock = $this->getLayout()->getBlock('blcg.grid_profile.form_container')) {
            /** @var $containerBlock BL_CustomGrid_Block_Grid_Profile_Form_Container */
            $containerBlock->setProfileId($gridProfile->getId())->setActionCode($actionCode);
        }
        
        return $this;
    }
    
    public function goToAction()
    {
        try {
            $this->_initGridModel();
            $this->_initGridProfile(false);
            $this->_setActionSuccessJsonResponse(array(array('type' => 'reload')));
        } catch (Mage_Core_Exception $e) {
            $this->_setActionErrorJsonResponse($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_setActionErrorJsonResponse($this->__('Could not go to the specified profile'));
        }
    }
    
    public function defaultFormAction()
    {
        $this->_prepareFormLayout(
            'default',
            array(
                BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE,
            )
        );
        $this->renderLayout();
    }
    
    public function defaultAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $this->_initGridModel();
                $gridProfile = $this->_initGridProfile();
                
                $gridProfile->chooseAsDefault($data);
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully chosen as default'));
                $this->_setActionSuccessJsonResponse();
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not choose as default the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function copyToNewFormAction()
    {
        $this->_prepareFormLayout('copy_new', BL_CustomGrid_Model_Grid_Sentry::ACTION_COPY_PROFILES_TO_NEW);
        $this->renderLayout();
    }
    
    public function copyToNewAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $gridModel = $this->_initGridModel();
                $copiedProfile = $this->_initGridProfile();
                
                $newProfileId  = $copiedProfile->copyToNew($data);
                $actions = array();
                
                if ($gridModel->isAvailableProfile($newProfileId)) {
                    $actions[] = array(
                        'type'    => 'create',
                        'profile' => array(
                            'id'        => $newProfileId,
                            'name'      => trim($data['name']),
                            'isBase'    => false,
                            'isCurrent' => false,
                        ),
                    );
                }
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully copied'));
                $this->_setActionSuccessJsonResponse($actions);
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not copy the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function copyToExistingFormAction()
    {
        $this->_prepareFormLayout('copy_existing', BL_CustomGrid_Model_Grid_Sentry::ACTION_COPY_PROFILES_TO_EXISTING);
        $this->renderLayout();
    }
    
    public function copyToExistingAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $gridModel = $this->_initGridModel();
                $copiedProfile = $this->_initGridProfile();
                
                if (isset($data['to_profile_id'])) {
                    $toProfileId = (int) $data['to_profile_id'];
                    unset($data['to_profile_id']);
                } else {
                    Mage::throwException($this->__('Invalid request'));
                }
                
                $isCopyToSessionProfile = ($toProfileId === $gridModel->getSessionProfileId());
                $copiedProfile->copyToExisting($toProfileId, $data);
                $actions = array();
                
                if ($isCopyToSessionProfile) {
                    $actions[] = array('type' => 'reload');
                }
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully copied')); 
                $this->_setActionSuccessJsonResponse($actions);
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not copy the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function editFormAction()
    {
        $this->_prepareFormLayout('edit', BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_PROFILES);
        $this->renderLayout();
    }
    
    public function editAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $this->_initGridModel();
                $gridProfile = $this->_initGridProfile();
                
                $gridProfile->update($data);
                
                $actions = array(
                    array(
                        'type'        => 'rename',
                        'profileId'   => $gridProfile->getId(),
                        'profileName' => trim($data['name']),
                    )
                );
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully edited'));
                $this->_setActionSuccessJsonResponse($actions);
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not edit the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function assignFormAction()
    {
        $this->_prepareFormLayout('assign', BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES);
        $this->renderLayout();
    }
    
    public function assignAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $gridModel   = $this->_initGridModel();
                $gridProfile = $this->_initGridProfile();
                $isSessionProfile = ($gridProfile->getId() === $gridModel->getSessionProfileId());
                
                $gridProfile->assign($data);
                $actions = array();
                
                if (!$gridModel->isAvailableProfile($gridProfile->getId())) {
                    $actions[] = array(
                        'type'      => 'delete',
                        'profileId' => $gridProfile->getId()
                    );
                    
                    if ($isSessionProfile) {
                        $actions[] = array('type' => 'reload');
                    }
                }
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully assigned'));
                $this->_setActionSuccessJsonResponse($actions);
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not assign the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $gridModel   = $this->_initGridModel();
                $gridProfile = $this->_initGridProfile();
                $isSessionProfile = ($gridProfile->getId() === $gridModel->getSessionProfileId());
                
                $gridProfile->delete();
                
                $actions = array(
                    array(
                        'type'      => 'delete',
                        'profileId' => $gridProfile->getId(),
                    )
                );
                
                if ($isSessionProfile) {
                    $actions[] = array('type' => 'reload');
                }
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully deleted'));
                $this->_setActionSuccessJsonResponse($actions);
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not delete the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    protected function _isAllowed()
    {
        // Specific permissions are enforced by the models
        return true;
    }
}
