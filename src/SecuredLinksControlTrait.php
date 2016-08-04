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

trait SecuredLinksControlTrait
{

	/**
	 * For @secured annotated signal handler methods checks if URL parameters has not been changed
	 *
	 * @param  string $signal
	 * @throws Nette\Application\UI\BadSignalException if there is no handler method or the security token does not match
	 * @throws \LogicException if there is no redirect in a secured signal
	 */
	public function signalReceived($signal)
	{
		$method = $this->formatSignalMethod($signal);
		$secured = FALSE;

		if (method_exists($this, $method)) {
			$reflection = new Nette\Reflection\Method($this, $method);
			$secured = $reflection->hasAnnotation('secured');
			if ($secured) {
				$params = array($this->getUniqueId());
				if ($this->params) {
					foreach ($reflection->getParameters() as $param) {
						if ($param->isOptional()) {
							continue;
						}
						if (isset($this->params[$param->name])) {
							$params[$param->name] = $this->params[$param->name];
						}
					}
				}
				if (!isset($this->params['_sec']) || $this->params['_sec'] !== $this->getPresenter()->getCsrfToken(get_class($this), $method, $params)) {
					throw new Nette\Application\UI\BadSignalException("Invalid security token for signal '$signal' in class " . get_class($this) . ".");
				}
			}
		}

		parent::signalReceived($signal);

		if ($secured && !$this->getPresenter()->isAjax()) {
			throw new \LogicException("Secured signal '$signal' did not redirect. Possible csrf-token reveal by http referer header. Please redirect in $method().");
		}
	}

}
