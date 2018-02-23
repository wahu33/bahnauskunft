<?php
/* --------------------------------------------
*  Verspätungsalarm einrichten
*  Autor: Walter Hupfeld
*  Version 0.1 vom 10.10.2017
*  zuletzt bearbeitet: 10.10.2017
*  -------------------------------------------- */
?>
<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>DB-Verspätungsalarm</title>
    <meta name="description" content="Deutsche Bahn Verspätungsalarm einrichten">
    <meta name="author" content="Walter Hupfeld">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous"><script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
   <style>
     .choosen { width: 500px; }
     .red { color:red;}
  </style>
    <script type="text/javascript">
      function submitform()
      {
        document.zugalarm.submit();
      }
   </script>
  </head>
<?php

    include("lib/settings.php");
    include("lib/phpbahn.php");

    $db = new SQLite3(DB_FILENAME);
/*
    /Noch an geänderte Datenstruktur anpassen 10.10.18
    $db-> exec("CREATE TABLE IF NOT EXISTS tableAlarm(
       id INTEGER PRIMARY KEY AUTOINCREMENT,
       email text NOT NULL DEFAULT '',
       zug text NOT NULL DEFAULT '',
       station text NOT NULL DEFAULT '')");
*/

    $strIbnr = (!empty($_POST['station'])) ? $_POST['station'] : "8000149"; //Hamm
    $numHour    = (!empty($_POST['hour'])) ? $_POST['hour'] : 0 ;
    $strEmail  =  (!empty($_POST['email'])) ? $_POST['email'] : "";
    $strStation = $arrStations[$strIbnr];

    // Züge zum Verspätungsalarm ausgewählt -> in die Datenbank einfügen
    $arrSZuege = (!empty($_POST['zug'])) ? $_POST['zug'] : array();
    foreach ($arrSZuege as $strZug) {
        //Zugnummer und Abfahrtszeit splitten
        $arrZug = explode("_",$strZug);
        $strZug = $arrZug[0];
        $strDeparture = $arrZug[1];
        //Erst mal schauen, ob der Zug schon eingetragen ist.
        $strSQL = "SELECT id FROM tableAlarm WHERE email='$strEmail'
                                AND zug='$strZug' AND station='$strStation'";
        $result=$db->query($strSQL);
        if ($result->fetchArray()==FALSE) {
            //Zug eingtragen
            $strSQL = "INSERT INTO tableAlarm (email,zug,station,departure,ibnr)
                        VALUES ('$strEmail','$strZug','$strStation','$strDeparture','$strIbnr')";
            $db-> exec($strSQL);
        }
    }

    $boolOk = false;
    // Formular ausgefüllt und E-Mail vorhanden - Züge und Abfahrtstermine ermitteln
    if (!(empty($strIbnr) or empty($numHour) or empty($strEmail))) {
        $boolOk = true;
        $bahn = new phpbahn(SETTING_APIKEY);
        //Bahnhofsnummer muss nicht jedesmal ermittelt werden
        //Wird per Hand in settings.php eingetragen
        //$bhf = $bahn->getStation($strStation) ;
        //reset($bhf);
        //$ibnr = key($bhf);
        //$bhf = array_shift($bhf);
        $time = strtotime('midnight')+$numHour*3600;
        $zuege = $bahn->getTimetable($strIbnr, $time );
        if (!count($zuege)) { echo "keine Verbindungen"; }
    }

    // Eintrag für Teilnehmer löschen
    if ($_POST['delete']==123){
        $numId = (!empty($_POST['id'])) ? (int)$_POST['id'] : -1;
        $strSQL="DELETE FROM tableAlarm WHERE id=$numId";
        $db = new SQLite3(DB_FILENAME);
        $db->exec($strSQL);
    }

?>
  <body>
  <div class="container">
    <h2>Verpätungsalarm einrichten</h2>
    <div class="row">
    <div class="col-6">
    <div class="card choosen">
    <div class="card-header"><h4>Alarm hinzufügen</h4></div>
    <div class="card-body">
    <form id="formv" method="post" action="<?=$strUrl?>" style="width:450px;">
        <div class="form-group">
          <label for="station">Abfahrtsbahnhof:</label>
          <select class="form-control" id="station" name="station">
            <?php
                foreach ($arrStations as $strKey => $strVal) {
                    $strSelected = ($strIbnr == $strKey) ? "selected='selected'" : "";
                    echo "<option $strSelected value='$strKey'>$strVal</option>›";
                }
            ?>
          </select><br>

          <label for="hour">Abfahrt</label>
          <select class="form-control" id="hour" name="hour">
            <?php
                for ($i=5;$i<=20;$i++) {
                  $strSelected = ($i==$numHour) ? "selected='selected'" : "" ;
                  echo "<option $strSelected value='$i'>$i:00</option>";
                }
             ?>
          </select><br>

          <label for="email">Mailadresse:</label>
            <input  type="email" class="form-control" id="email" name="email" type="text" value="<?=$strEmail?>"><br>
<?php
    if ($boolOk) {
          foreach ($zuege as $zug) {
            if(isset($zug['abfahrt'])){
                echo "<div class='row'>";
                $strZiel = array_pop($zug['abfahrt']['routeGeplant']);
                $timeAbfahrt = $bahn->dateToTimestamp($zug['abfahrt']['zeitGeplant']);
                $strAbfahrt = date("H:i", $timeAbfahrt);
                $strBahnValue = $zug['zug']['klasse'].$zug['zug']['nummer']."_".$strAbfahrt;
                $strBahnAnzeige =
                    $zug['zug']['klasse']." ".$zug['zug']['nummer']."</div>".
                    "<div class='col-2'>" . $strAbfahrt . "</div>".
                    "<div class='col-6'>" . $strZiel  . "</div>";

                echo "<div class='col-4'>&nbsp;&nbsp;&nbsp;<input type='checkbox' name='zug[]' class='form-check-input' value='$strBahnValue'> ";
                echo $strBahnAnzeige;
                echo "</div>\n";
              }
          }
          //DEBUG: echo "<pre>"; print_r($zuege); echo "</pre>";
     }
 ?>
           <button type="submit" class="btn btn-primary">Abschicken</button>
       </div> <!-- form-group -->
     </div> <!-- card-body -->
   </div> <!-- card -->
    </form>
  </div> <!-- col-6 -->
  <div class="col-6">
<?php
  //DEBUG: echo "<pre>"; print_r($_POST); echo "</pre>";
  if (!empty($strEmail)) {
    echo "<div class='card choosen'>";
    echo "<div class='card-header'><h4>Ihre vorhandenen Alarme:</h4></div>";
    echo "<div class='card-body'>";
    echo "<strong>E-Mail</strong>: $strEmail<br>";
    $strSQL = "SELECT DISTINCT * from tableAlarm WHERE email='$strEmail' ORDER BY departure";
    $result = $db->query($strSQL);
    echo "<form id='zugalarm' name='zugalarm' action='$strUrl' method='post' onsubmit=''>\n";
    echo "<div class='row'>";
    while ($row = $result->fetchArray()) {
      echo "<div class='col-4'>".$row['station']."</div>\n";
      echo "<div class='col-4'><i class='fa fa-train' aria-hidden='true'></i> ".$row['zug']."</div>\n";
      echo "<div class='col-3'>".$row['departure']." Uhr</div>\n";
      echo "<div class='col-1'>\n";

      echo "<input type='hidden' name='email' value='$strEmail'>\n";
      echo "<input type='hidden' name='hour' value='$numHour'>\n";
      echo "<input type='hidden' name='station' value='$strIbnr'>\n";
      echo "<input type='hidden' name='id' value='".$row['id']."'>\n";
      echo "<input type='hidden' name='delete' value='123'>\n";

      echo "<a href='javascript: submitform()'><i class='fa fa-times red' aria-hidden='true'></i></a>\n";
      echo "</div>\n";
    }
    echo "</div> <!-- row -->\n";
    echo "</form>\n";
    echo "</div> <!-- card-body -->\n";
    echo "</div> <!-- card -->";
  }
?>
  </div> <!-- col-6 -->
  </div> <!-- row -->
  </div> <!-- container -->
  </body>
</html>
