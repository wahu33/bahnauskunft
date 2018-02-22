<?php
/* --------------------------------------------
*  Verspätungsalarm - Aufruf in Cronjob
*  Dieses Skript macht keine Ausgabe
*  Autor: Walter Hupfeld
*  Version 0.1 vom 10.10.2017
*  zuletzt bearbeitet: 10.10.2017
*  -------------------------------------------- */
include('lib/phpbahn.php');
include('lib/mail.php');
include('lib/settings.php');

$bahn = new phpbahn(SETTING_APIKEY);
$db = new SQLite3(DB_FILENAME);

// Über alle EMail-Adressen iterieren - nur eine E-Mail pro User
$strSQL="SELECT email FROM tableAlarm GROUP BY email";
$result = $db->query($strSQL);
while ($row=$result->fetchArray()) {
    $strEmail=$row['email'];
    //echo $strEmail."<br>";
    //Über die Bahnhöfe iterieren - Nutze die Ibnr
    $strSQL="SELECT ibnr from tableAlarm  WHERE email='$strEmail' GROUP BY ibnr";
    $result2=$db->query($strSQL);
    $maxVerspaetung=0;
    $strMailText="";
    while ($row2=$result2->fetchArray()){
        $strIbnr=$row2['ibnr'];
        //echo "-".$strIbnr."<br>";
        //$bhf = $bahn->getStation($strStation) ;
        //reset($bhf);
        //$ibnr = key($bhf);
        //$bhf = array_shift($bhf);
        //echo $ibnr."<br>";
        $strSQL="SELECT zug,departure from tableAlarm  WHERE email='$strEmail' AND ibnr='$strIbnr'";
        $result3=$db->query($strSQL);
        $arrZuege= array();
        while ($row3=$result3->fetchArray()) {
            //echo "*".$row3['zug']." - ".$row3['departure']."<br>";
            array_push($arrZuege,$row3['zug']);
        }
        // Jetzt über die aktuelle Stunde und die nächste Stunde iterieren
        for ($numStunde=0;$numStunde<=1;$numStunde++) {
            $numZeit = time() + $numStunde*3600;
            $zuege = $bahn->getTimetable($strIbnr,$numZeit);
            if(!count($zuege)){	echo "keine Verbindungen"; }
            //DEBUG:  print_r($arrZuege);echo "<hr>";

            foreach($zuege as $zug){
              	$zugname = $zug['zug']['klasse'].$zug['zug']['nummer'];
              	//echo $zugname."<br>\n";
                //print_r($arrZuege);echo "<hr>";
              	if(in_array($zugname, $arrZuege) AND isset($zug['abfahrt']) ) {
                    $timeAbfahrt=$bahn->dateToTimestamp($zug['abfahrt']['zeitGeplant']);
                    $strAbfahrt=date("H:i",$timeAbfahrt);
                    $strStation = $arrStations[$strIbnr];
                		if(!isset($zug['abfahrt']['zeitAktuell'])){
                			$verspaetung = 0;
                		}else{
                			$verspaetung = $bahn->dateToTimestamp($zug['abfahrt']['zeitAktuell'])-$bahn->dateToTimestamp($zug['abfahrt']['zeitGeplant']);
                      $strMailText.="$zugname hat ". ($verspaetung/60) . " Minuten Verspätung ab $strStation.\r\n";
                		}
                    if ($verspaetung>$maxVerspaetung) $maxVerspaetung=$verspaetung;

                    $strSQL="insert into verspaetungen (zeit,station,zug,verspaetung,email,abfahrt)
                                      values (datetime(),'$strStation','$zugname','$verspaetung','$strEmail','$strAbfahrt')";
                    $db->exec($strSQL);
                    //  echo "DEBUG: ".$strSQL."<br>\n";
              	}
            }
        }
     }
    if ($maxVerspaetung>=360) {
      echo "Versende Mail an $strEmail mit Verspätungsalarm:<br>";
      echo nl2br($strMailText);
      alertmail($strEmail,$strMailText);
    }
}
