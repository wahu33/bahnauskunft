<?php
  require_once("lib/settings.php");
  $arrDate=getDate();
  $strDefaultStation= ($arrDate['hours']<=12) ? "Hamm" : "Soest";
  $strStation = (!empty($_POST['station'])) ? $_POST['station'] : $strDefaultStation ;
  $numHour =  (!empty($_POST['hour'])) ?  (int)$_POST['hour'] : date("H",time());
?>
<!doctype html>
<html>
  <head>
      <meta charset="utf-8">
      <title>Anzeigetafel</title>
      <meta name="description" content="Abfahrttafel der Deutschen Bahn">
      <meta name="author" content="Walter Hupfeld">
      <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.16/css/jquery.dataTables.css">
      <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
      <script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.16/js/jquery.dataTables.js"></script>
  </head>
  <style>
      body { height:100%; font-family:sans-serif; padding:0; }
      #header { background-color:#333; padding:3px; color:white; margin:0; }
      #header a {color:white;}
      .change{  color:blue; }
      .red { color:red;}
      .green {color: green; }
      .strecke {font-size:0.8em;}
      .nowrap {white-space:nowrap;}
  </style>

<body>
  <div id="abfahrten">
    <?php  include("show_abfahrten.php");  ?>
  </div>

  <script>
    $(document).ready(function(){
       $('#abfahrttafel').DataTable( {
           "order": [[ 1, "asc" ]],
           "paging":   false,
       });
    });
  </script>

</body>
</html>
