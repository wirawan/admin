<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FolderViewer
 *
 * @author user
 */
class Dms_Relation_FolderViewer
{
    public $view;
    public $catalogGuid;

    public function __construct($catalogGuid, $folderGuid)
    {
        $this->view = new Zend_View();
        $this->view->setScriptPath(dirname(__FILE__));

        $this->catalogGuid = $catalogGuid;
        $this->view->catalogGuid = $catalogGuid;
        $this->view->folderGuid = $folderGuid;

        $this->view->addHelperPath(ROOT_DIR.'/library/Pandamp/Controller/Action/Helper', 'Pandamp_Controller_Action_Helper');

        $bpm = new Pandamp_Core_Hol_Catalog();
        $this->view->rowsetFolder = $bpm->getFolders($catalogGuid);
    }
    public function render()
    {
        $aName = explode('_', basename(__FILE__));

        return $this->view->render(str_replace('.php','.phtml',strtolower(basename(__FILE__))));
    }
}
