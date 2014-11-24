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
 * l, lang    language
 * f, first   baixa o primeiro link
 */
$options = getopt('l:fd', array('first', 'lang::', 'logged::'));
$search = implode(" ", array_slice($argv, sizeof($options) + 1));

/* Começa a treta :D */
try {
	if (!isset($options['logged'])) LegendasTV::login('__SEU_USERNAME__', '__SUA_SENHA__');
	$subtitles = LegendasTV::search($search, @$options['l'] ?: (@$options['lang'] ?: 'Qualquer idioma'));
} catch (Exception $e) {
	die($e->getMessage());
}

if (array_key_exists('d', $options))
{ # caso flag "d" esteja ativada retorna apenas os destaques 
	$subtitles = array_filter($subtitles, function($subtitle) { return $subtitle->destaque; });
}
else
{ # caso contrário, ordena por destaque e downloads
	usort($subtitles, function($a, $b) {
		if ($a->destaque and !$b->destaque) return -1;
		if ($b->destaque and !$a->destaque) return 1;
		return $a->downloads > $b->downloads ? -1 : 1;
	});
}

if (count($subtitles) > 1 and ! (array_key_exists('f', $options) or array_key_exists('first', $options)))
{
	echo "Qual das legendas abaixo desejas baixar?\n\n";
	foreach ($subtitles as $id => $subtitle)
	{
		echo sprintf("[%".(count($subtitles) > 10 ? 2 : 1)."d] %s %s (%d/dl %s)\n", $id, $subtitle->destaque ? '*' : ' ', $subtitle->arquivo, $subtitle->downloads, $subtitle->idioma);
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
