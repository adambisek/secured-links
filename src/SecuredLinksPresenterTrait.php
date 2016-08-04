<?php

/**
 * This file is part of the Nextras community extensions of Nette Framework
 *
 * @license MIT
 * @author Jan Skrasek
 * @author Adam Bisek <adam.bisek@gmail.com>
 */
namespace AdamBisek;

use Nette;

trait SecuredLinksPresenterTrait
{

	use SecuredLinksControlTrait;

	/**
	 * Request/URL factory.
	 * @param  Component  base
	 * @param  string   destination in format "[//] [[[module:]presenter:]action | signal! | this] [#fragment]"
	 * @param  array    array of arguments
	 * @param  string   forward|redirect|link
	 * @return string   URL
	 * @throws InvalidLinkException
	 * @internal
	 */
	protected function createRequest($component, $destination, array $args, $mode)
	{
		if (!$component instanceof self || substr($destination, -1) === '!') {
			// check if signal must be secured
			$signal = strtr(rtrim($destination, '!'), ':', '-');
			$method = $component->formatSignalMethod($signal);
			$signalMethodReflection = new Nette\Reflection\Method($component, $method);
			if (!$signalMethodReflection->hasAnnotation('secured')) {
				goto parent;
			}
			// gather args, create hash and append to args
			$namedArgs = $args;
			self::argsToParams($this, $method, $namedArgs); // convert indexed args to named args
			$protectedParams = array($component->getUniqueId());
			foreach ($signalMethodReflection->getParameters() as $param) {
				if ($param->isOptional()) {
					continue;
				}
				if (isset($namedArgs[$component->getParameterId($param->name)])) {
					$protectedParams[$param->name] = $namedArgs[$component->getParameterId($param->name)];
				}
			}
			$args['_sec'] = $this->getCsrfToken(get_class($component), $method, $protectedParams);
		}

		parent:
		return parent::createRequest($component, $destination, $args, $mode);
	}


	/**
	 * Returns unique token for method and params
	 * @param  string $control
	 * @param  string $method
	 * @param  array $params
	 * @return string
	 */
	public function getCsrfToken($control, $method, $params)
	{
		$session = $this->getSession('Nextras.Application.UI.SecuredLinksPresenterTrait');
		if (!isset($session->token)) {
			$session->token = Nette\Utils\Random::generate();
		}

		$params = Nette\Utils\Arrays::flatten($params);
		$params = implode('|', array_keys($params)) . '|' . implode('|', array_values($params));
		return substr(md5($control . $method . $params . $session->token . $this->getSession()->getId()), 0, 8);
	}

}
