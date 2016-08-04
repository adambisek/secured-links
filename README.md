## Nextras\SecuredLinks

[![Build Status](https://travis-ci.org/adambisek/secured-links.svg?branch=master)](https://travis-ci.org/adambisek/secured-links)
[![Coverage Status](https://coveralls.io/repos/github/adambisek/secured-links/badge.svg?branch=master)](https://coveralls.io/github/adambisek/secured-links?branch=master)
[![Stable version](http://img.shields.io/packagist/v/adambisek/secured-links.svg?style=flat)](https://packagist.org/packages/adambisek/secured-links)

**SecuredLinksTrait** creates secured signal links.
**PHP 5.4+ ONLY**

forked from nextras/secured-links

## Installation

The best way to install is using [Composer](http://getcomposer.org/):

```sh
$ composer require adambisek/secured-links
```

## Usage of SecuredLinksTrait

```php
abstract class BasePrenseter extends Nette\Application\UI\Presenter
{
	use Nextras\Application\UI\SecuredLinksPresenterTrait;
}


class MyPresenter extends BasePresenter
{
	/**
	 * @secured
	 */
	public function handleDelete($id)
	{
	}
}


abstract class BaseControl extends Nette\Application\UI\Control
{
	use Nextras\Application\UI\SecuredLinksControlTrait;
}


class MyControl extends BaseControl
{
	/**
	 * @secured
	 */
	public function handleDelete($id)
	{
	}
}
```
