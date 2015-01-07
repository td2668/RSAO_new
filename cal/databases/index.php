<?php 

// we protect this directory because it has a file-list that could potentially be sensitive
// no need to help the spam-bot's out
header("Location: ../index.php"); 

?>