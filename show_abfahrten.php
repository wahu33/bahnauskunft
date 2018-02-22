<?php
  require('lib/phpbahn.php');
  require_once("lib/settings.php");

  $bahn = new phpbahn(SETTING_APIKEY);
  $bhf = $bahn->getStation($strStation);
  reset($bhf);
  $ibnr = key($bhf);
  $bhf = array_shift($bhf);

  //echo "DEBUG: ".$bhf."   ".$ibnr."   ".$bhf; exit;
  $numTime = ($numHour>=0) ? strtotime('midnight')+$numHour*3600 : $numTime=time();
  $zuege = $bahn->getTimetable($ibnr, $numTime) ;

  echo "<div id='header'>";
  echo "<form name='auswahl' action='$strUrl' method='post'>";
  echo "<strong>Abfahrten für ".$bhf['name']."</strong> ";
  // Auswahlbox Bahnhöfe ---------------------
  echo "<select name='station'  onChange='document.auswahl.submit()'>";
  foreach ($arrStations as $strStationItem) {
    $strSelected = ($strStationItem==$strStation) ? " selected='selected' " : "";
    echo "<option $strSelected value='$strStationItem'>$strStationItem</option>\n";
  }
  echo "</select>";
  // Auswahlbox Uhrzeit ---------------------
  echo "<select name='hour'  onChange='document.auswahl.submit()'>";
  for ($i=0;$i<=23;$i++) {
    $strHour = ($i<10) ? "0".$i : "".$i;
    $strSelected = ($numHour==$i) ? " selected='selected' " : "";
    echo "<option $strSelected value='$i'>$strHour:00 Uhr</option>\n";
  }
  echo "</select>\n";
  // ---------------------------
  echo "<a href='alarm.php'>Verspätungsarlarm</a>";
  echo "<span style='float:right'>".date("H:i")." Uhr</span>";
  echo "</form>\n";
  echo "</div>\n";
  // Ende Header --------------------------

  echo "<table id='abfahrttafel'  class='display'>\n".
            "<thead>".
            "<tr><th>Zug</th><th>Geplante Abfahrt</th>
            <th>Heutige Abfahrt</th><th>Delay</th><th>Geplantes Gleis</th><th>Heutiges Gleis</th>
            <th>Ziel</th><th>Über</th></tr></thead>\n";
  echo  "<tfoot>".
             "<tr><th>Zug</th><th>Geplante Abfahrt</th>
             <th>Heutige Abfahrt</th><th>Delay</th><th>Geplantes Gleis</th><th>Heutiges Gleis</th>
             <th>Ziel</th><th>Über</th></tr></tfoot>\n";

  //Die gefundenen Elemente werden nacheinander zu Tabellenzeilen
  foreach($zuege as $zug){
      //Dies ist eine Abfahrttafel. Daher werden nur Elemente berücksichtigt, die eine Abfahrt enthalten:
      if(isset($zug['abfahrt'])){

          $ziel = array_pop($zug['abfahrt']['routeGeplant']);
          $naechsteHalte = array_slice($zug['abfahrt']['routeGeplant'], 0, SETTING_STOPS);
          $strecke = implode(", ",$naechsteHalte);

          $streckeAktuell="";
          if (!empty($zug['abfahrt']['routeAktuell'])){
              $ziel2 = array_pop($zug['abfahrt']['routeAktuell']);
              $abweichendeHalte = array_slice($zug['abfahrt']['routeAktuell'], 0, SETTING_STOPS);
              $streckeAktuell = implode(", ",$abweichendeHalte);
              if ($strecke==$streckeAktuell) $streckeAktuell="";
          }

          $numAbfahrtGeplant = $bahn->dateToTimestamp($zug['abfahrt']['zeitGeplant']);
          if (isset($zug['abfahrt']['zeitAktuell'])) {
            $numAbfahrtAktuell = $bahn->dateToTimestamp($zug['abfahrt']['zeitAktuell']);
            $numDelay = $numAbfahrtAktuell - $numAbfahrtGeplant;
          }
          else {  $numDelay=0; }
          // ------------------------
          echo "<tr>";
          echo "<td class='nowrap'><i class='fa fa-train' aria-hidden='true'></i> ".$zug['zug']['klasse']." ".$zug['zug']['nummer']."</td>";
          echo "<td class='nowrap'><i class='fa fa-clock-o' aria-hidden='true'></i> ".date("H:i", $numAbfahrtGeplant)."</td>";
          // Abfahrt aktuell -------
          if(@$zug['abfahrt']['cancel'] == "cancelled"){
              echo "<td class='change nowrap'><i class='fa fa-times' aria-hidden='true'></i> FÄLLT AUS</td>";
              $numDelay=-1;
          }
          elseif(isset($zug['abfahrt']['zeitAktuell'])) {
              echo "<td class='change nowrap'>".date("H:i",$numAbfahrtAktuell) ."</td>";
          }
          else {
              echo "<td></td>";
          }

          // Delay ---------------
          $numDelay=$numDelay/60;
          if ($numDelay>10) {
            echo "<td class='red'><i class='fa fa-circle-o-notch fa-spin fa-fw'></i> +".$numDelay."</td>";
          }
          elseif ($numDelay>=5) {
            echo "<td class='red'></i> +".$numDelay."</td>";
          }
          elseif ($numDelay<0) {
            echo "<td></td>";
          }
          else {
            echo "<td class='green'>+".$numDelay."</td>";
          }

          // Gleis geplant ----------
          echo "<td>".$zug['abfahrt']['gleisGeplant']."</td>";

          // Gleis aktuell -----------
          if(isset($zug['abfahrt']['gleisAktuell'])){
              echo "<td class='change'>".$zug['abfahrt']['gleisAktuell']."</td>";
          }else{
               echo "<td></td>";
          }
          echo "<td>".$ziel."</td>";
          echo "<td class='strecke'>".$strecke;
          if ($streckeAktuell>"") { echo "<br><span class='red'>".$streckeAktuell."</span>"; }
          echo "</td>";
          echo "</tr>\n";
      }
  }
  echo "</table>\n";
