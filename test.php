<?php
function checksum ($code) {
   $a = hexdec($code[0]);
   $b = hexdec($code[1]);
   $c = hexdec($code[2]);
   $d = hexdec($code[3]);
   $a = $a + $b + $c + $d;
   return substr(dechex($a),-1);
}

$CODE = Null;
$CODE1 = Null;
$OPN = Null;
$OPN1 = Null;
$OPN2 = Null;
$ODD = "0";

switch ($_GET["Submit"]) {
  case "Off":
    $CODE = "C005";
    $OPN = "0,0,0";
    if (file_exists("/user1/hvac/mbr-sleep")) {
       unlink("/user1/hvac/mbr-sleep");  }
    break;
  case "Jet":
    $CODE = "1008";
    $OPN = "Jet";
    break;
  case "Heat-68-Hi":
    $CODE = "0454";
    $OPN = "4,5,4";
    break;
  case "Heat-66-Med":
    $CODE = "0442";
    $OPN = "4,4,2";
    break;
  case "Cool-74-Med":
    $CODE = "0082";
    $OPN = "0,8,2";
    break;
  case "Cancel":
    $CODE = "B000";
    if (file_exists("/user1/hvac/mbr-off")) {
       unlink("/user1/hvac/mbr-off");  }
    if (file_exists("/user1/hvac/mbr-on")) {
       unlink("/user1/hvac/mbr-on");  }
    $OPN2=" Timer";
    break;
  case "Sleep":
    $CODE = 'A' . substr('000' . dechex($_GET["sleep"]),-3);
    file_put_contents("/user1/hvac/mbr-sleep",time()+($_GET["sleep"]*60));
    $OPN2=$_GET["sleep"];
    break;
  case "On":
    $TEMP=$_GET["temp"];
    if ($TEMP[1]=="X") $ODD=4;
    $CODE = $ODD . $_GET["system"] . $TEMP[0] . $_GET["fan"];
    $OPN =  $_GET["system"] . "," . $_GET["temp"] . "," . $_GET["fan"];
    break;
  case "Change":
    $TEMP=$_GET["temp"];
    if ($TEMP[1]=="X") $ODD=4;
    $CODE = $ODD . dechex(8 + $_GET["system"]) . $TEMP[0] . $_GET["fan"];
    $OPN =  "Change," . $_GET["system"] . "," . $_GET["temp"] . "," . $_GET["fan"];
    break;
  case "Set":
    $now = time();
    if (!empty($_GET["on"])) {
       $on = strtotime($_GET["on"]);
       if ($now > $on) { $on = $on + 86400; }
       $CODE = '8' . substr('000' . dechex((int)(($on - $now)/60)),-3);
       file_put_contents("/user1/hvac/mbr-on",$_GET["on"].",".$on);
       $OPN2="On at " . $_GET["on"]; }
    if (!empty($_GET["off"])) {
       $off = strtotime($_GET["off"]);
       if ($now > $off) { $off = $off + 86400; }
       $CODE1 = '9' . substr('000' . dechex((int)(($off - $now)/60)),-3);
       file_put_contents("/user1/hvac/mbr-off",$_GET["off"].",".$off);
       $OPN1="Off at " . $_GET["off"];  }
    if (isset($off) and isset($on) and ($off < $on)) {
       $CODE = Null;
       $CODE1 = Null; }
    break;
  case "F-Set":
    if ($_GET["fmode"]=="X") {
#       $fcmd = "0,".$_GET["fmode"]."\n";
#       $mfile=fopen("/user1/hvac/set_pt1","w");
#       fwrite($mfile,$fcmd);
#       print $fcmd;
#       fclose($mfile);
       touch("/user1/hvac/off");  }
    elseif ($_GET["fmode"]=="O") {
       if (file_exists("/user1/hvac/off")) {
          unlink("/user1/hvac/off");  }}
    elseif ($_GET["fmode"]=="F") {
#       if (file_exists("/user1/hvac/off")) {
#          unlink("/user1/hvac/off");  }
       touch("/user1/hvac/fan");  }
    elseif ($_GET["fmode"]=="N") {
#       if (file_exists("/user1/hvac/off")) {
#          unlink("/user1/hvac/off");  }
       if (file_exists("/user1/hvac/fan")) {
          unlink("/user1/hvac/fan");  }}
    else {
       if (file_exists("/user1/hvac/off")) {
          unlink("/user1/hvac/off");  }
       $fcmd = $_GET["ftemp"].",".$_GET["fmode"]."\n";
       $mfile=fopen("/user1/hvac/set_pt1","w");
       fwrite($mfile,$fcmd);
#      print $fcmd;
       fclose($mfile);
       print "<h3>Thermostat set to ".$_GET["ftemp"]." degrees.<br>Normal programming resumes at next preset time."; }
    $CODE = Null;
    break;
  case "Vacation On":
    touch("/user1/hvac/vacation");
    $CODE = Null;
    break;
  case "Vacation Off":
    if (file_exists("/user1/hvac/vacation")) {
       unlink("/user1/hvac/vacation"); }
    $CODE = Null;
    break;
  default:
    $CODE = Null;
}
$conf = Null;
if (!is_null($CODE)) {
   $CODE = '88' . $CODE . checksum($CODE);
   $conf .= "          MY_COMMAND               0x" . strtoupper($CODE) . "\n"; }
if (!is_null($CODE1)) {
   $CODE1 = '88' . $CODE1 . checksum($CODE1);
   $conf .= "          MY_COMMAND1              0x" . strtoupper($CODE1) . "\n"; }
if (!is_null($conf)) {
   $conf = file_get_contents("/user1/lircd/lircd.conf1") . $conf;
   $conf .= file_get_contents("/user1/lircd/lircd.conf2");
   file_put_contents("/user1/lircd/lircd.conf", $conf);
   system("sudo /usr/bin/pkill -HUP lircd");

   if (!is_null($CODE)) {
      system("/usr/bin/irsend SEND_ONCE lgac MY_COMMAND");
      if (!is_null($OPN))  {
         if ($OPN=="Jet") { $OPN=file_get_contents("/run/mbr-set") . ", JET"; }
         if (($fh = fopen("/run/mbr-set", "r")) !== FALSE) {
            $md = fgetcsv($fh, 100, ",");
            $cmd = str_getcsv($OPN);
            if (($md[0]=='0') and ($md[1]=='0') and ($md[2]=='0') and ($cmd[0]=="Change"))  {
               $OPN = "0,0,0";  }
            else {
               if ($cmd[0]=="Change") {
                  $OPN = $cmd[1] . ',' . $cmd[2] . ',' . $cmd[2]; }
            }
            fclose($fh);
         }
         if ($_GET["Submit"] != "Off") {
            echo "not off<br>";
            file_put_contents("/run/mbr-prev-cmd", $OPN);  }
         file_put_contents("/run/mbr-set", $OPN);  }
      echo "sending MY_COMMAND " . $CODE . "<br>";
      if (!is_null($OPN)) {
         $Log_Info = "Sending ".$_GET["Submit"].", ".$OPN.". Code: ".$CODE;  }
      if (!is_null($OPN2)) {
         $Log_Info = "Sending ".$_GET["Submit"].", ".$OPN2.". Code: ".$CODE;  }
      syslog(LOG_INFO, $Log_Info);  }
   if (!is_null($CODE1)) {
      sleep(2);
      system("/usr/bin/irsend SEND_ONCE lgac MY_COMMAND1");
      echo "sending MY_COMMAND1 " . $CODE1 . "<br>";
      $Log_Info = "Sending ".$_GET["Submit"].", ".$OPN1.". Code: ".$CODE1;
      syslog(LOG_INFO, $Log_Info);  }
#  else  { echo "conf is null<br>";  }
}
?>
