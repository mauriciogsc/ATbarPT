<?
        error_reporting(0);
        header("Content-Type: image/gif");
        echo base64_decode("R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==");
        try {
                require_once("../../lib/db.class.php");
                $db = db::singleton("localhost", "atbar-stats", "n8sdaw4tjI8wef93dmd", "stats");

                $insert = array();
		$referer = array();
                $insert['time'] = "NOW()";
                if(!empty($_SERVER['HTTP_USER_AGENT'])) {
                        $agent = $_SERVER['HTTP_USER_AGENT'];

                        $agentClutter[] = '.NET CLR 1.1.4322';
                        $agentClutter[] = '.NET CLR 2.0.50727';
                        $agentClutter[] = '.NET CLR 3.0.30729';
                        $agentClutter[] = '.NET CLR 3.0.04506.30';
                        $agentClutter[] = '.NET CLR 3.0.04506.648';
                        $agentClutter[] = '.NET CLR 3.0.4506.2152';
                        $agentClutter[] = '.NET CLR 3.5.21022';
                        $agentClutter[] = '.NET CLR 3.5.30729';
                        $agentClutter[] = '.NET4.0C';
                        $agentClutter[] = '.NET4.0E';
                        $agentClutter[] = 'InfoPath.1';
                        $agentClutter[] = 'InfoPath.2';
                        $agentClutter[] = 'InfoPath.3';
                        $agentClutter[] = 'Media Center PC 6.0';
                        $agentClutter[] = 'MS-RTC LM 8';
                        $agentClutter[] = 'OfficeLivePatch.1.3';
                        $agentClutter[] = 'OfficeLiveConnector.1.4';
                        $agentClutter[] = 'OfficeLiveConnector.1.5';
                        $agentClutter[] = 'Tablet PC 2.0';
                        $agentClutter[] = 'BTRS28059';
                        $agentClutter[] = 'GTB6.3';
                        $agentClutter[] = 'GTB6.5';
                        $agentClutter[] = 'GTB6.6';
                        $agentClutter[] = 'ShopperReports 3.0.491.0';
                        $agentClutter[] = 'SRS_IT_E8790575BC765A5134A998';
                        $agentClutter[] = 'SLCC1';
                        $agentClutter[] = 'SLCC2';
                        $agentClutter[] = 'FunWebProducts';
                        $agentClutter[] = 'MathPlayer 2.20';
                        foreach($agentClutter as $clutter) $agent = str_replace($clutter, "", $agent);
                        foreach(explode(";", $agent) as $i) if($i != " ") {
                                $insert['agent'] .= $i . ";";
                        }

                        $insert['agent'] = str_replace("; );", ")", $insert['agent']);

                        //$insert['agent'] = $agent;
                }
                if(!empty($_SERVER['REMOTE_ADDR'])) $insert['remote_host'] = gethostbyaddr($_SERVER['REMOTE_ADDR']);
                if(!empty($_SERVER['HTTP_REFERER'])) {
                        preg_match("/\/\/[w]{0,3}\.?(.*?)\//", $_SERVER['HTTP_REFERER'], $refererArr);

                        $referer['referer'] = (count($refererArr) > 1) ? $refererArr[1] : $_SERVER['HTTP_REFERER'];
                }
                if(!empty($_GET['channel'])) $insert['channel'] = $_GET['channel'];
                if(!empty($_GET['version'])) $insert['version'] = $_GET['version'];

                $db->insert($insert, "usage");
		$db->insert($referer, "usage_referer");

                $db->runBatch();
        } catch(Exception $e) {

        }

