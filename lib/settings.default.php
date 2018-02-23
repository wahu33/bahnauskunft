<?php
const SETTING_APIKEY = "use-your-own-api-key";
const SETTING_BAHNHOF = "Hamm";
const SETTING_STOPS = 30;
const DB_FILENAME = "sqlite/alarm.db";

$strUrl = (!empty($_SERVER["REQUEST_URI"])) ? $_SERVER["REQUEST_URI"] : "";
$arrStations = array ('8000149'=>'Hamm',
                      '8000076'=>'Soest',
                      '8000080'=>'Dortmund',
                      '8000263'=>'Münster',
                      '8000297'=>'Paderborn',
                      '8000207'=>'Köln Hbf',
                      '8000085'=>'Düsseldorf',
                      '8003680'=>'Limburg');

$strSender = "user@example.com";
$strCarbonCopy = "user@example.com";

date_default_timezone_set("Europe/Berlin");
