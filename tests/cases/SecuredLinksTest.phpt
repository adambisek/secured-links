<?php

use Nette\Application\Request;
use Nette\Application\Routers\SimpleRouter;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use AdamBisek\SecuredLinksControlTrait;
use AdamBisek\SecuredLinksPresenterTrait;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestControl extends Control
{
	use SecuredLinksControlTrait;
	/** @secured */
	public function handlePay($amount = 0)
	{
	}
}


class TestPresenter extends Presenter
{
	use SecuredLinksPresenterTrait;
	protected function startup()
	{
		parent::startup();
		$this['mycontrol'] = new TestControl;
	}
	public function renderDefault()
	{
		$this->terminate();
	}
	/** @secured */
	public function handlePay($amount = 0)
	{
		$this->redirect("default");
	}
	/** @secured */
	public function handlePay2($amount)
	{
		$this->redirect("default");
	}
	/** @secured */
	public function handleList(array $sections)
	{
	}
	public function handleUnsecured($amount)
	{
	}
}


$url = new UrlScript('http://localhost/index.php');
$url->setScriptPath('/index.php');

$httpRequest = new HttpRequest($url);
$httpResponse = new Response();

$router = new SimpleRouter();
$request = new Request('Test', HttpRequest::GET, array());

$sessionSection = Mockery::mock('alias:Nette\Http\SessionSection');
$sessionSection->token = 'abcd';


// 1. Test link creating
$session = Mockery::mock('Nette\Http\Session');
$session->shouldReceive('getSection')->with('Nextras.Application.UI.SecuredLinksPresenterTrait')->andReturn($sessionSection);
$session->shouldReceive('getId')->times(9)->andReturn('session_id_1');

$presenter = new TestPresenter();
$presenter->autoCanonicalize = FALSE;
$presenter->injectPrimary(NULL, NULL, $router, $httpRequest, $httpResponse, $session, NULL);
$presenter->run($request);

Assert::same( '/index.php?action=default&presenter=Test', $presenter->link('default') ); // obligate action - without securing
Assert::same( '/index.php?action=default&do=unsecured&presenter=Test', $presenter->link('unsecured!') ); // obligate signal - without securing
Assert::same( '/index.php?_sec=15b97390&action=default&do=pay&presenter=Test', $presenter->link('pay!') );
Assert::same( '/index.php?_sec=15b97390&action=default&do=pay&presenter=Test', $presenter->link('pay!') );
Assert::same( '/index.php?_sec=15b97390&amount=200&action=default&do=pay&presenter=Test', $presenter->link('pay!', [200]) );
Assert::same( '/index.php?_sec=1292dd35&amount=100&action=default&do=pay2&presenter=Test', $presenter->link('pay2!', [100]) );
Assert::same( '/index.php?_sec=6c9cc123&amount=200&action=default&do=pay2&presenter=Test', $presenter->link('pay2!', [200]) );
Assert::same( '/index.php?_sec=52c37d1f&sections[0]=a&sections[1]=b&action=default&do=list&presenter=Test', urldecode($presenter->link('list!', [['a', 'b']])) );
Assert::same( '/index.php?_sec=a0f08fca&sections[0]=a&sections[1]=c&action=default&do=list&presenter=Test', urldecode($presenter->link('list!', [['a', 'c']])) );

Assert::same( '/index.php?mycontrol-_sec=3370fd04&action=default&do=mycontrol-pay&presenter=Test', $presenter['mycontrol']->link('pay') );
Assert::same( '/index.php?mycontrol-_sec=3370fd04&mycontrol-amount=200&action=default&do=mycontrol-pay&presenter=Test', $presenter['mycontrol']->link('pay', [200]) );


$session->shouldReceive('getId')->times(2)->andReturn('session_id_2');

Assert::same( '/index.php?_sec=ea0c9a62&sections[0]=a&sections[1]=b&action=default&do=list&presenter=Test', urldecode($presenter->link('list!', [['a', 'b']])) );
Assert::same( '/index.php?_sec=a4ddd9f8&sections[0]=a&sections[1]=c&action=default&do=list&presenter=Test', urldecode($presenter->link('list!', [['a', 'c']])) );


// 2. Test signal receiving
$session = Mockery::mock('Nette\Http\Session');
$session->shouldReceive('getSection')->with('Nextras.Application.UI.SecuredLinksPresenterTrait')->andReturn($sessionSection);
$session->shouldReceive('getId')->times(2)->andReturn('session_id_1');

// a. without token
$request = new Request('Test', HttpRequest::GET, array(
	'do' => 'pay',
));
$presenter = new TestPresenter();
$presenter->autoCanonicalize = FALSE;
$presenter->injectPrimary(NULL, NULL, $router, $httpRequest, $httpResponse, $session, NULL);
Assert::exception(function() use ($presenter, $request) {
	$presenter->run($request);
}, 'Nette\Application\UI\BadSignalException', 'Invalid security token for signal \'pay\' in class TestPresenter.');

// b. with valid token and required parameter
$request = new Request('Test', HttpRequest::GET, array(
	'do' => 'pay2',
	'amount' => 100,
	'_sec' => '1292dd35',
));
$presenter = new TestPresenter();
$presenter->autoCanonicalize = FALSE;
$presenter->injectPrimary(NULL, NULL, $router, $httpRequest, $httpResponse, $session, NULL);
$presenter->run($request);

// c. without redirect
$request = new Request('Test', HttpRequest::GET, array(
	'do' => 'list',
	'sections' => ['a', 'b'],
	'_sec' => '52c37d1f',
));
$presenter = new TestPresenter();
$presenter->autoCanonicalize = FALSE;
$presenter->injectPrimary(NULL, NULL, $router, $httpRequest, $httpResponse, $session, NULL);
Assert::exception(function() use ($presenter, $request) {
	$presenter->run($request);
}, 'LogicException', 'Secured signal \'list\' did not redirect. Possible csrf-token reveal by http referer header. Please redirect in handlelist().');


Mockery::close();
