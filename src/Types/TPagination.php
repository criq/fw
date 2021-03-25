<?php

namespace Katu\Types;

class TPagination
{
	const DEFAULT_COPY_NEXT = 'Next';
	const DEFAULT_COPY_PREV = 'Previous';
	const DEFAULT_PER_PAGE = 50;
	const DEFAULT_QUERY_PARAM = 'page';
	const PAGINATION_ALL_PAGES_LIMIT = 10;
	const PAGINATION_CURRENT_OFFSET = 3;
	const PAGINATION_ENDS_OFFSET = 0;

	public $page;
	public $pages;
	public $perPage;
	public $total;

	public function __construct(int $total, int $perPage, int $page)
	{
		$this->setPage((int)$page);
		$this->setPerPage((int)$perPage);
		$this->setTotal((int)$total);
		$this->setPages((int)ceil($total / $perPage));
	}

	public function setTotal(int $total)
	{
		if ($total < 0) {
			throw new \Katu\Exceptions\ErrorException("Invalid total.");
		}

		$this->total = $total;
		try {
			$this->setPages((int)ceil($total / $this->getPerPage()));
		} catch (\Throwable $e) {
			// Nevermind.
		}

		return $this;
	}

	public function getTotal()
	{
		return $this->total;
	}

	public function setPerPage(int $perPage)
	{
		if ((int)$perPage < 1) {
			throw new \Katu\Exceptions\ErrorException("Invalid per page.");
		}

		$this->perPage = $perPage;

		return $this;
	}

	public function getPerPage()
	{
		return $this->perPage;
	}

	public function setPage(int $page)
	{
		if ((int)$page < 1) {
			throw new \Katu\Exceptions\ErrorException("Invalid page.");
		}

		$this->page = $page;

		return $this;
	}

	public function getPage()
	{
		return $this->page;
	}

	public function setPages(int $pages)
	{
		$this->pages = $pages;

		return $this;
	}

	public function getPages()
	{
		return $this->pages;
	}

	public static function getAppQueryParam()
	{
		try {
			return \Katu\Config\Config::get('pagination', 'queryParam');
		} catch (\Katu\Exceptions\MissingConfigException $e) {
			return static::DEFAULT_QUERY_PARAM;
		}
	}

	public static function getAppPerPage()
	{
		try {
			return \Katu\Config\Config::get('pagination', 'perPage');
		} catch (\Katu\Exceptions\MissingConfigException $e) {
			return static::DEFAULT_PER_PAGE;
		}
	}

	public static function getRequestPageExpression(\Slim\Http\Request $request, int $perPage = null)
	{
		return new \Sexy\Page(static::getPageFromRequest($request), $perPage ?: static::getAppPerPage());
	}

	public static function getPageFromRequest(\Slim\Http\Request $request) : int
	{
		$params = $request->getParams();
		if (!($params[static::getAppQueryParam()] ?? null)) {
			return 1;
		}

		if ($params[static::getAppQueryParam()] < 1) {
			return 1;
		}

		return (int)$params[static::getAppQueryParam()];
	}

	public function getMinPage()
	{
		return 1;
	}

	public function getMaxPage()
	{
		return (int) (ceil($this->total / $this->perPage));
	}

	public function getTemplatePages()
	{
		$options = [
			'allPagesLimit' => static::PAGINATION_ALL_PAGES_LIMIT,
			'currentOffset' => static::PAGINATION_CURRENT_OFFSET,
			'endsOffset' => static::PAGINATION_ENDS_OFFSET,
		];

		if (!$this->total) {
			return [];
		}

		if ($this->getMaxPage() <= $options['allPagesLimit']) {
			return range($this->getMinPage(), $this->getMaxPage());
		}

		$pages = [];
		$pages = array_merge($pages, range($this->getMinPage(), $this->getMinPage() + $options['endsOffset']));
		$pages = array_merge($pages, range($this->page - $options['currentOffset'], $this->page + $options['currentOffset']));
		$pages = array_merge($pages, range($this->getMaxPage() - $options['endsOffset'], $this->getMaxPage()));

		$pages = array_unique(array_filter($pages, function ($i) {
			return ($i > 0 && $i <= $this->getMaxPage()) ? true : false;
		}));
		natsort($pages);
		$pages = array_values($pages);

		return $pages;
	}

	public function getPageURL($url, $page)
	{
		$url = new \Katu\Types\TURL($url);
		$url->removeQueryParam(static::getAppQueryParam());

		if ($page > 1) {
			$url->addQueryParam(static::getAppQueryParam(), $page);
		}

		return $url;
	}

	public function getCopy()
	{
		return [
			'prev' => \Katu\Config\Config::getWithDefault('pagination', 'copy', 'prev', static::DEFAULT_COPY_PREV),
			'next' => \Katu\Config\Config::getWithDefault('pagination', 'copy', 'next', static::DEFAULT_COPY_NEXT),
		];
	}

	public function getResponseArray()
	{
		return [
			'total' => $this->getTotal(),
			'pages' => $this->getPages(),
			'perPage' => $this->getPerPage(),
			'page' => $this->getPage(),
		];
	}
}
