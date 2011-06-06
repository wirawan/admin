<?php

/**
 * Description of SearchController
 *
 * @author nihki <nihki@madaniyah.com>
 */
class Dev_SearchController extends Zend_Controller_Action
{
    function  preDispatch()
    {
        $this->_helper->layout->setLayout('layout-customer-migration');
    }
    function solrAction()
    {
        $this->_helper->viewRenderer->setNoRender(TRUE);
        $title = "<h4>HUKUMONLINE INDONESIA: <small>search</small></h4><hr/>";

        echo $title.'<br>';

        $solr = new Apache_Solr_Service( 'localhost', '8983', '/solr/core0' );
        if ( ! $solr->ping() ) {
            echo 'Solr service not responding.';
            exit;
        }
        else
        {
            echo 'is ON<br>';
        }

        $start = 0;
        $limit = 50;
        
        //$querySolr = 'id:(hol10111 hol10112)';
        $rowset = App_Model_Show_CatalogFolder::show()->getCatalogGuidByFolderGuid("fb16", $start, $limit);

        $numi = count($rowset);
        $querySolr = "id:(";
        for($i=0;$i<$numi;$i++)
        {
            $row = $rowset[$i];
            $querySolr .= $row['guid'] .' ';
        }
        $querySolr .= ')';

        //$aParams = array('qt'=>'spellCheckCompRH', 'spellcheck.q'=>$querySolr, 'spellcheck'=>'true','spellcheck.collate'=>'true');
        $aParams=array('q.op'=>'OR');
        //$response = $solr->search( $querySolr,0, 10000, $aParams);
        $response = $solr->searchByPost( $querySolr,$start, $limit, $aParams);
        
        if ( $response->getHttpStatus() == 200 ) {
            echo '<pre>';
            print_r( $response->getRawResponse() );
            echo '</pre>';

            if ( $response->response->numFound > 0 ) {
                if(isset($response->spellcheck->suggestions->collation))
                    echo '<br>Did you mean: <strong>'.$response->spellcheck->suggestions->collation.'</strong>';
                    echo "found: ". $response->response->numFound,"<br />";

                $i=0;
                foreach ( $response->response->docs as $doc ) {
                    if(!isset($doc->subTitle))
                        $subTitle = '';
                    else
                        $subTitle = $doc->subTitle;

                    echo $i++." $doc->title <br /> $subTitle <br>";
                }

                echo '<br />';
            }
            else
            {
                if(isset($response->spellcheck->suggestions->collation))
                {
                    echo "No match. Maybe what you want to search is: ". $response->spellcheck->suggestions->collation;
                }
            }
        }
        else {
            echo 'test';
            echo $response->getHttpStatusMessage();
        }
    }
}
