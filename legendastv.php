<?php

/**
 * Classe para pesquisa e download de legendas do Legendas.tv
 *
 * Uso (temporariamente):
 *   LegendasTV::config($meu_login, $minha_senha);
 *   LegendasTV::search('The Big Bang Theory s05e01');
 */
class LegendasTV {

	/**
	 * Configuração de acesso ao site
	 * Você pode preencher estes dois campos com valores predefinidos para evitar a 
	 * necessidade do LegendasTV::config
	 * O campo auth deve ser preenchido com a hash md5 da senha de acesso ao site
	 * @see config
	 */
	private static $login;
	private static $auth;
	
	private static $resource = 'http://legendas.tv/index.php?opcao=buscarlegenda';

	/**
	 * Tradução para as diferentes linguagens que podem ser pesquisadas
	 */
	protected static $languages = array(
		'pt-br' => 1,
		'pt'    => 10,
		'en'    => 2,
		'es'    => 3,
		'other' => 100,
		'all'   => 99,
	);

	/** 
	 * Tradução para os tipos de pesquisa no site
	 */
	protected static $types = array(
		'release' => 1,
		'filme'   => 2,
		'usuario' => 3,
	);

	/**
	 * Configura as credenciais do usuário para acesso ao site
	 * @param  string
	 * @param  string
	 */
	public static function config($login, $senha)
	{
		self::$login = $login;
		self::$auth = md5($senha);
	}


	public static function search($search, $type = 'release', $lang = 'pt-br')
	{
		if (!isset(self::$types[$type]))
		{
			throw new Exception('Tipo de legenda inválido');
		}
		if (!isset(self::$languages[$lang]))
		{
			throw new Exception('Idioma inválido');
		}
	
		$page = self::request(array(
			'txtLegenda'   => $search,
			'int_idioma'   => self::$languages[$lang],
			'selTipo'      => self::$types[$type],
			'btn_buscar.x' => 0,
			'btn_buscar.y' => 0,
		));

		return $page;
	}

	public static function request(Array $content)
	{
		$content = http_build_query($content);
		$content_length = strlen($content);
		$cookie  = http_build_query(array(
			'Login' => self::$login,
			'Auth'  => self::$auth,
		), '', ';');

		$opts = array('http' => array(
			'method'  => 'POST',
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n".
			             "Connection: close\r\n".
			             "Content-Length: {$content_length}\r\n".
			             "Cookie: {$cookie}\r\n",
			'content' => $content,
		));
		$context = stream_context_create($opts);
		$result = file_get_contents(self::$resource, false, $context);

		return $result;
	}
}