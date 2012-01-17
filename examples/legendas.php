#!/usr/bin/php
<?php

/* Exemplo de shellscript para download de legendas usando a classe LegendasTV */

require '../lib/legendastv.php';
LegendasTV::config('seu_usuario', 'sua_senha');

/* Pega a consulta pelo argv */
array_shift($argv); $search = implode(' ', $argv);

/**
 * Função para emular o famoso readln :P
 * @return  string  O que o cara digitou no console
 */
function readln()
{
	while (false !== ($ln = fgets(STDIN)))
		return $ln;
}

/* Começa a treta :D */

$subtitles = LegendasTV::search($search);

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

echo "Baixando {$subtitle->filename}...\n";
$subtitle->download();
echo "Arquivo ".basename($subtitle->download_link)." baixado!\n";

?>