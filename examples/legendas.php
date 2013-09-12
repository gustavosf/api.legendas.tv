#!/usr/bin/php
<?php

/* Exemplo de shellscript para download de legendas usando a classe LegendasTV */
require dirname(__FILE__).'/../lib/legendastv.php';

/**
 * Função para emular o famoso readln :P
 * @return  string  O que o cara digitou no console
 */
function readln()
{
	while (false !== ($ln = fgets(STDIN)))
		return $ln;
}

/* tratamento de argumentos
 * l          language
 * f, first   baixa o primeiro link
 */
$options = getopt('l:f', array('first'));
$search = implode(" ", array_slice($argv, sizeof($options) + 1));

/* Começa a treta :D */
$subtitles = LegendasTV::search($search, @$options['l']);

if (count($subtitles) > 1 and ! (array_key_exists('f', $options) or array_key_exists('first', $options)))
{
	echo "Qual das legendas abaixo desejas baixar?\n\n";
	foreach ($subtitles as $id => $subtitle)
	{
		echo "[{$id}] ".$subtitle->destaque ? '*' : ' '." {$subtitle->arquivo} ({$subtitle->downloads}/dl {$subtitle->idioma})\n";
	}
	$option = (int)readln();

	while ( ! isset($subtitles[$option]))
	{
		echo "Opção ińválida. Digite novamente: ";
		$option = readln();
	}

	$subtitle = $subtitles[$option];
}
elseif ($subtitles)
{
	$subtitle = $subtitles[0]; // A única :)
}
else
{
	die('Nenhuma legenda encontrada');
}

echo "Baixando {$subtitle->arquivo}...\n";
$filename = $subtitle->download();
echo "Arquivo {$filename} baixado!\n";

?>
