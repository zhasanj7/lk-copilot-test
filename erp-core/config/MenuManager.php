<?php
/**
 * IBS-LK ERP v5.8.3 - Menu Manager
 * Standalone ERP Menu Management System
 * 
 * Core logic extracted from install.xml:
 * - Single guarded top-level admin menu
 * - Duplicate prevention with permission checks
 * - Dynamic menu item rendering
 */

class MenuManager {
    private $menus = array();
    private $user = null;
    private $urlGenerator = null;
    private $sessionToken = null;
    
    const MENU_ID = 'menu-ibs-lk';
    const MENU_NAME = 'IBS-LK';
    const MENU_ICON = 'fa-industry';
    
    /**
     * Initialize MenuManager
     * 
     * @param object $user User object with permission checking
     * @param object $urlGenerator URL generation utility
     * @param string $sessionToken Session token for secure links
     */
    public function __construct($user, $urlGenerator, $sessionToken) {
        $this->user = $user;
        $this->urlGenerator = $urlGenerator;
        $this->sessionToken = $sessionToken;
    }
    
    /**
     * Set existing menus array
     * Used to check for duplicates and clean menu array
     * 
     * @param array $menus Existing menus
     */
    public function setExistingMenus($menus = array()) {
        $this->menus = is_array($menus) ? $menus : array();
    }
    
    /**
     * Check if IBS-LK menu already exists
     * Prevents duplicate menu injection
     * 
     * @return boolean
     */
    private function menuExists() {
        if (empty($this->menus) || !is_array($this->menus)) {
            return false;
        }
        
        foreach ($this->menus as $menu) {
            $hasId = isset($menu['id']) && $menu['id'] === self::MENU_ID;
            $hasName = isset($menu['name']) && $menu['name'] === self::MENU_NAME;
            
            if ($hasId || $hasName) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check user permissions for ERP access
     * Multiple permission checks for flexibility
     * 
     * @return boolean
     */
    private function hasEVPAccess() {
        if ($this->user === null) {
            return false;
        }
        
        return $this->user->hasPermission('access', 'extension/module/ibs_lk_overview') || 
               $this->user->hasPermission('access', 'extension/module/ibs_lk');
    }
    
    /**
     * Get all ERP menu items with their routes and permissions
     * Centralized menu structure definition
     * 
     * @return array Menu items configuration
     */
    private function getMenuItems() {
        return array(
            array(
                'text' => 'All Orders',
                'route' => 'extension/module/ibs_lk_all_orders',
                'permission' => 'extension/module/ibs_lk_orders'
            ),
            array(
                'text' => 'Vendor Fulfillment',
                'route' => 'extension/module/ibs_lk_orders',
                'permission' => 'extension/module/ibs_lk_orders'
            ),
            array(
                'text' => 'Dispatch Batches',
                'route' => 'extension/module/ibs_lk_dispatch_batches',
                'permission' => 'extension/module/ibs_lk_dispatch_batches'
            ),
            array(
                'text' => 'Vendor Returns',
                'route' => 'extension/module/ibs_lk_supplier_return',
                'permission' => 'extension/module/ibs_lk_supplier_return'
            ),
            array(
                'text' => 'Owner Return',
                'route' => 'extension/module/ibs_lk_owner_return',
                'permission' => 'extension/module/ibs_lk_owner_return'
            ),
            array(
                'text' => 'Product Control',
                'route' => 'extension/module/ibs_lk_product_cost_stock',
                'permission' => 'extension/module/ibs_lk_product_cost_stock'
            ),
            array(
                'text' => 'Payable & Settlement',
                'route' => 'extension/module/ibs_lk_payable',
                'permission' => 'extension/module/ibs_lk_payable'
            ),
            array(
                'text' => 'Manual Order',
                'route' => 'extension/module/ibs_lk_manual_order',
                'permission' => 'extension/module/ibs_lk_manual_order'
            ),
            array(
                'text' => 'Supplier Account / Ledger',
                'route' => 'extension/module/ibs_lk_supplier_account',
                'permission' => 'extension/module/ibs_lk_supplier_account'
            ),
            array(
                'text' => 'Reports',
                'route' => 'extension/module/ibs_lk_reports',
                'permission' => 'extension/module/ibs_lk_reports'
            ),
            array(
                'text' => 'Overview',
                'route' => 'extension/module/ibs_lk_overview',
                'permission' => 'extension/module/ibs_lk_overview'
            ),
            array(
                'text' => 'Settings / Configuration',
                'route' => 'extension/module/ibs_lk_settings',
                'permission' => 'extension/module/ibs_lk_settings'
            )
        );
    }
    
    /**
     * Build child menu items with permission-based filtering
     * Only visible menu items user has access to are rendered
     * 
     * @return array Child menu items
     */
    private function buildChildMenus() {
        $children = array();
        $items = $this->getMenuItems();
        
        foreach ($items as $item) {
            $permissionRoute = isset($item['permission']) ? $item['permission'] : $item['route'];
            
            // Check if user has permission to this menu item
            if ($this->user->hasPermission('access', $permissionRoute) || 
                $this->user->hasPermission('access', 'extension/module/ibs_lk')) {
                
                $children[] = array(
                    'name' => $item['text'],
                    'href' => $this->urlGenerator->link(
                        $item['route'], 
                        'user_token=' . $this->sessionToken, 
                        true
                    ),
                    'children' => array()
                );
            }
        }
        
        return $children;
    }
    
    /**
     * Create IBS-LK main menu with child items
     * 
     * @return array Menu structure
     */
    public function createMenu() {
        return array(
            'id' => self::MENU_ID,
            'icon' => self::MENU_ICON,
            'name' => self::MENU_NAME,
            'href' => '',
            'children' => $this->buildChildMenus()
        );
    }
    
    /**
     * Inject ERP menu into existing menus
     * Checks for duplicates before injection
     * 
     * @return array Updated menus array
     */
    public function injectMenu() {
        // Check if menu already exists to prevent duplicates
        if ($this->menuExists()) {
            return $this->menus;
        }
        
        // Check if user has ERP access
        if (!$this->hasEVPAccess()) {
            return $this->menus;
        }
        
        // Create and add the menu
        $this->menus[] = $this->createMenu();
        return $this->menus;
    }
    
    /**
     * Clean duplicate menus from array
     * Safety cleanup for old OCMOD builds that may have injected duplicates
     * 
     * @return array Cleaned menus array
     */
    public function cleanDuplicateMenus() {
        if (empty($this->menus) || !is_array($this->menus)) {
            return $this->menus;
        }
        
        $cleanMenus = array();
        $seenMenu = false;
        
        foreach ($this->menus as $menu) {
            $isIbsLkMenu = (isset($menu['id']) && $menu['id'] === self::MENU_ID) || 
                           (isset($menu['name']) && $menu['name'] === self::MENU_NAME);
            
            if ($isIbsLkMenu) {
                // If we've already seen this menu, skip it (remove duplicate)
                if ($seenMenu) {
                    continue;
                }
                
                $seenMenu = true;
                $menu['id'] = self::MENU_ID;
                $menu['name'] = self::MENU_NAME;
            }
            
            $cleanMenus[] = $menu;
        }
        
        $this->menus = $cleanMenus;
        return $this->menus;
    }
    
    /**
     * Get final processed menus
     * 
     * @return array
     */
    public function getMenus() {
        return $this->menus;
    }
}
