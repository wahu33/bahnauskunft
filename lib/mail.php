<?php

function alertmail($strEmail,$strText) {
    global $strSender;
    global $strCarbonCopy;

    $to = $strEmail;
    $subject = "Bahn-Alert";

    $message = "<html><head><title>Bahn Alert</title>
    </head><body><h2>Verspätungsalarm</h2><p>".
    nl2br($strText)."</p></body></html>";

    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    // More headers
    $headers .= "From: ".$strSender . "\r\n";
    $headers .= "Cc: ".$strCarbonCopy . "\r\n";

    mail($to, $subject, $message, $headers);
}
?>
