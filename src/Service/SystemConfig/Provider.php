<?php

namespace Peak\Service\SystemConfig;

use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{

	public function boot()
	{
		$this->migrate();
		$this->config();
	}

	protected function migrate()
	{

//		return $this->loadMigrationsFrom(__DIR__.'/../database/migrations');


		$this->publishes([
			__DIR__.'/publish/migration.php' => database_path('migrations/9PeakLaravelServiceSystemConfig.php'),
		]/*, 'passport-migrations'*/);
	}

	protected function config()
	{
		$this->publishes(
			[
				__DIR__.'/public/config.php' => config_path('system.php'),
			],
			'config'
		);

//		$path = realpath(__DIR__.'/../config/config.php');
//		$this->publishes([$path => config_path('storage.php')], 'config');
//		$this->mergeConfigFrom($path, 'storage');
	}


}