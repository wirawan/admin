<?php

/**
 * Description of IndexController
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Search_IndexController extends Zend_Controller_Action
{
    protected $_user;

    function  preDispatch()
    {
        $this->_helper->layout->setLayout('layout-search');

        $auth = Zend_Auth::getInstance();
        
        $identity = Pandamp_Application::getResource('identity');

        $sReturn = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        $sReturn = base64_encode($sReturn);

        //$sso = new Pandamp_Session_Remote();
        //$user = $sso->getInfo();

        if (!$auth->hasIdentity()) {
            //$this->_forward('login','account','admin');
			$loginUrl = $identity->loginUrl;
			
			//$this->_redirect($loginUrl.'?returnTo='.$sReturn);     
			$this->_redirect($loginUrl.'/returnUrl/'.$sReturn);
        }
        else
        {
            //$this->_user = $auth->getIdentity();
            $idt = $auth->getIdentity();
			//$this->_user = $identity['properties'];
			$this->_user = new stdClass();
			$this->_user->kopel 	= $idt['properties']['kopel'];
			$this->_user->username 	= $idt['properties']['username'];
			$this->_user->packageId = $idt['properties']['packageId'];

            $acl = Pandamp_Acl::manager();
            //if (!Pandamp_Controller_Action_Helper_IsAllowed::isAllowed('all-access','all'))
            if (!$acl->checkAcl("site",'all','user', $this->_user->username, false,false))
            {
                $zl = Zend_Registry::get("Zend_Locale");
                $this->_redirect(ROOT_URL.'/'.$zl->getLanguage().'/error/restricted');
            }
        }
    }
    function indexAction()
    {
        $solrWriteDir = Zend_Registry::get("Solr_WriteDir");
        $this->view->solrWriteDir = $solrWriteDir;
    }
    function headerAction()
    {
        $r = $this->getRequest();
        $sOffset = $r->getParam('sOffset');
        $this->view->sOffset = $sOffset;
        $sLimit = $r->getParam('sLimit');
        $this->view->sLimit = $sLimit;

        $query = ($r->getParam('q'))? $r->getParam('q') : '';
        $this->_helper->layout()->searchQuery = $query;
        $this->view->user = $this->_user;
    }
}
