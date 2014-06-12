<?php

/**
 * Classe para pesquisa e download de legendas do Legendas.tv
 *
 * Uso (temporariamente):
 *   LegendasTV::search('The Big Bang Theory s05e01');
 *   LegendasTV::search('The Big Bang Theory s05e01', 'Inglês');
 */
class LegendasTV {

	/**
	 * Página para a busca das legendas
	 * Link é montado através da combinação $resource/%1/%2/%3 onde:
	 * %1 = Termo da busca
	 * %2 = Código da linguagem
	 * %3 = Tipo de resultados esperados (todos, packs ou destaques apenas)
	 */
	private static $resource = 'http://legendas.tv/util/carrega_legendas_busca/%s/%s/%s';

	/**
	 * Tradução para as diferentes linguagens que podem ser pesquisadas
	 */
	protected static $languages = array(
		'Qualquer idioma' => '-', # default
		'Português-BR' => 1, 'ptbr' => 1,
		'Inglês' => 2, 'en' => 2,
		'Espanhol' => 3, 'es' => 3,
		'Português-PT' => 10, 'pt' => 10,
		'Alemão' => 5, 'gr' => 5,
		'Árabe' => 11, 'ar' => 11,
		'Búlgaro' => 15, 'bl' => 15,
		'Checo' => 12, 'ck' => 12,
		'Chinês' => 13, 'ch' => 13,
		'Coreano' => 14, 'kr' => 14,
		'Dinamarquês' => 7, 'dn' => 7,
		'Francês' => 4, 'fr' => 4,
		'Italiano' => 16, 'it' => 16,
		'Japonês' => 6, 'jp' => 6,
		'Norueguês' => 8, 'nr' => 8,
		'Polonês' => 17, 'pl' => 17,
		'Sueco' => 9, 'sw' => 9,
	);

	/**
	 * Tipos de legendas para pesquisa
	 */
	protected static $types = array(
		'Todos' => '-',
		'Pack' => 'p',
		'Destaque' =>'d',
	);

	/**
	 * Efetua uma busca por legendas no site do legendas.tv
	 * @param  string  O conteúdo da busca
	 * @param  string  A linǵuagem da legenda
	 * @return array
	 * @todo   Rolar a oaginação nos resultados da busca
	 *         Retornar uma coleção de legendas, não um array, com métodos
	 *         para ordenar por campos como por exemplo, destaque ou downloads
	 */
	public static function search($search, $lang = 'Qualquer idioma', $type = 'Todos')
	{
		if (!isset(self::$languages[$lang]))
		{
			throw new Exception('Idioma inválido');
		}

		/** funcionamento antigo do sistema (com post). Migrou para GET
		// list($page) = self::request(self::$resource, true, array(
		// 	'termo'     => $search,
		// 	'page'      => 1,
		// 	'id_filme'  => null,
		// 	'id_idioma' => self::$languages[$lang],
		// ));
		*/
		$link = sprintf(self::$resource, urlencode($search), self::$languages[$lang], self::$types[$type]);

		list($page) = self::request($link, true);
		$subtitles = self::parse($page);
		return $subtitles;
	}

	/**
	 * Efetua o parse de uma página de listagem de legendas
	 * @param  string
	 * @return array  Todas as legendas identificadas
	 * @todo   Centralizar o parse de outras páginas aqui também.
	*/
	private static function parse($page)
	{
		$regex = '/div class="(.*?)">.*?<a href="(.*?)">(.*?)<.*?p class="data">(\d+?) downloads, nota (\d+?), enviado por .*?>(.*?)<\/a> em (.*?) <\/p>.*?<.*?alt="(.*?)".*?<\/div>/';
		preg_match_all($regex, $page, $match);

		$parsed = array();
		foreach ($match[0] as $key => $m)
		{
			$id = explode('/', $match[2][$key]);
			$parsed[] = new Legenda(array(
				'destaque'  => $match[1][$key] == 'destaque',
				'id'        => $id[2],
				'link'      => $match[2][$key],
				'arquivo'   => $match[3][$key],
				'downloads' => $match[4][$key],
				'nota'      => $match[5][$key],
				'uploader'  => $match[6][$key],
				'data'      => $match[7][$key],
				'idioma'    => $match[8][$key],
			));
		}

		return $parsed;
	}

	/**
	 * Efetua uma requisição ao site do Legendas.TV
	 * @param  string
	 * @param  string GET (default) ou POST
	 * @param  array  Query a ser enviada por post
	 * @return array  Array com o Conteúdo da página, info do curl e header
	 */
	public static function request($url, $xml_http_request = false, $params = array())
	{
		$query = array_filter(array_map(function($k, $s) {
			return $s ? $k.':'.urlencode($s) : null;
		}, array_keys($params), $params));
		$url = $url.implode('/', $query);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if ($xml_http_request) curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest'));
		$content = curl_exec($ch);

		preg_match('/^(.*?)\r\n\r\n(.*?)$/msU', $content, $match);
		$header = $match[1];
		$content = $match[2];
		$info = curl_getinfo($ch);

		curl_close($ch);
		return array($content, $info, $header);
	}
}

class Legenda {

	/**
	 * Dados gerais da legenda
	 */
	private $data = array();

	public function __construct($data)
	{
		$this->data = $data;
	}

	/**
	 * Efetua a requisição de um arquivo ao servidor
	 * @param  string
	 * @return string  Arquivo ou link para o arquivo
	 */
	public function download($filename = null)
	{
		// list($file, $info, $header) = LegendasTV::request("http://legendas.tv/pages/downloadarquivo/{$this->id}");
		list($file, $info, $header) = LegendasTV::request("http://legendas.tv/downloadarquivo/{$this->id}");
		if ($filename === null)
		{
			/** Antigo funcionamento. Agora não retorna mais o filename no header
			// preg_match('/filename="(.*?)"/', $header, $filename);
			*/
			preg_match('/Location: http:\/\/f\.legendas\.tv\/l\/(.*)/', $header, $filename);

			// O formato abaixo é o nome de retorno do arquivo do legendas.tv, que não diz muita coisa
			$filename = trim($filename[1]);

			// Alteramos para o nome de arquivo refletir o nome completo da legenda
			$filename = $this->data['arquivo'].substr($filename, strrpos($filename, '.'));
		}
		file_put_contents($filename, $file);
		return $filename;
	}

	/**
	 * Método mágico para retornar informações da Legenda
	 * @param  string
	 * @return mixed
	 * @todo   Buscar informações extras por demanda através do link
	 *         info.php sem o parâmetro c
	 */
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