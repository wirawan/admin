<?php

/**
 * Description of CatalogController
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Dms_CatalogController extends Zend_Controller_Action
{
    protected $_user;
    protected $_lang;

    function  preDispatch()
    {
        $this->_helper->layout->setLayout('layout-dms-catalog');

        $auth = Zend_Auth::getInstance();

		$identity = Pandamp_Application::getResource('identity');

		$loginUrl = $identity->loginUrl;
		
		/*
		$multidb = Pandamp_Application::getResource('multidb');
		$multidb->init();
		
		$db = $multidb->getDb('db2');
		*/
		
        $sReturn = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        $sReturn = base64_encode($sReturn);

        //$sso = new Pandamp_Session_Remote();
        //$user = $sso->getInfo();

        if (!$auth->hasIdentity()) {
            //$this->_forward('login','account','admin');
			
			$this->_redirect($loginUrl.'?returnUrl='.$sReturn); 
        }
        else
        {
            $this->_user = $auth->getIdentity();

			$zl = Zend_Registry::get("Zend_Locale");
			$this->_lang = $zl;
			
            $acl = Pandamp_Acl::manager();
            if (!$acl->checkAcl("site",'all','user', $this->_user->username, false,false))
            {
                //$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/error/restricted');
                $this->_forward('restricted','error','admin',array('lang'=>$this->_lang->getLanguage()));
            }
            
			// [TODO] else: check if user has access to admin page and status website is online
			$tblSetting = new App_Model_Db_Table_Setting();
			$rowset = $tblSetting->find(1)->current();
			
			if ($rowset)
			{
				if (($rowset->status == 1 && $this->_lang->getLanguage() == 'id') || ($rowset->status == 2 && $this->_lang->getLanguage() == 'en') || ($rowset->status == 3))
				{
					// it means that user offline other than admin
					$aReturn = App_Model_Show_AroGroup::show()->getUserGroup($this->_user->packageId);
					
					if (isset($aReturn['name']))
					{
						//if (($aReturn[1] !== "admin"))
						if (($aReturn['name'] !== "Master") && ($aReturn['name'] !== "Super Admin"))
						{
							$this->_forward('temporary','error','admin'); 
						}
					}
				}
			}
			
			// check session expire
			/*
			$timeLeftTillSessionExpires = $_SESSION['__ZF']['Zend_Auth']['ENT'] - time();

			if (Pandamp_Lib_Formater::diff('now', $this->_user->dtime) > $timeLeftTillSessionExpires) {
				$db->update('KutuUser',array('ses'=>'*'),"ses='".Zend_Session::getId()."'");
				$flashMessenger = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');
		        $flashMessenger->addMessage('Session Expired');
		        $auth->clearIdentity();
		        
		        $this->_redirect($loginUrl.'?returnUrl='.$sReturn);     
			}
			
			$dat = Pandamp_Lib_Formater::now();
			$db->update('KutuUser',array('dtime'=>$dat),"ses='".Zend_Session::getId()."'");
			*/
        }
    }
    function rightdownAction()
    {
        $r = $this->getRequest();
        $catalogGuid = $r->getParam('guid');
        $this->view->catalogGuid = $catalogGuid;

        $node = ($r->getParam('node')?$r->getParam('node'):'root');

        $this->view->currentNode = $node;
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
    
    public function detailAction()
    {
    	$this->_helper->layout->setLayout('new/layout-pusatdata');
    	
    	$request = $this->getRequest();
    	
    	$node = $request->getParam('node','root');
    	$catalogGuid = $request->getParam('guid');
    	
    	$modDir = $this->getFrontController()->getModuleDirectory();
    	require_once($modDir.'/components/Menu/FolderBreadcrumbs2.php');
    	$w = new Dms_Menu_FolderBreadcrumbs2($node);
    	$this->view->assign('breadcrumbs', $w);
    	
        $modDir = $this->getFrontController()->getModuleDirectory("dms");
        require_once($modDir.'/components/Catalog/Detail.php');
        $w = new Dms_Catalog_Detail($catalogGuid, $node);
        $this->view->assign('widget1', $w);
        
    	$modelCatalog = App_Model_Show_Catalog::show()->getCatalogByGuid($catalogGuid);
    	$this->view->assign('rowCatalog', $modelCatalog);
    	
    	$this->view->assign('guid', $catalogGuid);
    	$this->view->assign('currentNode', $node);
    }
    
    function detailOldAction()
    {
        $r = $this->getRequest();
        $catalogGuid = $r->getParam('guid');
        $this->view->catalogGuid = $catalogGuid;

        $node = ($r->getParam('node')?$r->getParam('node'):'root');
        $this->view->currentNode = $node;
        
        $modDir = $this->getFrontController()->getModuleDirectory("dms");
        require_once($modDir.'/components/Menu/FolderBreadcrumbs.php');
        $w = new Dms_Menu_FolderBreadcrumbs($node);
        $this->view->widget2 = $w;

        $title = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($catalogGuid,'fixedTitle');
        $this->view->catalogTitle = $title;
        $subTitle = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($catalogGuid,'fixedSubTitle');
        $this->view->catalogSubTitle = $subTitle;

        $modDir = $this->getFrontController()->getModuleDirectory("dms");
        require_once($modDir.'/components/Catalog/Detail.php');
        $w = new Dms_Catalog_Detail($catalogGuid, $node);
        $this->view->widget1 = $w;

        require_once($modDir.'/components/Relation/OtherViewer.php');
        $w3 = new Dms_Relation_OtherViewer($catalogGuid, $node);
        $this->view->widget3 = $w3;

        require_once($modDir.'/components/Relation/FolderViewer.php');
        $w4 = new Dms_Relation_FolderViewer($catalogGuid, $node);
        $this->view->widget4 = $w4;

        require_once($modDir.'/components/Relation/History.php');
        $w5 = new Dms_Relation_History($catalogGuid, $node);
        $this->view->widgetHistory = $w5;

        require_once($modDir.'/components/Relation/Historynew.php');
        $w9 = new Dms_Relation_Historynew($catalogGuid, $node);
        $this->view->widgetHistorynew = $w9;
        
        if ($r->getParam('deb')==1) {
	        require_once($modDir.'/components/Relation/History2.php');
	        $h2 = new Dms_Relation_History2($catalogGuid);
	        $this->view->widgetHistory2 = $h2;
        }
        
        require_once($modDir.'/components/Relation/LegalBasis.php');
        $w6 = new Dms_Relation_LegalBasis($catalogGuid, $node);
        $this->view->widgetLegalBasis = $w6;

        require_once($modDir.'/components/Relation/Regulation.php');
        $w7 = new Dms_Relation_Regulation($catalogGuid, $node);
        $this->view->widgetImplementingRegulation = $w7;

        require_once($modDir.'/components/Relation/Iregulation.php');
        $w10 = new Dms_Relation_Iregulation($catalogGuid, $node);
        $this->view->widgetIregulation = $w10;

        require_once($modDir.'/components/Relation/Document.php');
        $w8 = new Dms_Relation_Document($catalogGuid, $node);
        $this->view->widgetFiles = $w8;

//        require_once($modDir.'/components/Relation/Dci.php');
//        $w9 = new Dms_Relation_Dci($catalogGuid, $node);
//        $this->view->widgetImageFiles = $w9;

        $modelCatalog = App_Model_Show_Catalog::show()->getCatalogByGuid($catalogGuid);
        $this->view->rowCatalog = $modelCatalog;

        $this->_helper->layout()->headerTitle = "Catalog Management: Details";
    }
    function renderFolderAction()
    {
    	$this->_helper->layout->disableLayout();
    	
        $r = $this->getRequest();
        
        $catalogGuid = $r->getParam('guid');
        $node = ($r->getParam('node')?$r->getParam('node'):'root');
        
        $modDir = $this->getFrontController()->getModuleDirectory("dms");
        require_once($modDir.'/components/Relation/FolderViewer.php');
        $w4 = new Dms_Relation_FolderViewer($catalogGuid, $node);
        $this->view->widget4 = $w4;
    }
    function galleryAction()
    {
        $this->_helper->layout->disableLayout();

        $r = $this->getRequest();

        $catalogGuid = $r->getParam("guid");
        $node = $r->getParam("node");
        $page = ($r->getParam("page"))? $r->getParam("page") : 1;

        $limit = 15;

        $offset = $page;

        $modDir = $this->getFrontController()->getModuleDirectory("dms");
        require_once($modDir.'/components/Relation/Dci.php');
        $w9 = new Dms_Relation_Dci($catalogGuid, $node, $limit, $offset);
        $this->view->widgetImageFiles = $w9;
    }
    function editdocumentAction()
    {
    	$this->_helper->layout->setLayout('layout-dms-catalog-document');
    	
    	$r = $this->getRequest();
    	
    	$catalogGuid = explode(',',$r->getParam('guid'));
    	$this->view->catalogGuid = $catalogGuid;
    	
    	$relatedGuid = $r->getParam('relatedGuid');
    	$this->view->relatedGuid = $relatedGuid;
    	
    	$num_rows = count($catalogGuid);
    	$this->view->numberOfRows = $num_rows;
    }
    
    /**
     * Edit document
     *
     * @return void
     */
    public function editdocAction()
    {
    	$this->_helper->getHelper('layout')->disableLayout();
    
    	$request = $this->getRequest();
    
    	$itemGuid = explode(',',$request->getParam('itemGuid'));
    
    	$relatedGuid = $request->getParam('relatedGuid');
    	$folderGuid = $request->getParam('folderGuid');
    
    	$this->view->assign('catalogGuid', $itemGuid);
    	$this->view->assign('relatedGuid', $relatedGuid);
    	$this->view->assign('folderGuid', $folderGuid);
    
    	$num_rows = count($itemGuid);
    	$this->view->assign('numberOfRows', $num_rows);
    
    }
    
    function recycleAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
        $r = $this->getRequest();
    	
        $catalogGuid = explode(',',$r->getParam('guid'));
        
        $hol = new Pandamp_Core_Hol_Catalog();

        if (is_array($catalogGuid))
        {
            foreach($catalogGuid as $guid)
            {
                try
                {
                    $hol->recycle($guid);
                }
                catch(Exception $e)
                {
                    throw new Zend_Exception($e->getMessage());
                }
            }

        }
        else
        {
            try
            {
                $hol->recycle($catalogGuid);
            }
            catch(Exception $e)
            {
                throw new Zend_Exception($e->getMessage());
            }
        }
    }
    function recyclebinAction()
    {
    	$tblCatalog = new App_Model_Db_Table_Catalog();
    	$rowset = $tblCatalog->fetchAll("status=-2");
    	
    	$this->view->rowset = $rowset;
    }
    function restoreAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
        $r = $this->getRequest();
    	
        $catalogGuid = explode(',',$r->getParam('guid'));
        $status = $r->getParam('status');
        
        $hol = new Pandamp_Core_Hol_Catalog();

        if (is_array($catalogGuid))
        {
            foreach($catalogGuid as $guid)
            {
                try
                {
                    $hol->restore($guid, $status);
                }
                catch(Exception $e)
                {
                    throw new Zend_Exception($e->getMessage());
                }
            }

        }
        else
        {
            try
            {
                $hol->restore($catalogGuid, $status);
            }
            catch(Exception $e)
            {
                throw new Zend_Exception($e->getMessage());
            }
        }
    }
    function deleteAction()
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(TRUE);
        
        $r = $this->getRequest();

        //$catalogGuid = $r->getParam('guid');
        $catalogGuid = explode(',',$r->getParam('guid'));
        
        $hol = new Pandamp_Core_Hol_Catalog();

        if (is_array($catalogGuid))
        {
            foreach($catalogGuid as $guid)
            {
                try
                {
                    $hol->delete($guid);
                }
                catch(Exception $e)
                {
                    throw new Zend_Exception($e->getMessage());
                }
            }

        }
        else
        {
            try
            {
                $hol->delete($catalogGuid);
            }
            catch(Exception $e)
            {
                throw new Zend_Exception($e->getMessage());
            }
        }
    }
    function deleteConfirmAction()
    {
        $r = $this->getRequest();

        $catalogGuid = explode(',',$r->getParam('guid'));
        $this->view->catalogGuid = $catalogGuid;

        $this->_helper->layout()->headerTitle = "Catalog Management: Delete";
    }
    function moveFolderAction()
    {
        $urlReferer = $_SERVER['HTTP_REFERER'];
        $r = $this->getRequest();

        $guid = explode(',',$r->getParam('guid'));

        if(is_array($guid))
        {
            $sGuid = '';
            $sTitle = '';
            for($i=0;$i<count($guid);$i++)
            {
                $sGuid .= $guid[$i].';';
                $modelCatalog = App_Model_Show_Catalog::show()->getCatalogByGuid($guid[$i]);
                if ($modelCatalog['profileGuid'] == "klinik")
                    $sTitle .= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue( $guid[$i], "fixedCommentTitle").', ';
                else
                    $sTitle .= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue( $guid[$i], "fixedTitle").', ';
            }
            $guid = $sGuid;
        }
        else
        {
            $sTitle = '';
            if(!empty($guid))
            {
                $modelCatalog = App_Model_Show_Catalog::show()->getCatalogByGuid($guid);
                if ($modelCatalog['profileGuid'] == "klinik")
                    $sTitle .= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue( $guid, "fixedCommentTitle").', ';
                else
                    $sTitle .= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue( $guid, "fixedTitle");
            }
        }

        $this->view->catalogTitle = $sTitle;
        $this->view->guid = $guid;

        $sourceNode = $r->getParam('sourceNode');
        $this->view->sourceNode = $sourceNode;

        $this->_helper->layout()->headerTitle = "Catalog Management: Move to Folder";

        if($r->isPost())
        {
            $sessHistory = new Zend_Session_Namespace('BROWSER_HISTORY');
            $urlReferer = $sessHistory->urlReferer;
            
            $guid = explode(',',$r->getParam('guid'));

            $req = $this->getRequest();
            $targetNode = $req->getParam('targetNode');
            $tblCatalog = new App_Model_Db_Table_Catalog();
            
            $queue = Zend_Registry::get(Bootstrap::NAME_ORDERQUEUE);

            if(is_array($guid))
            {
                foreach($guid as $tmpGuid)
                {
                    $rowset = $tblCatalog->find($tmpGuid);
                    if(count($rowset))
                    {
                        $row = $rowset->current();
                        $row->moveToFolder($sourceNode, $targetNode);
                        
                        Zend_Registry::get('Zend_Log')->info('move multiple article:'.$tmpGuid.' to folderGuid:'.$targetNode.' dari ip:'.Pandamp_Lib_Formater::getHttpRealIp().' oleh:'.$this->_user->kopel);
                        $queue->addJob('Pandamp_Job_Catalog',
                        		['guid' => $tmpGuid,'folderGuid' => $targetNode, 'ip' => Pandamp_Lib_Formater::getHttpRealIp(), 'kopel' => $this->_user->kopel, 'lang' => $this->view->getLanguage()],
                        	false);
                    }
                }
            }
            else
            {
            	$g = $r->getParam('guid');
                $rowset = $tblCatalog->find($g);
                if(count($rowset))
                {
                    $row = $rowset->current();
                    $row->moveToFolder($sourceNode, $targetNode);
                    
                    Zend_Registry::get('Zend_Log')->info('move article:'.$g.' to folderGuid:'.$targetNode.' dari ip:'.Pandamp_Lib_Formater::getHttpRealIp().' oleh:'.$this->_user->kopel);
                    $queue->addJob('Pandamp_Job_Catalog',
                    		['guid' => $g,'folderGuid' => $targetNode, 'ip' => Pandamp_Lib_Formater::getHttpRealIp(), 'kopel' => $this->_user->kopel, 'lang' => $this->view->getLanguage()],
                    	false);
                }
            }

            $tblSetting = new App_Model_Db_Table_Setting();
			$rowSetting = $tblSetting->find(1)->current();
			$un = unserialize($rowSetting->dataCache);
			if($un!="")
			{
				if (!in_array($sourceNode, $un))
				{
					$un[] = $sourceNode;
				}
				$un = serialize($un);
			}
			else
			{
				$un = serialize([$sourceNode]);
			}
			
			$rowSetting->dataCache = $un;
			$rowSetting->save();
        }

        $sessHistory = new Zend_Session_Namespace('BROWSER_HISTORY');
        $sessHistory->urlReferer = $urlReferer;
        $this->view->urlReferer = $sessHistory->urlReferer;
    }
    function copyFolderAction()
    {
        $urlReferer = $_SERVER['HTTP_REFERER'];
        $r = $this->getRequest();

        $guid = explode(',',$r->getParam('guid'));

        if(is_array($guid))
        {
            $sGuid = '';
            $sTitle = '';
            for($i=0;$i<count($guid);$i++)
            {
                $sGuid .= $guid[$i].';';
                $modelCatalog = App_Model_Show_Catalog::show()->getCatalogByGuid($guid[$i]);
                if ($modelCatalog['profileGuid'] == "klinik")
                    $sTitle .= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue( $guid[$i], "fixedCommentTitle").', ';
                else
                    $sTitle .= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue( $guid[$i], "fixedTitle").', ';
            }
            $guid = $sGuid;
        }
        else
        {
            $sTitle = '';
            if(!empty($guid))
            {
                $modelCatalog = App_Model_Show_Catalog::show()->getCatalogByGuid($guid);
                if ($modelCatalog['profileGuid'] == "klinik")
                    $sTitle .= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue( $guid, "fixedCommentTitle").', ';
                else
                    $sTitle .= App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue( $guid, "fixedTitle");
            }
        }

        $this->view->catalogTitle = $sTitle;
        $this->view->guid = $guid;

        $this->_helper->layout()->headerTitle = "Catalog Management: Copy to Folder";

        if($r->isPost())
        {
            $sessHistory = new Zend_Session_Namespace('BROWSER_HISTORY');
            $urlReferer = $sessHistory->urlReferer;

            $req = $this->getRequest();
            $targetNode = $req->getParam('targetNode');
            
            $queue = Zend_Registry::get(Bootstrap::NAME_ORDERQUEUE);

            $tblCatalog = new App_Model_Db_Table_Catalog();

            if(is_array($r->getParam('guid')))
            {
                foreach($r->getParam('guid') as $tmpGuid)
                {
                    $rowset = $tblCatalog->find($tmpGuid);
                    if(count($rowset))
                    {
                        $row = $rowset->current();
                        $row->copyToFolder($targetNode);
                        
                        Zend_Registry::get('Zend_Log')->info('copy multiple article:'.$tmpGuid.' to folderGuid:'.$targetNode.' dari ip:'.Pandamp_Lib_Formater::getHttpRealIp().' oleh:'.$this->_user->kopel);
                        $queue->addJob('Pandamp_Job_Catalog',
                        		['guid' => $tmpGuid,'folderGuid' => $targetNode, 'ip' => Pandamp_Lib_Formater::getHttpRealIp(), 'kopel' => $this->_user->kopel, 'lang' => $this->view->getLanguage()],
                        	false);
                    }
                }
            }
            else
            {
            	$g = $r->getParam('guid');
                $rowset = $tblCatalog->find($g);
                if(count($rowset))
                {
                    $row = $rowset->current();
                    $row->copyToFolder($targetNode);
                    
                    Zend_Registry::get('Zend_Log')->info('copy article:'.$g.' to folderGuid:'.$targetNode.' dari ip:'.Pandamp_Lib_Formater::getHttpRealIp().' oleh:'.$this->_user->kopel);
                    $queue->addJob('Pandamp_Job_Catalog',
                    		['guid' => $g,'folderGuid' => $targetNode, 'ip' => Pandamp_Lib_Formater::getHttpRealIp(), 'kopel' => $this->_user->kopel, 'lang' => $this->view->getLanguage()],
                    	false);
                }
            }
            $this->view->message = "Data was successfully saved.";
        }

        $sessHistory = new Zend_Session_Namespace('BROWSER_HISTORY');
        $sessHistory->urlReferer = $urlReferer;
        $this->view->urlReferer = $sessHistory->urlReferer;

    }
    
    function newAction()
    {
    	$this->_helper->layout->setLayout('layout-dms-newcatalog');
    	
        $r = $this->getRequest();
        $folderGuid = $r->getParam('node');
        $profileGuid = $r->getParam('profile');

		$modDir = $this->getFrontController()->getModuleDirectory();
		require_once($modDir.'/components/Menu/FolderBreadcrumbs2.php');
		$w = new Dms_Menu_FolderBreadcrumbs2($folderGuid);
		$this->view->assign('breadcrumbs', $w);
		
        $this->view->profile = $profileGuid;
        
        $this->view->guid = (new Pandamp_Core_Guid())->generateGuid();

        $generatorForm = new Pandamp_Form_Helper_CatalogInputGenerator();
        $aRender = $generatorForm->generateFormAdd(strtolower($profileGuid), $folderGuid);

        $this->view->aRenderedAttributes = $aRender;

        $this->view->currentNode = $folderGuid;
        $this->_helper->layout()->headerTitle = "Catalog Management: Add New Catalog";

        $message = "";
        if($r->isPost())
        {
	        $aData = $r->getPost();
			
	        $aData['username'] = $this->_user->username;
	
	        $Bpm = new Pandamp_Core_Hol_Catalog();
	        $id = $Bpm->save($aData);
	        
            if ($id) {
            	$this->_helper->getHelper('FlashMessenger')
            		->addMessage('The article has been added successfully.');
            	
            	$queue = Zend_Registry::get(Bootstrap::NAME_ORDERQUEUE);
            	$queue->addJob('Pandamp_Job_Catalog',
            			['guid' => $id,'folderGuid' => $folderGuid, 'ip' => Pandamp_Lib_Formater::getHttpRealIp(), 'kopel' => $this->_user->kopel, 'lang' => $this->view->getLanguage()],
            			false);

            	$this->_helper->json([
            			'response' => true,
            			'message' => 'Artikel berhasil disimpan. <a href="'.ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/explorer/browse/node/'.$folderGuid.'">Lihat artikel</a>.'
            		]);
					
					
				/*if (!empty($aData['fixedKeywords']))
				{
					if (in_array($profileGuid,array('article','clinic'))) {
					$keywords = base64_encode(trim($aData['fixedKeywords']));
					$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/catalog/relatedcatalog/guid/'.$id.'/profile/'.$profileGuid.'/keywords/'.$keywords.'/node/'.$folderGuid);
					}
				}
				else 
				{
					$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/explorer/browse/node/'.$folderGuid);
				}*/
            }
            
        }
    }
    
    function editAction()
    {
    	$this->_helper->layout->setLayout('layout-dms-newcatalog');
    	
        $r = $this->getRequest();
        $catalogGuid = ($this->_getParam('guid'))? $this->_getParam('guid') : '';

        $sessHistory = new Zend_Session_Namespace('BROWSER_HISTORY');
        if (isset($sessHistory->currentNode)) 
        	unset($sessHistory->currentNode);
        	
        $sessHistory->currentNode = ($this->_getParam('node'))? $this->_getParam('node') : $sessHistory->currentNode;
        $this->view->currentNode = $sessHistory->currentNode;

        $urlReferer = (isset($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : '';

        $message = "";

        $modDir = $this->getFrontController()->getModuleDirectory();
        require_once($modDir.'/components/Menu/FolderBreadcrumbs2.php');
        $w = new Dms_Menu_FolderBreadcrumbs2($sessHistory->currentNode);
        $this->view->assign('breadcrumbs', $w);
        
        $modelCatalog = App_Model_Show_Catalog::show()->getCatalogByGuid($catalogGuid);
        
        $this->view->assign('catalog', $modelCatalog);
        $this->view->assign('catalogGuid', $catalogGuid);
        
        $this->view->profile = $modelCatalog['profileGuid'];
        
        if ($modelCatalog['profileGuid'] == "klinik") {
            $this->_forward('answer.clinic','clinic','dms',array('guid'=>$catalogGuid,'node'=>$sessHistory->currentNode));
        }
        else
        {
            $gen = new Pandamp_Form_Helper_CatalogInputGenerator();
            $aRender = $gen->generateFormEdit($catalogGuid);
            $this->view->aRenderedAttributes = $aRender;
            
            /*$catalogFolderDb = new App_Model_Db_Table_CatalogFolder();
            $rowCategory = $catalogFolderDb->fetchAll("catalogGuid='$catalogGuid'");
            $categories = array();
            if ($rowCategory) {
            	foreach ($rowCategory as $rc)
            	{
            		$categories[] = $rc->folderGuid;
            	}
            }
            
            $this->view->assign('categories',$categories);*/
        }

        if($r->isPost())
        {
            $sessHistory = new Zend_Session_Namespace('BROWSER_HISTORY');
            $urlReferer = $sessHistory->urlReferer;

	        $aData = $r->getPost();
	
	        $aData['username'] = $this->_user->username;
	
	        $Bpm = new Pandamp_Core_Hol_Catalog();
	        $id	 = $Bpm->save($aData);
	        
            if ($id) {
            	/*$gen = new Pandamp_Form_Helper_CatalogInputGenerator();
            	$aRender = $gen->generateFormEdit($id);
            	$this->view->aRenderedAttributes = $aRender;*/
            	
            	//$modelCatalog = App_Model_Show_Catalog::show()->getCatalogByGuid($id);
// 	            $message = "Data was successfully saved.";
// 				$this->_helper->getHelper('FlashMessenger')
// 					->addMessage($message);

            	$queue = Zend_Registry::get(Bootstrap::NAME_ORDERQUEUE);
            	$queue->addJob('Pandamp_Job_Catalog',
            			['guid' => $id,'folderGuid' => $sessHistory->currentNode, 'ip' => Pandamp_Lib_Formater::getHttpRealIp(), 'kopel' => $this->_user->kopel, 'lang' => $this->view->getLanguage()],
            			false);
            	
            	$this->_helper->json([
            			'response' => true,
            			'message' => 'Artikel berhasil disimpan. <a href="'.ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/explorer/browse/node/'.$sessHistory->currentNode.'">Lihat artikel</a>.'
            		]);
					
				/*if ($modelCatalog->profileGuid == "klinik") {
					if ($modelCatalog->status == 99) {
						$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/clinic/browse/status/99/node/lt4b11e8c86c8a4');
					}
					else if ($modelCatalog->status == 2) {
						$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/clinic/browse/status/2/node/lt4b11ecf5408d2');
					}
					else if ($modelCatalog->status == 0) {
						$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/clinic/browse/status/0/node/lt4b11e8fde1e42');
					}
					else if ($modelCatalog->status == 1) {
						$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/clinic/browse/status/1/node/lt4b11ece54d870');
					}
					else 
					{
						$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/clinic/browse/status/'.$modelCatalog['status'].'/node/'.$sessHistory->currentNode);
					}
				}
				else if (!empty($aData['fixedKeywords']))
				{
					if (in_array($modelCatalog->profileGuid,array('article','clinic'))) {
					$keywords = base64_encode(trim($aData['fixedKeywords']));
					$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/catalog/relatedcatalog/guid/'.$id.'/profile/'.$modelCatalog->profileGuid.'/keywords/'.$keywords.'/node/'.$sessHistory->currentNode);
					}
				}
				else 
				{
					$this->_redirect(ROOT_URL.'/'.$this->_lang->getLanguage().'/dms/explorer/browse/node/'.$sessHistory->currentNode);
				}*/
            	
            }
        }

        $this->_helper->layout()->headerTitle = "Catalog Management: Edit Catalog";

        $sessHistory = new Zend_Session_Namespace('BROWSER_HISTORY');
        $sessHistory->urlReferer = $urlReferer;
        
        $this->view->urlReferer = $sessHistory->urlReferer;
    }
    
    /**
     * Related article
     */
    function relatedcatalogAction()
    {
    	$this->_helper->layout->setLayout('layout-dms-relation');
    	
    	$request  = $this->getRequest();
    	
    	$catalogGuid 	= $request->getParam('guid');
    	$profile 		= $request->getParam('profile');
    	$node 			= $request->getParam('node');
    	
    	$keywords = base64_decode($request->getParam('keywords'));
    	$keywords = explode(',',$keywords);
    	$keywords = array_filter(array_map('trim', $keywords));
    	$keywords = implode(' ',$keywords);
    	//$keywords = implode(' OR ',$keywords);
    	
    	$querySolr = $keywords.' title:[" " TO *] profile:'.$profile.' -id:'.$catalogGuid.' -profile:kutu_doc';
    	
    	$sQuery	 = $request->getParam('sQuery',$querySolr);
    	$nOffset = $request->getParam('nOffset',0);
    	$nLimit	 = $request->getParam('nLimit',50);
    	
        $withSelected = ($request->getParam('relate'))?$request->getParam('relate'):"relateas";
        $this->view->assign('withSelected', $withSelected);

    	
    	$indexingEngine = Pandamp_Search::manager();
    	
        $hits = $indexingEngine->find($sQuery,$nOffset, $nLimit,"publishedDate desc");

            
        $this->view->assign('nOffset', $nOffset);
        $this->view->assign('nLimit', $nLimit);
        $this->view->assign('hits',$hits);
        $this->view->assign('query',$sQuery);
    	$this->view->assign('profile',$profile);
    	$this->view->assign('guid',$catalogGuid);
    	$this->view->assign('node',$node);
    	
    	$this->_helper->layout()->headerTitle = "Related Article";
    }
    
    /**
     * Suggest tags based on user's input
     *
     * @return void
     */
    public function suggestAction()
    {
    	$this->_helper->getHelper('viewRenderer')->setNoRender();
    	$this->_helper->getHelper('layout')->disableLayout();
    
    	$request = $this->getRequest();
    	$q 		 = $request->getParam('term');
    	$profile = $request->getParam('profile');
    	$category = $request->getParam('category');
    	$limit 	 = $request->getParam('limit', 10);
    	
    	if ($profile)
    		$q .= ' status:99 profile:' . $profile;
    	else
    		$q .= ' status:99 profile:'.$category.' -profile:kutu_contact -profile:kutu_doc -profile:comment -profile:partner -profile:author -profile:about_us -profile:kategoriklinik -profile:kutu_email -profile:kutu_kotik -profile:kutu_mitra';
    
    	if ($category=="(kutu_peraturan_kolonial OR kutu_rancangan_peraturan OR kutu_peraturan)")
    		$orderBy = "fixedDate desc";
    	else
    		$orderBy = "publishedDate desc";
    	
    	$indexing = Pandamp_Search::manager();
    	
    	$hits = $indexing->find($q, 0, $limit,$orderBy);
    	$solrNumFound = count($hits->response->docs);
    
    	if($solrNumFound>$limit)
    		$numRowset = $limit ;
    	else
    		$numRowset = $solrNumFound;
    
    	//$return = '';
    	$a = array();
    	for ($i=0;$i<$numRowset;$i++) {
    		$row = $hits->response->docs[$i];
    		//$return .= $row->title . '|' . $row->id . "\n";
    			
    		$profileSolr = str_replace("kutu_", "", $row->profile);
    		$profileSolr = str_replace("kategoriklinik","Kategori Klinik",$profileSolr);
    		$profileSolr = str_replace("peraturan_kolonial","Peraturan Kolonial",$profileSolr);
    		$profileSolr = str_replace("rancangan_peraturan","Rancangan Peraturan",$profileSolr);
    		$profileSolr = str_replace("financial_services","Financial Services",$profileSolr);
    		$profileSolr = str_replace("general_corporate","General Corporate",$profileSolr);
    		$profileSolr = str_replace("oil_and_gas","Oil and Gas",$profileSolr);
    		$profileSolr = str_replace("executive_alert","Executive Alert",$profileSolr);
    		$profileSolr = str_replace("manufacturing_&_industry","Manufacturing & Industry",$profileSolr);
    		$profileSolr = str_replace("consumer_goods","Consumer Goods",$profileSolr);
    		$profileSolr = str_replace("telecommunications_and_media","Telecommunications and Media",$profileSolr);
    		$profileSolr = str_replace("executive_summary","Executive Summary",$profileSolr);
    		$profileSolr = str_replace("hot_news","Hot News",$profileSolr);
    		$profileSolr = str_replace("hot_issue_ile","Hot Issue ILE",$profileSolr);
    		$profileSolr = str_replace("hot_issue_ild","Hot Issue ILD",$profileSolr);
    		$profileSolr = str_replace("hot_issue_ilb","Hot Issue ILB",$profileSolr);
    			
    		//$a[$i]['label']= strip_tags($row->title) . '|' . $profileSolr;
    		$a[$i]['label']= strip_tags($row->title);
    		$a[$i]['value']= $row->id;
    	}
    	//$this->getResponse()->setBody($return);
    	$this->getResponse()->setBody(Zend_Json::encode($a));
    }
}
