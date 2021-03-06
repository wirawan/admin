<?php

/**
 * Description of UserStatus
 *
 * @author nihki <nihki@madaniyah.com>
 */
class App_Model_Show_UserStatus extends App_Model_Db_DefaultAdapter
{
    /**
     * class instance object
     */
    private static $_instance;
    
    private static $_db;

    /**
     * de-activate constructor
     */
    final private function  __construct() 
    {
    	self::$_db = Zend_Registry::get('db2');
    }

     /**
      * de-activate object cloning
      */
    final private function  __clone() {}

    /**
     * @return obj
     */
    public function show()
    {
        if (!isset(self::$_instance)) {
                $show = __CLASS__;
                self::$_instance = new $show;
        }
        return self::$_instance;
    }

    public function getUserStatus($statusId)
    {
        $db = parent::_dbSelect();
        $select = $db->from('KutuUserStatus',array("accountStatusId","status"))
                    ->where("accountStatusId=?",$statusId);
                    
		$conn = self::$_db;                    

        $result = $conn->fetchRow($select);

        return $result;
    }
}
