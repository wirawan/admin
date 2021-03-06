<?php
/**
 * @author	2011-2012 Nihki Prihadi <nihki@madaniyah.com>
 * @version $Id: url.php 1 2012-02-14 13:07Z $
 * @modifiedDate           2016-05-18 12:51Z $
 */

class App_Model_Db_Table_Url extends Zend_Db_Table_Abstract
{
	protected $_name = 'shorturls';
	
    protected function  _setupDatabaseAdapter()
    {
        $this->_db = Zend_Registry::get('db4');

        parent::_setupDatabaseAdapter();
    }
    
   	public function countUrl($uri)
   	{
    	$db = $this->_db->query("select count(*) as count from clicks as a, shorturls as b where a.urlid = b.id and b.url = '".$uri."'");
    	
    	$dataFetch = $db->fetch();
   		return $dataFetch['count'];
   	}
}