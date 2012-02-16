<?php

/**
 * Description of EventController
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Agenda_EventController extends Zend_Controller_Action
{
    protected $_user;
    function  preDispatch()
    {
        $this->_helper->layout->setLayout('layout-event');
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
            if (!$acl->checkAcl("site",'all','user', $this->_user->username, false,false))
            {
                $zl = Zend_Registry::get("Zend_Locale");
                $this->_redirect(ROOT_URL.'/'.$zl->getLanguage().'/error/restricted');
            }
        }
    }
    function postmessageAction()
    {
        if (!Pandamp_Controller_Action_Helper_IsAllowed::isAllowed('eventcalendar','all'))
        {
            $zl = Zend_Registry::get("Zend_Locale");
            $this->_redirect(ROOT_URL.'/'.$zl->getLanguage().'/error/restricted');
        }

        $r = $this->getRequest();
        if ($r->isPost())
        {
            $aData = $r->getParams();
            $aData['guid'] = $this->_user->kopel;
            try {

                $hol = new Pandamp_Core_Hol_Calendar();
                $hol->save($aData);

            }
            catch (Exception $e)
            {
                throw new Zend_Exception($e->getMessage());
            }
        }
        $this->_helper->layout()->headerTitle = "Event Calendar";
    }
    function openpostingAction()
    {
        $lang['days'] = array("Senin","Selasa","Rabu","Kamis","Jumat","Sabtu","Minggu");
        $lang['months'] = array("Januari","Februari","Maret","April","May","Juni","Juli","Agustus","September","Oktober","November","Desember");

        $pid = $this->_getParam('pid');

        $tblCalendar = new App_Model_Db_Table_Calendar();
        $row = App_Model_Show_Calendar::show()->openPosting($pid);

        $d = $row[0]['d'];
        $m = $row[0]['m'];
        $y = $row[0]['y'];
        $dateline = $d." ".$lang['months'][$m-1] ." ".$y;
        $wday = date("w", mktime(0,0,0,$m,$d,$y));

        $this->view->dateline = $dateline;
        $this->view->wday = $lang['days'][$wday-1];

        // write posting
        $rowposting = App_Model_Show_Calendar::show()->writePosting($pid);

        $title = stripslashes($rowposting[0]['title']);
        $body = stripslashes(str_replace("\n", "<br />", $rowposting[0]['text']));
        $postedby 	= "Posted by : " . $rowposting[0]['username'];

        if (!($rowposting[0]["start_time"] == "55:55:55" && $rowposting[0]["end_time"] == "55:55:55")) {
                if ($rowposting[0]["start_time"] == "55:55:55")
                        $starttime = "- -";
                else
                        $starttime = $rowposting[0]["stime"];

                if ($rowposting[0]["end_time"] == "55:55:55")
                        $endtime = "- -";
                else
                        $endtime = $rowposting[0]["etime"];

                $timestr = "$starttime - $endtime";
        } else {
                $timestr = "";
        }

        if (Pandamp_Controller_Action_Helper_IsAllowed::isAllowed('eventcalendar','all'))
        {
            $zl = Zend_Registry::get("Zend_Locale");
            $editstr = "<span class=\"display_edit\">";
            $editstr .= "[<a href=\"".ROOT_URL."/".$zl->getLanguage()."/calendar/event/editposting/pid/" . $pid."\">edit</a>]&nbsp;";
            $editstr .= "[<a href=\"".ROOT_URL."/".$zl->getLanguage()."/calendar/event/deleteposting/pid/" . $pid."\">delete</a>]&nbsp;</span>";

        }
        else
        {
            $editstr = "";
        }

        $this->view->title = $title;
        $this->view->body = $body;
        $this->view->postedby = $postedby;
        $this->view->timestr = $timestr;
        $this->view->editstr = $editstr;
        $this->view->pid = $pid;
        $this->view->month = $m;
        $this->view->year = $y;

        $this->_helper->layout()->headerTitle = "Event Calendar";
    }
    function editpostingAction()
    {
        $zl = Zend_Registry::get("Zend_Locale");
        if (!Pandamp_Controller_Action_Helper_IsAllowed::isAllowed('eventcalendar','all'))
        {
            $this->_redirect(ROOT_URL.'/'.$zl->getLanguage().'/error/restricted');
        }

        $r = $this->getRequest();
        $pid = $r->getParam('pid');

        $tblcalendar = new App_Model_Db_Table_Calendar();
        $rowedit = $tblcalendar->find($pid)->current();

        $day = $rowedit->d;
        if ($day < 10) $day = 0 . $day;
        $month = $rowedit->m;
        if ($month < 10) $month = 0 . $month;
        $year = $rowedit->y;

        $this->view->dateOfEvent = $day.'-'.$month.'-'.$year;
        $this->view->title = $rowedit->title;
        $this->view->text = $rowedit->text;
        $this->view->starttime = $rowedit->start_time;
        $this->view->endtime = $rowedit->end_time;
        $this->view->pid = $pid;


        if ($r->isPost())
        {
            $aData = $r->getParams();
            $aData['guid'] = $this->_user->kopel;
            try {

                $hol = new Pandamp_Core_Hol_Calendar();
                $hol->save($aData);

                $this->_redirect(ROOT_URL."/".$zl->getLanguage()."/calendar/event/openposting/pid/".$pid);
            }
            catch (Exception $e)
            {
                throw new Zend_Exception($e->getMessage());
            }
        }
        $this->_helper->layout()->headerTitle = "Event Calendar";
    }
    function deletepostingAction()
    {
        $req = $this->getRequest();
        $pid = $req->getParam('pid');
        $m = $req->getParam('m');
        $y = $req->getParam('y');

        try
        {
            $tblcalendar = new App_Model_Db_Table_Calendar();
            $tblcalendar->delete('id='.$pid);

            $message = $pid." has been deleted successfully";
        }
        catch (Exception $e)
        {
            $message = $e->getMessage();
        }

        $this->view->message = $message;
        $this->_helper->layout()->headerTitle = "Event Calendar";
    }
}
