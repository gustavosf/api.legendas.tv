#!/usr/bin/php
<?php

/* Exemplo de shellscript para download de legendas usando a classe LegendasTV */

require 'Console/Getopt.php';
$opt = new Console_Getopt;

require dirname(__FILE__).'/../lib/legendastv.php';
LegendasTV::config('sega', 'falkland');

/**
 * Função para emular o famoso readln :P
 * @return  string  O que o cara digitou no console
 */
function readln()
{
	while (false !== ($ln = fgets(STDIN)))
		return $ln;
}

function &condense_arguments($params)
{
    $new_params = array();
    foreach ($params[0] as $param) {
        $new_params[$param[0]] = $param[1];
    }
    return $new_params;
}

$options = $opt->getopt($opt->readPHPArgv(), 'l:');
$search = implode(" ", $options[1]);
$options = condense_arguments($options);

/* Começa a treta :D */
$subtitles = LegendasTV::search($search, 'release', @$option['l'] ?: 'pt-br');

if (count($subtitles) > 1)
{
	echo "Qual das legendas abaixo desejas baixar?\n\n";
	foreach ($subtitles as $id => $subtitle)
	{
		echo "[{$id}] {$subtitle->filename}\n";
	}
	$option = (int)readln();

	while ( ! isset($subtitles[$option]))
	{
		echo "Opção ińválida. Digite novamente: ";
		$option = readln();
	}

	$subtitle = $subtitles[$option];
}
else
{
	$subtitle = $subtitles[0]; // A única :)
}

echo "Baixando {$subtitle->download_link}...\n";
$subtitle->download();
echo "Arquivo ".basename($subtitle->download_link)." baixado!\n";

?>
