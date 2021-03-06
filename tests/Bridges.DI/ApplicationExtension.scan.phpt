<?php

declare(strict_types=1);

use Nette\Bridges\ApplicationDI\ApplicationExtension;
use Nette\DI;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/files/MyPresenter.php';


test(function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('application', new ApplicationExtension);

	$builder = $compiler->getContainerBuilder();
	$builder->addDefinition('myRouter')->setFactory(Nette\Application\Routers\SimpleRouter::class);
	$builder->addDefinition('myHttpRequest')->setFactory(Nette\Http\Request::class, [new DI\Statement(Nette\Http\UrlScript::class)]);
	$builder->addDefinition('myHttpResponse')->setFactory(Nette\Http\Response::class);
	$code = $compiler->setClassName('Container1')->compile();
	eval($code);

	$container = new Container1;
	$tags = $container->findByTag('nette.presenter');
	Assert::count(1, array_keys($tags, NetteModule\ErrorPresenter::class, true));
	Assert::count(1, array_keys($tags, NetteModule\MicroPresenter::class, true));
	Assert::count(0, array_keys($tags, Nette\Application\UI\Presenter::class, true));
});


test(function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('application', new ApplicationExtension);

	$builder = $compiler->getContainerBuilder();
	$builder->addDefinition('myRouter')->setFactory(Nette\Application\Routers\SimpleRouter::class);
	$builder->addDefinition('myHttpRequest')->setFactory(Nette\Http\Request::class, [new DI\Statement(Nette\Http\UrlScript::class)]);
	$builder->addDefinition('myHttpResponse')->setFactory(Nette\Http\Response::class);
	$code = $compiler->addConfig([
		'application' => [
			'scanDirs' => [__DIR__ . '/files'],
		],
	])->setClassName('Container2')->compile();
	eval($code);

	$container = new Container2;
	$tags = $container->findByTag('nette.presenter');
	Assert::count(1, array_keys($tags, 'BasePresenter', true));
	Assert::count(1, array_keys($tags, 'Presenter1', true));
	Assert::count(1, array_keys($tags, 'Presenter2', true));
});


test(function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('application', new ApplicationExtension(false, [__DIR__ . '/files']));

	$builder = $compiler->getContainerBuilder();
	$builder->addDefinition('myRouter')->setFactory(Nette\Application\Routers\SimpleRouter::class);
	$builder->addDefinition('myHttpRequest')->setFactory(Nette\Http\Request::class, [new DI\Statement(Nette\Http\UrlScript::class)]);
	$builder->addDefinition('myHttpResponse')->setFactory(Nette\Http\Response::class);
	$loader = new DI\Config\Loader;
	$config = $loader->load(Tester\FileMock::create('
	services:
		-
			factory: Presenter1
			setup:
				- setView(test)
	', 'neon'));
	$code = $compiler->addConfig($config)->setClassName('Container3')->compile();
	eval($code);

	$container = new Container3;
	$tags = $container->findByTag('nette.presenter');
	Assert::count(1, array_keys($tags, 'BasePresenter', true));
	Assert::count(1, array_keys($tags, 'Presenter1', true));
	Assert::count(1, array_keys($tags, 'Presenter2', true));

	$tmp = array_keys($tags, 'Presenter1', true);
	Assert::same('test', $container->getService((string) $tmp[0])->getView());
});
