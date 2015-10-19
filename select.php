<?php
if (isset($_GET["opt"]) == TRUE) {
   $LIRC_COMMAND = "sudo irsend SEND_ONCE geac ".$_GET['opt'];  }
  else  {
   $LIRC_COMMAND = "sudo irsend SEND_ONCE geac OFF";  }
system($LIRC_COMMAND);
?>
