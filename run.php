<?php 

$result 			= shell_exec("ffmpeg -y -i upload/posts/150826100950_841549.webm   -vcodec libx264  upload/posts/new.mp4  >log1.txt 2>log2.txt &"); 

echo "=========completed=========";
?>