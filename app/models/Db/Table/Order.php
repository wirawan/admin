<?php

/**
 * Description of Order
 *
 * @author nihki <nihki@madaniyah.com>
 */

class App_Model_Db_Table_Order extends Zend_Db_Table_Abstract
{
    protected $_name = 'KutuOrder';
    protected $_rowClass = 'App_Model_Db_Table_Row_Order';
    protected $_referenceMap = array(
        'User' => array(
            'columns'       => array('userId'),
            'refTableClass' => array('App_Model_Db_Table_User'),
            'refColumns'    => array('kopel')
        )
    );
    protected function  _setupDatabaseAdapter()
    {
        $zl = Zend_Registry::get('Zend_Locale');
        if ($zl->getLanguage() == 'id')
            $this->_db = Zend_Registry::get('db1');
        else
            $this->_db = Zend_Registry::get('db3');

        parent::_setupDatabaseAdapter();
    }
}
