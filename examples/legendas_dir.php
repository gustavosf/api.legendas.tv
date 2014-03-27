#!/usr/bin/php
<?php
define('TORRENTDIR','');
if($argv[1]){
   if(is_dir($argv[1])){
       define('TORRENTDIR',$argv[1]);
   }
}

require dirname(__FILE__).'/../lib/legendastv.php';

$files = scandir(TORRENTDIR);

foreach($files as $file){
    if(is_dir(TORRENTDIR.'/'.$file)) continue;
    if(preg_match('/\.(avi|mp4|mkv)/',$file,$s)){
        $file = basename($file,$s[0]);
        //tenta a pesquisa com o nome inteiro
        $subtitles = LegendasTV::search($file,'Português-BR');
        if($subtitles[0]){
            $subtitle  = $subtitles[0];
            echo "Baixando {$subtitle->arquivo}...\n";
            $filename = $subtitle->download();
            echo "Arquivo {$filename} baixado!\n";
            exec("mv $filename ".TORRENTDIR);
        } else {
            //nao achou com o nome inteiro, tenta só com o episodio, e baixa o primeiro encontrado
            $file = preg_replace('/\.(HDTV|480p|720p|1080p).+/','.$1',$file);
            $subtitles = LegendasTV::search($file,'Português-BR');
            if($subtitles[0]){
                $subtitle  = $subtitles[0];
                echo "Baixando {$subtitle->arquivo}...\n";
                $filename = $subtitle->download();
                echo "Arquivo {$filename} baixado!\n";
                exec("mv $filename ".TORRENTDIR);
            }
        }
    }
}
?>
