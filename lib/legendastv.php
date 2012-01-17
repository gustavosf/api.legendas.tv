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
	public static $login;
	public static $auth;
	
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
	
		list($page) = self::request(self::$resource, 'POST', array(
			'txtLegenda'   => $search,
			'int_idioma'   => self::$languages[$lang],
			'selTipo'      => self::$types[$type],
			'btn_buscar.x' => 0,
			'btn_buscar.y' => 0,
		));
		$page = self::parse($page);
	
		return $page;
	}

	private static function parse($page)
	{
		
		$regex = "/gpop\('(.*)','(?P<title_pt>.*)','(?P<filename>.*)','(?P<cds>.*)','(?P<fps>.*)','(?P<size>.*)','(?P<downloads>.*)',.*,'(?P<submited>.*)'\).*abredown\('(?P<id>.*)'\)/";
		preg_match_all($regex, $page, $match);
		
		$parsed = array();
		foreach ($match[0] as $key => $m)
		{
			$parsed[] = new Legenda(array(
				'title' => $match[1][$key],
				'title_pt' => $match[2][$key],
				'filename' => $match[3][$key],
				'cds' => $match[4][$key],
				'fps' => $match[5][$key],
				'size' => $match[6][$key],
				'downloads' => $match[7][$key],
				'submited' => $match[8][$key],
				'id' => $match[9][$key]
			));
		}
				
		return $parsed;
		
	}

	/**
	 * Efetua uma requisição ao site do Legendas.TV
	 *
	 * @param  string
	 * @param  string GET (default) ou POST
	 * @param  array  Query a ser enviada por post 
	 * @return array  Array com o Conteúdo da página, info do curl e header
	 */
	public static function request($url, $method = 'GET', $params = array())
	{
		/* Inicializa o cookie. Pegaremos o sessid depois da primeira consulta */
		static $cookie;
		if ($cookie === null) $cookie = 'Login='.self::$login.';Auth='.self::$auth;

		echo "loading {$url}\n";
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_COOKIESESSION, true);
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HEADER, true); 
		curl_setopt($ch, CURLOPT_POST, $method == 'POST' ? count($params) : false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $method == 'POST' ? http_build_query($params) : false);
		$content = curl_exec($ch);
		$info    = curl_getinfo($ch);

		preg_match('/^(.*?)\r\n\r\n(.*?)$/msU', $content, $match);
		$header = $match[1];
		$content = $match[2];

		/* Armazenamos o PHPSESSID no cookie */
		if (preg_match('/(PHPSESSID=.*?);/', $header, $match))
		{
			$cookie = 'Login='.self::$login.';Auth='.self::$auth.';'.$match[1];
		}

		curl_close($ch);
		return array($content, $info, $header);
	}
}

class Legenda {
	
	private $data = array();

	public function __construct($data)
	{
		$this->data = $data;
	}

	/**
	 * Efetua a requisição de um arquivo ao servidor
	 *
	 * @param  string
	 * @param  bool    true se quer apenas o link para o arquivo
	 * @return string  Arquivo ou link para o arquivo
	 */
	public function download($filename = null, $onlyLink = false)
	{
		list($file, $info) = LegendasTV::request("http://legendas.tv/info.php?c=1&d={$this->id}");
		
		if (!$onlyLink)
		{
			if ($filename === null) $filename = basename($info['url']);
			$this->data['download_link'] = $info['url'];
			file_put_contents($filename, $file);
			return true;
		}
		else {
			return $info['url'];
		}
	}

	public function __get($prop)
	{
		if ($prop == 'download_link' and !isset($this->data['download_link']))
		{
			$this->data['download_link'] = $this->download(null, true);
		}

		if ( ! isset($this->data[$prop]))
		{
			throw new InvalidArgumentException;
		}

		return $this->data[$prop];
	}

}