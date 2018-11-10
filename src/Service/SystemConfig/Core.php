<?php

namespace Peak\Service\SystemConfig;

use Illuminate\Support\Facades\Cache;

class Core
{

	private static $key;

	/**
	 * initiate 初始化
	 * */
	static function init ($file='system')
	{
		self::$key = $file;
		return static::class;
	}


	/**
	 * 获取配置数据
	 * */
	private static function dat()
	{
		return config(self::$key.'.dat');
	}


	/**
	 * 分类、分组、参数是否合法存在
	 * test if the category, group, param valid
	 * */

	private static function is_category_valid ($category) {
		return $category && array_key_exists($category, self::dat());
	}

	private static function is_group_valid ($category, $group) {
		return self::is_category_valid($category) && $group && array_key_exists($group, self::dat()[$category]);
	}

	private static function is_param_valid ($category, $group, $param) {
		return self::is_group_valid($category, $group) && $param && array_key_exists($param, self::dat()[$category][$group]);
	}



	// 从数据库获取数据
	private static function get_from_db ($category, $group=null, $param=null)
	{

		if ( !self::is_category_valid($category)) return;

		$qry = Model::where('category', $category);

		if ($group) {
			is_array($group) ? $qry->whereIn('group', $group) : $qry->where('group', $group);
			if ($param) {
				$qry->where('param', $param);
			}
		}

		$qry = $qry->orderBy('group')->get();

		return $qry->toArray();
	}



	/**
	 * category list or param list in group of the given category (分类列表，或指定分类的参数列表)
	 * @param category string|null  return category list when category is null, otherwise return array dat
	 *
	 * */
	public function ls ($category=null)
	{
		if ($category) {
			if (!is_string($category)||!self::is_category_valid($category)) return;

			$dat = self::dat()[$category];

			$qry = self::get_from_db($category, array_keys(self::dat()));

			foreach ($qry as &$row) {
				if (self::is_param_valid($row['category'], $row['group'], $row['param'])) {
					$dat[$row['group']][$row['param']]['val'] = $row['val'];
				}
			}

			return $dat;
		}

		return array_keys(self::dat());
	}




	/**
	 * save the sys config data in db # 存储数据到数据表
	 * @param $category string, category
	 * @param $group string, group
	 * @param $param array, key=>val type, the key is the field "param". # 键值对的形式，其中键对应param字段.
	 * */
	public function save ($category, $group, array $param)
	{
		if (!$param) return;

		foreach ($param as $key=>&$val) {

			Cache::forget(self::cache_key($category, $group, $key));
			$val = [
				'category' => $category,
				'group' => $group,
				'param' => $key,
				'val' => $val,
			];
		}

		\DB::transaction (function () use (&$category, &$group, &$param) {
			Model::where([
				'category' => $category,
				'group' => $group,
			])->delete();
			Model::insert($param);
		});

	}



	# 缓存部分

	/**
	 * set the cache name
	 * */
	private static function cache_key ($category, $group, $param)
	{
		return join('.', [
			config(self::$key.'.key'),
			$category,
			$group,
			$param,
		]);
	}



	/**
	 * get the param value from cache (从缓存中获取参数)
	 * @param $param string. string is chain style with "."  ### 用"."符号作为链式调用分隔符
	 * */
	public function get ($param)
	{

		list($category, $group, $param) = is_array($param) ? $param :explode('.', $param);
		if (self::is_param_valid($category, $group, $param) ) {
			return Cache::remember (
				self::cache_key($category, $group, $param),
				config(self::$key.'.exp'),
				function () use ($category, $group, $param) {
					$dat = self::get_from_db($category, $group, $param);
					if ($dat) {
						return $dat[0]['val'];
					}
				}
			);
		}
	}


}
