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
switch ($_GET["Submit"]) {
  case "Off":
    $CODE = "C005";
    break;
  case "Jet":
    $CODE = "1008";
    break;
  case "Heat-68-Hi":
    $CODE = "0454";
    break;
  case "Heat-68-Med":
    $CODE = "0452";
    break;
  case "Cool-72-Med":
    $CODE = "0072";
    break;
  case "Cancel":
    $CODE = "B000";
    break;
  case "Sleep":
    $CODE = 'A' . substr('000' . dechex($_GET["sleep"]),-3);
    break;
  case "On":
    $CODE = '0' . $_GET["system"] . $_GET["temp"] . $_GET["fan"];
    break;
  case "Change":
    $CODE = '0' . dechex(8 + $_GET["system"]) . $_GET["temp"] . $_GET["fan"];
    break;
  case "Set":
    $now = time();
    if (!empty($_GET["on"])) { 
       $on = strtotime($_GET["on"]);
       if ($now > $on) { $on = $on + 86400; }
       $CODE = '8' . substr('000' . dechex((int)(($on - $now)/60)),-3);  }
    if (!empty($_GET["off"])) { 
       $off = strtotime($_GET["off"]);
       if ($now > $off) { $off = $off + 86400; }
       $CODE1 = '9' . substr('000' . dechex((int)(($off - $now)/60)),-3); }
    if (isset($off) and isset($on) and ($off < $on)) {
       $CODE = Null;
       $CODE1 = Null; }
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
      echo "sending MY_COMMAND " . $CODE . "<br>"; }
   if (!is_null($CODE1)) {
      sleep(2);
      system("/usr/bin/irsend SEND_ONCE lgac MY_COMMAND1");
      echo "sending MY_COMMAND1 " . $CODE1 . "<br>"; }}
  else  { echo "conf is null<br>";  }
?>
