<?php

namespace Peak\Service\SystemConfig;

use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider
{

	public function boot()
	{
		// 创建迁移
		$this->publishes([
			__DIR__.'/publish/migration.php' => database_path('migrations/2018_07_31_170327_create_table_system.php'),
		]);

		// 创建config文件
		$this->publishes(
			[
				__DIR__.'/publish/config.php' => config_path('system.php'),
			],
			'config'
		);




	}


	public function register()
	{
		$this->app->bind('Peak\Service\SystemConfig\Core', function(){
			return new \Peak\Service\SystemConfig\Core();
		});
	}



}