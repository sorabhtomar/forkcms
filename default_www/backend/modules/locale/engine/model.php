<?php

/**
 * In this file we store all generic functions that we will be using in the locale module
 *
 * @package		backend
 * @subpackage	locale
 *
 * @author		Davy Hellemans <davy@netlash.com>
 * @author		Tijs Verkoyen <tijs@sumocoders.be>
 * @author		Dieter Vanden Eynde <dieter@dieterve.be>
 * @author		Lowie Benoot <lowie@netlash.com>
 * @since		2.0
 */
class BackendLocaleModel
{
	/**
	 * Build the language files
	 *
	 * @return	void
	 * @param	string $language		The language to build the locale-file for.
	 * @param	string $application		The application to build the locale-file for.
	 */
	public static function buildCache($language, $application)
	{
		// get db
		$db = BackendModel::getDB();

		// get types
		$types = $db->getEnumValues('locale', 'type');

		// get locale for backend
		$locale = (array) $db->getRecords('SELECT type, module, name, value
											FROM locale
											WHERE language = ? AND application = ?
											ORDER BY type ASC, name ASC, module ASC',
											array((string) $language, (string) $application));

		// start generating PHP
		$value = '<?php' . "\n\n";
		$value .= '/**' . "\n";
		$value .= ' *' . "\n";
		$value .= ' * This file is generated by Fork CMS, it contains' . "\n";
		$value .= ' * more information about the locale. Do NOT edit.' . "\n";
		$value .= ' * ' . "\n";
		$value .= ' * @author		Fork CMS' . "\n";
		$value .= ' * @generated	' . date('Y-m-d H:i:s') . "\n";
		$value .= ' */' . "\n";
		$value .= "\n";

		// loop types
		foreach($types as $type)
		{
			// default module
			$modules = array('core');

			// continue output
			$value .= "\n";
			$value .= '// init var' . "\n";
			$value .= '$' . $type . ' = array();' . "\n";
			$value .= '$' . $type . '[\'core\'] = array();' . "\n";

			// loop locale
			foreach($locale as $i => $item)
			{
				// types match
				if($item['type'] == $type)
				{
					// new module
					if(!in_array($item['module'], $modules))
					{
						$value .= '$' . $type . '[\'' . $item['module'] . '\'] = array();' . "\n";
						$modules[] = $item['module'];
					}

					// parse
					if($application == 'backend') $value .= '$' . $type . '[\'' . $item['module'] . '\'][\'' . $item['name'] . '\'] = \'' . str_replace('\"', '"', addslashes($item['value'])) . '\';' . "\n";
					else $value .= '$' . $type . '[\'' . $item['name'] . '\'] = \'' . str_replace('\"', '"', addslashes($item['value'])) . '\';' . "\n";

					// unset
					unset($locale[$i]);
				}
			}
		}

		// close php
		$value .= "\n";
		$value .= '?>';

		// store
		SpoonFile::setContent(constant(mb_strtoupper($application) . '_CACHE_PATH') . '/locale/' . $language . '.php', $value);
	}


	/**
	 * Build a query for the URL based on the filter
	 *
	 * @return	array
	 * @param	array $filter	The filter.
	 */
	public static function buildURLQueryByFilter($filter)
	{
		$query = '';

		// loop filter items
		foreach($filter as $key => $value)
		{
			// is it an array?
			if(is_array($value))
			{
				// loop the array
				foreach($value as $v)
				{
					// add to the query
					$query .= '&' . $key . '[]=' . $v;
				}
			}

			// not an array
			else
			{
				// add to the query
				$query .= '&' . $key . '=' . $value;
			}
		}

		return $query;
	}


	/**
	 * Delete (multiple) items from locale
	 *
	 * @return	void
	 * @param	array $ids	The id(s) to delete.
	 */
	public static function delete(array $ids)
	{
		// loop and cast to integers
		foreach($ids as &$id) $id = (int) $id;

		// create an array with an equal amount of questionmarks as ids provided
		$idPlaceHolders = array_fill(0, count($ids), '?');

		// delete records
		BackendModel::getDB(true)->delete('locale', 'id IN (' . implode(', ', $idPlaceHolders) . ')', $ids);

		// rebuild cache
		self::buildCache(BL::getWorkingLanguage(), 'backend');
		self::buildCache(BL::getWorkingLanguage(), 'frontend');
	}


	/**
	 * Does an id exist.
	 *
	 * @return	bool
	 * @param	int $id		The id to check for existence.
	 */
	public static function exists($id)
	{
		return (bool) BackendModel::getDB()->getVar('SELECT COUNT(id)
														FROM locale
														WHERE id = ?',
														array((int) $id));
	}


	/**
	 * Does a locale exists by its name.
	 *
	 * @return	bool
	 * @param	string $name			The name of the locale.
	 * @param	string $type			The type of the locale.
	 * @param	string $module			The module wherin will be searched.
	 * @param	string $language		The language to use.
	 * @param	string $application		The application wherin will be searched.
	 * @param	int[optional] $id		The id to exclude in the check.
	 */
	public static function existsByName($name, $type, $module, $language, $application, $id = null)
	{
		// redefine
		$name = (string) $name;
		$type = (string) $type;
		$module = (string) $module;
		$language = (string) $language;
		$application = (string) $application;
		$id = ($id !== null) ? (int) $id : null;

		// get db
		$db = BackendModel::getDB();

		// return
		if($id !== null) return (bool) $db->getVar('SELECT COUNT(id)
													FROM locale
													WHERE name = ? AND type = ? AND module = ? AND language = ? AND application = ? AND id != ?',
													array($name, $type, $module, $language, $application, $id));

		return (bool) BackendModel::getDB()->getVar('SELECT COUNT(id)
														FROM locale
														WHERE name = ? AND type = ? AND module = ? AND language = ? AND application = ?',
														array($name, $type, $module, $language, $application));
	}


	/**
	 * Get a single item from locale.
	 *
	 * @return	array
	 * @param	int $id		The id of the item to get.
	 */
	public static function get($id)
	{
		return (array) BackendModel::getDB()->getRecord('SELECT * FROM locale WHERE id = ?', array((int) $id));
	}


	/**
	 * Get a locale by its name
	 *
	 * @return	bool
	 * @param	string $name			The name of the locale.
	 * @param	string $type			The type of the locale.
	 * @param	string $module			The module wherin will be searched.
	 * @param	string $language		The language to use.
	 * @param	string $application		The application wherin will be searched.
	 */
	public static function getByName($name, $type, $module, $language, $application)
	{
		// redefine
		$name = (string) $name;
		$type = (string) $type;
		$module = (string) $module;
		$language = (string) $language;
		$application = (string) $application;

		// get db
		$db = BackendModel::getDB();

		return BackendModel::getDB()->getVar('SELECT l.id
											FROM locale AS l
											WHERE name = ? AND type = ? AND module = ? AND language = ? AND application = ?',
											array($name, $type, $module, $language, $application));
	}


	/**
	 * Grab labels found in the backend navigation.
	 *
	 * @return	array
	 * @param	array $items				The items to get the labels from.
	 * @param	array[optional] $labels		An array that will hold the labels.
	 */
	private static function getLabelsFromBackendNavigation(array $items, $labels = array())
	{
		// loop items
		foreach($items as $item)
		{
			// add the label
			$labels[] = $item['label'];

			// any children?
			if(isset($item['children']) && is_array($item['children']))
			{
				// get the labels from the children
				$labels = self::getLabelsFromBackendNavigation($item['children'], $labels);
			}
		}

		// return
		return $labels;
	}


	/**
	 * Get the languages for a multicheckbox.
	 *
	 * @return	array
	 * @param	bool[optional] $includeInterfaceLanguages		Should we also get the interfacelanguages?
	 */
	public static function getLanguagesForMultiCheckbox($includeInterfaceLanguages = false)
	{
		// get working languages
		$aLanguages = BL::getWorkingLanguages();

		// add the interface languages if needed
		if($includeInterfaceLanguages) $aLanguages = array_merge($aLanguages, BL::getInterfaceLanguages());

		// create a new array to redefine the langauges for the multicheckbox
		$languages = array();

		// loop the languages
		foreach($aLanguages as $key => $lang)
		{
			// add to array
			$languages[$key]['value'] = $key;
			$languages[$key]['label'] = $lang;
		}

		return $languages;
	}


	/**
	 * Get the locale that is used in the backend but doesn't exists.
	 *
	 * @return	array
	 * @param	string $language	The language to check.
	 */
	public static function getNonExistingBackendLocale($language)
	{
		// init some vars
		$tree = self::getTree(BACKEND_PATH);
		$modules = BackendModel::getModules(false);

		// search fo the error module
		$key = array_search('error', $modules);

		// remove error module
		if($key !== false) unset($modules[$key]);

		$used = array();
		$navigation = Spoon::get('navigation');

		// get labels from navigation
		$lbl = self::getLabelsFromBackendNavigation($navigation->navigation);
		foreach((array) $lbl as $label) $used['lbl'][$label] = array('files' => array('<small>used in navigation</small>'), 'module_specific' => array());

		// get labels from table
		$lbl = (array) BackendModel::getDB()->getColumn('SELECT label FROM pages_extras');
		foreach((array) $lbl as $label) $used['lbl'][$label] = array('files' => array('<small>used in database</small>'), 'module_specific' => array());

		// loop files
		foreach($tree as $file)
		{
			// grab content
			$content = SpoonFile::getContent($file);

			// process based on extension
			switch(SpoonFile::getExtension($file))
			{
				// javascript file
				case 'js':
					$matches = array();

					// get matches
					preg_match_all('/\{\$(act|err|lbl|msg)(.*)(\|.*)?\}/iU', $content, $matches);

					// any matches?
					if(isset($matches[2]))
					{
						// loop matches
						foreach($matches[2] as $key => $match)
						{
							// set type
							$type = $matches[1][$key];

							// loop modules
							foreach($modules as $module)
							{
								// determine if this is a module specific locale
								if(substr($match, 0, mb_strlen($module)) == SpoonFilter::toCamelCase($module) && mb_strlen($match) > mb_strlen($module))
								{
									// cleanup
									$match = str_replace(SpoonFilter::toCamelCase($module), '', $match);

									// init if needed
									if(!isset($used[$type][$match])) $used[$type][$match] = array('files' => array(), 'module_specific' => array());

									// add module
									$used[$type][$match]['module_specific'][] = $module;
								}
							}

							// init if needed
							if(!isset($used[$match])) $used[$type][$match] = array('files' => array(), 'module_specific' => array());

							// add file
							if(!in_array($file, $used[$type][$match]['files'])) $used[$type][$match]['files'][] = $file;
						}
					}
				break;

				// PHP file
				case 'php':
					$matches = array();
					$matchesURL = array();

					// get matches
					preg_match_all('/(BackendLanguage|BL)::(get(Label|Error|Message)|act|err|lbl|msg)\(\'(.*)\'(.*)?\)/iU', $content, $matches);

					// match errors
					preg_match_all('/&(amp;)?(error|report)=([A-Z0-9-_]+)/i', $content, $matchesURL);

					// any errormessages
					if(!empty($matchesURL[0]))
					{
						// loop matches
						foreach($matchesURL[3] as $key => $match)
						{
							$type = 'lbl';
							if($matchesURL[2][$key] == 'error') $type = 'Error';
							if($matchesURL[2][$key] == 'report') $type = 'Message';

							$matches[0][] = '';
							$matches[1][] = 'BL';
							$matches[2][] = '';
							$matches[3][] = $type;
							$matches[4][] = SpoonFilter::toCamelCase(SpoonFilter::toCamelCase($match, '-'), '_');
							$matches[5][] = '';
						}
					}

					// any matches?
					if(!empty($matches[4]))
					{
						// loop matches
						foreach($matches[4] as $key => $match)
						{
							// set type
							$type = 'lbl';
							if($matches[3][$key] == 'Error' || $matches[2][$key] == 'err') $type = 'err';
							if($matches[3][$key] == 'Message' || $matches[2][$key] == 'msg') $type = 'msg';

							// specific module?
							if(isset($matches[5][$key]) && $matches[5][$key] != '')
							{
								// try to grab the module
								$specificModule = $matches[5][$key];
								$specificModule = trim(str_replace(array(',', '\''), '', $specificModule));

								// not core?
								if($specificModule != 'core')
								{
									// dynamic module
									if($specificModule == '$this->URL->getModule(')
									{
										// init var
										$count = 0;

										// replace
										$modulePath = str_replace(BACKEND_MODULES_PATH, '', $file, $count);

										// validate
										if($count == 1)
										{
											// split into chunks
											$chunks = (array) explode('/', trim($modulePath, '/'));

											// set specific module
											if(isset($chunks[0])) $specificModule = $chunks[0];

											// skip
											else continue;
										}
									}

									// init if needed
									if(!isset($used[$type][$match])) $used[$type][$match] = array('files' => array(), 'module_specific' => array());

									// add module
									$used[$type][$match]['module_specific'][] = $specificModule;
								}
							}

							else
							{
								// loop modules
								foreach($modules as $module)
								{
									// determine if this is a module specific locale
									if(substr($match, 0, mb_strlen($module)) == SpoonFilter::toCamelCase($module) && mb_strlen($match) > mb_strlen($module) && ctype_upper(substr($match, mb_strlen($module) + 1, 1)))
									{
										// cleanup
										$match = str_replace(SpoonFilter::toCamelCase($module), '', $match);

										// init if needed
										if(!isset($used[$type][$match])) $used[$type][$match] = array('files' => array(), 'module_specific' => array());

										// add module
										$used[$type][$match]['module_specific'][] = $module;
									}
								}
							}

							// init if needed
							if(!isset($used[$type][$match])) $used[$type][$match] = array('files' => array(), 'module_specific' => array());

							// add file
							if(!in_array($file, $used[$type][$match]['files'])) $used[$type][$match]['files'][] = $file;
						}
					}
				break;

				// template file
				case 'tpl':
					$matches = array();

					// get matches
					preg_match_all('/\{\$(act|err|lbl|msg)([A-Z][a-zA-Z_]*)(\|.*)?\}/U', $content, $matches);

					// any matches?
					if(isset($matches[2]))
					{
						// loop matches
						foreach($matches[2] as $key => $match)
						{
							// set type
							$type = $matches[1][$key];

							// loop modules
							foreach($modules as $module)
							{
								// determine if this is a module specific locale
								if(substr($match, 0, mb_strlen($module)) == SpoonFilter::toCamelCase($module) && mb_strlen($match) > mb_strlen($module))
								{
									// cleanup
									$match = str_replace(SpoonFilter::toCamelCase($module), '', $match);

									// init if needed
									if(!isset($used[$type][$match])) $used[$type][$match] = array('files' => array(), 'module_specific' => array());

									// add module
									$used[$type][$match]['module_specific'][] = $module;
								}
							}

							// init if needed
							if(!isset($used[$type][$match])) $used[$type][$match] = array('files' => array(), 'module_specific' => array());

							// add file
							if(!in_array($file, $used[$type][$match]['files'])) $used[$type][$match]['files'][] = $file;
						}
					}
				break;
			}
		}

		// init var
		$nonExisting = array();

		// check if the locale is present in the current language
		foreach($used as $type => $items)
		{
			// loop items
			foreach($items as $key => $data)
			{
				// process based on type
				switch($type)
				{
					// error
					case 'err':
						// module specific?
						if(!empty($data['module_specific']))
						{
							// loop modules
							foreach($data['module_specific'] as $module)
							{
								// if the error isn't found add it to the list
								if(substr_count(BL::err($key, $module), '{$' . $type) > 0) $nonExisting[] = array('language' => $language, 'application' => 'backend', 'module' => $module, 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
							}
						}

						// not specific
						else
						{
							// if the error isn't found add it to the list
							if(substr_count(BL::err($key), '{$' . $type) > 0)
							{
								// init var
								$exists = false;

								// loop files
								foreach($data['files'] as $file)
								{
									// init var
									$count = 0;

									// replace
									$modulePath = str_replace(BACKEND_MODULES_PATH, '', $file, $count);

									// validate
									if($count == 1)
									{
										// split into chunks
										$chunks = (array) explode('/', trim($modulePath, '/'));

										// first part is the module
										if(isset($chunks[0]) && BL::err($key, $chunks[0]) != '{$' . $type . SpoonFilter::toCamelCase($chunks[0]) . $key . '}') $exists = true;
									}
								}

								// doesn't exists
								if(!$exists) $nonExisting[] = array('language' => $language, 'application' => 'backend', 'module' => 'core', 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
							}
						}
					break;

					// label
					case 'lbl':
						// module specific?
						if(!empty($data['module_specific']))
						{
							// loop modules
							foreach($data['module_specific'] as $module)
							{
								// if the label isn't found add it to the list
								if(substr_count(BL::lbl($key, $module), '{$' . $type) > 0) $nonExisting[] = array('language' => $language, 'application' => 'backend', 'module' => $module, 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
							}
						}

						// not specific
						else
						{
							// if the label isn't found, check in the specific module
							if(substr_count(BL::lbl($key), '{$' . $type) > 0)
							{
								// init var
								$exists = false;

								// loop files
								foreach($data['files'] as $file)
								{
									// init var
									$count = 0;

									// replace
									$modulePath = str_replace(BACKEND_MODULES_PATH, '', $file, $count);

									// validate
									if($count == 1)
									{
										// split into chunks
										$chunks = (array) explode('/', trim($modulePath, '/'));

										// first part is the module
										if(isset($chunks[0]) && BL::lbl($key, $chunks[0]) != '{$' . $type . SpoonFilter::toCamelCase($chunks[0]) . $key . '}') $exists = true;
									}
								}

								// doesn't exists
								if(!$exists) $nonExisting[] = array('language' => $language, 'application' => 'backend', 'module' => 'core', 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
							}
						}
					break;

					// message
					case 'msg':
						// module specific?
						if(!empty($data['module_specific']))
						{
							// loop modules
							foreach($data['module_specific'] as $module)
							{
								// if the message isn't found add it to the list
								if(substr_count(BL::msg($key, $module), '{$' . $type) > 0) $nonExisting[] = array('language' => $language, 'application' => 'backend', 'module' => $module, 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
							}
						}

						// not specific
						else
						{
							// if the message isn't found add it to the list
							if(substr_count(BL::msg($key), '{$' . $type) > 0)
							{
								// init var
								$exists = false;

								// loop files
								foreach($data['files'] as $file)
								{
									// init var
									$count = 0;

									// replace
									$modulePath = str_replace(BACKEND_MODULES_PATH, '', $file, $count);

									// validate
									if($count == 1)
									{
										// split into chunks
										$chunks = (array) explode('/', trim($modulePath, '/'));

										// first part is the module
										if(isset($chunks[0]) && BL::msg($key, $chunks[0]) != '{$' . $type . SpoonFilter::toCamelCase($chunks[0]) . $key . '}') $exists = true;
									}
								}

								// doesn't exists
								if(!$exists) $nonExisting[] = array('language' => $language, 'application' => 'backend', 'module' => 'core', 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
							}
						}
					break;
				}
			}
		}

		// return
		return $nonExisting;
	}


	/**
	 * Get the locale that is used in the frontend but doesn't exists.
	 *
	 * @return	array
	 * @param	string $language	The language to check.
	 */
	public static function getNonExistingFrontendLocale($language)
	{
		// get files to process
		$tree = self::getTree(FRONTEND_PATH);
		$used = array();

		// loop files
		foreach($tree as $file)
		{
			// grab content
			$content = SpoonFile::getContent($file);

			// process the file based on extension
			switch(SpoonFile::getExtension($file))
			{
				// javascript file
				case 'js':
					$matches = array();

					// get matches
					preg_match_all('/\{\$(act|err|lbl|msg)(.*)(\|.*)?\}/iU', $content, $matches);

					// any matches?
					if(isset($matches[2]))
					{
						// loop matches
						foreach($matches[2] as $key => $match)
						{
							// set type
							$type = $matches[1][$key];

							// init if needed
							if(!isset($used[$match])) $used[$type][$match] = array('files' => array());

							// add file
							if(!in_array($file, $used[$type][$match]['files'])) $used[$type][$match]['files'][] = $file;
						}
					}
				break;

				// PHP file
				case 'php':
					$matches = array();

					// get matches
					preg_match_all('/(FrontendLanguage|FL)::(get(Action|Label|Error|Message)|act|lbl|err|msg)\(\'(.*)\'\)/iU', $content, $matches);

					// any matches?
					if(!empty($matches[4]))
					{
						// loop matches
						foreach($matches[4] as $key => $match)
						{
							$type = 'lbl';
							if($matches[3][$key] == 'Action') $type = 'act';
							if($matches[2][$key] == 'act') $type = 'act';
							if($matches[3][$key] == 'Error') $type = 'err';
							if($matches[2][$key] == 'err') $type = 'err';
							if($matches[3][$key] == 'Message') $type = 'msg';
							if($matches[2][$key] == 'msg') $type = 'msg';

							// init if needed
							if(!isset($used[$type][$match])) $used[$type][$match] = array('files' => array());

							// add file
							if(!in_array($file, $used[$type][$match]['files'])) $used[$type][$match]['files'][] = $file;
						}
					}
				break;

				// template file
				case 'tpl':
					$matches = array();

					// get matches
					preg_match_all('/\{\$(act|err|lbl|msg)([a-z-_]*)(\|.*)?\}/iU', $content, $matches);

					// any matches?
					if(isset($matches[2]))
					{
						// loop matches
						foreach($matches[2] as $key => $match)
						{
							// set type
							$type = $matches[1][$key];

							// init if needed
							if(!isset($used[$type][$match])) $used[$type][$match] = array('files' => array());

							// add file
							if(!in_array($file, $used[$type][$match]['files'])) $used[$type][$match]['files'][] = $file;
						}
					}
				break;
			}
		}

		// init var
		$nonExisting = array();

		// set language
		FrontendLanguage::setLocale($language);

		// check if the locale is present in the current language
		foreach($used as $type => $items)
		{
			// loop items
			foreach($items as $key => $data)
			{
				// process based on type
				switch($type)
				{
					// action
					case 'act':
						// if the action isn't available add it to the list
						if(FL::act($key) == '{$' . $type . $key . '}') $nonExisting[] = array('language' => $language, 'application' => 'frontend', 'module' => 'core', 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
					break;

					// error
					case 'err':
						// if the error isn't available add it to the list
						if(FL::err($key) == '{$' . $type . $key . '}') $nonExisting[] = array('language' => $language, 'application' => 'frontend', 'module' => 'core', 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
					break;

					// label
					case 'lbl':
						// if the label isn't available add it to the list
						if(FL::lbl($key) == '{$' . $type . $key . '}') $nonExisting[] = array('language' => $language, 'application' => 'frontend', 'module' => 'core', 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
					break;

					// message
					case 'msg':
						// if the message isn't available add it to the list
						if(FL::msg($key) == '{$' . $type . $key . '}') $nonExisting[] = array('language' => $language, 'application' => 'frontend', 'module' => 'core', 'type' => $type, 'name' => $key, 'used_in' => serialize($data['files']));
					break;
				}
			}
		}

		// return
		return $nonExisting;
	}


	/**
	 * Get the translations
	 *
	 * @return	array
	 * @param	string $application			The application.
	 * @param	string $module				The module.
	 * @param	array $types				The types of the translations to get.
	 * @param	array $languages			The languages of the translations to get.
	 * @param	string $name				The name.
	 * @param	string $value				The value.
	 */
	public static function getTranslations($application, $module, $types, $languages, $name, $value)
	{
		// redefine languages
		$languages = (array) $languages;

		// create an array for the languages, surrounded by quotes (example: 'nl')
		$aLanguages = array();
		foreach($languages as $key => $val) $aLanguages[$key] = '\'' . $val . '\'';

		// surround the types with quotes
		foreach($types as $key => $val) $types[$key] = '\'' . $val . '\'';

		// get db
		$db = BackendModel::getDB();

		// build  the query
		$query = 'SELECT l.id, l.module, l.type, l.name, l.value, l.language
					FROM locale AS l
					WHERE l.language IN (' . implode(',', $aLanguages) . ') AND l.application = ? AND l.name LIKE ? AND l.value LIKE ? AND l.type IN (' . implode(',', $types) . ')';

		// add the paremeters
		$parameters = array($application, '%' . $name . '%', '%' . $value . '%');

		// add module to the query if needed
		if($module != null)
		{
			$query .= ' AND l.module = ?';
			$parameters[] = $module;
		}

		// get the translations
		$translations = (array) $db->getRecords($query, $parameters);

		// create an array for the sorted translations
		$sortedTranslations = array();

		// loop translations
		foreach($translations as $translation)
		{
			// add to the sorted array
			$sortedTranslations[$translation['type']][$translation['name']][$translation['module']][$translation['language']] = array('id' => $translation['id'], 'value' => $translation['value']);
		}

		// create an array to use in the datagrid
		$datagridTranslations = array();

		// an id that is used for in the datagrid, this is not the id of the translation!
		$id = 0;

		// loop the sorted translations
		foreach($sortedTranslations as $type => $references)
		{
			// create array for each type
			$datagridTranslations[$type] = array();

			foreach($references as $reference => $translation)
			{
				// loop modules
				foreach($translation as $module => $t)
				{
					// create translation (and increase id)
					$trans = array('module' => $module, 'name' => $reference, 'id' => $id++);

					// is there a translation? else empty string
					foreach($languages as $lang)
					{
						if(count($languages) == 1) $trans['translation_id'] = isset($t[$lang]) ? $t[$lang]['id'] : '';
						$trans[$lang] = isset($t[$lang]) ? $t[$lang]['value'] : '';
					}

					// add the translation to the array
					$datagridTranslations[$type][] = $trans;
				}
			}
		}

		return $datagridTranslations;
	}


	/**
	 * Get the filetree
	 *
	 * @return	array
	 * @param	string $path			The path to get the filetree for.
	 * @param	array[optional] $tree	An array to hold the results.
	 */
	private static function getTree($path, array $tree = array())
	{
		// paths that should be ignored
		$ignore = array(BACKEND_CACHE_PATH, BACKEND_CORE_PATH . '/js/tiny_mce', FRONTEND_CACHE_PATH);

		// get active modules
		$activeModules = BackendModel::getModules(true);

		// get the folder listing
		$items = SpoonDirectory::getList($path, true, array('.svn', '.git'));

		// already in the modules?
		if(substr_count($path, '/modules/') > 0)
		{
			// get last chunk
			$start = strpos($path, '/modules') + 9;
			$end = strpos($path, '/', $start + 1);

			if($end === false) $moduleName = substr($path, $start);
			else $moduleName = substr($path, $start, ($end - $start));

			// don't go any deeper
			if(!in_array($moduleName, $activeModules)) return $tree;
		}

		// loop items
		foreach($items as $item)
		{
			// if the path should be ignored, skip it
			if(in_array($path . '/' . $item, $ignore)) continue;

			// if the item is a directory we should index it also (recursive)
			if(is_dir($path . '/' . $item)) $tree = self::getTree($path . '/' . $item, $tree);

			else
			{
				// if the file has an extension that has to be processed add it into the tree
				if(in_array(SpoonFile::getExtension($item), array('js', 'php', 'tpl'))) $tree[] = $path . '/' . $item;
			}
		}

		// return
		return $tree;
	}


	/**
	 * Get full type name.
	 *
	 * @return	string
	 * @param	string $type		The type of the locale.
	 */
	public static function getTypeName($type)
	{
		// get full type name
		switch($type)
		{
			case 'act':
				$type = 'action';
			break;
			case 'err':
				$type = 'error';
			break;
			case 'lbl':
				$type = 'label';
			break;
			case 'msg':
				$type = 'message';
			break;
		}

		// cough up full name
		return $type;
	}


	/**
	 * Get all locale types.
	 *
	 * @return	array
	 */
	public static function getTypesForDropDown()
	{
		// fetch types
		$types = BackendModel::getDB()->getEnumValues('locale', 'type');

		// init
		$labels = $types;

		// loop and build labels
		foreach($labels as &$row) $row = ucfirst(BL::msg(mb_strtoupper($row), 'core'));

		// build array
		return array_combine($types, $labels);
	}


	/**
	 * Get all locale types for a multicheckbox.
	 *
	 * @return	array
	 */
	public static function getTypesForMultiCheckbox()
	{
		// fetch types
		$aTypes = BackendModel::getDB()->getEnumValues('locale', 'type');

		// init
		$labels = $aTypes;

		// loop and build labels
		foreach($labels as &$row) $row = ucfirst(BL::msg(mb_strtoupper($row), 'core'));

		// build array
		$aTypes = array_combine($aTypes, $labels);

		// create a new array to redefine the types for the multicheckbox
		$types = array();

		// loop the languages
		foreach($aTypes as $key => $type)
		{
			// add to array
			$types[$key]['value'] = $key;
			$types[$key]['label'] = $type;
		}

		// return the redefined array
		return $types;
	}


	/**
	 * Import a locale XML file.
	 *
	 * @return	void
	 * @param	SimpleXMLElement $xml				The locale XML.
	 * @param	bool[optional] $overwriteConflicts	Should we overwrite when there is a conflict?
	 */
	public static function importXML(SimpleXMLElement $xml, $overwriteConflicts = false)
	{
		// recast
		$overwriteConflicts = (bool) $overwriteConflicts;

		// possible values
		$possibleApplications = array('frontend', 'backend');
		$possibleModules = BackendModel::getModules(false);
		$possibleLanguages = BL::getActiveLanguages();
		$possibleTypes = array();

		// types
		$typesShort = (array) BackendModel::getDB()->getEnumValues('locale', 'type');
		foreach($typesShort as $type) $possibleTypes[$type] = self::getTypeName($type);

		// current locale items (used to check for conflicts)
		$currentLocale = (array) BackendModel::getDB()->getColumn('SELECT CONCAT(application, module, type, language, name) FROM locale');

		// applications
		foreach($xml as $application => $modules)
		{
			// application does not exist
			if(!in_array($application, $possibleApplications)) continue;

			// modules
			foreach($modules as $module => $items)
			{
				// module does not exist
				if(!in_array($module, $possibleModules)) continue;

				// items
				foreach($items as $item)
				{
					// attributes
					$attributes = $item->attributes();
					$type = SpoonFilter::getValue($attributes['type'], $possibleTypes, '');
					$name = SpoonFilter::getValue($attributes['name'], null, '');

					// missing attributes
					if($type == '' || $name == '') continue;

					// real type (shortened)
					$type = array_search($type, $possibleTypes);

					// translations
					foreach($item->translation as $translation)
					{
						// attributes
						$attributes = $translation->attributes();
						$language = SpoonFilter::getValue($attributes['language'], $possibleLanguages, '');

						// language does not exist
						if($language == '') continue;

						// the actual translation
						$translation = (string) $translation;

						// locale item
						$locale['user_id'] = BackendAuthentication::getUser()->getUserId();
						$locale['language'] = $language;
						$locale['application'] = $application;
						$locale['module'] = $module;
						$locale['type'] = $type;
						$locale['name'] = $name;
						$locale['value'] = $translation;
						$locale['edited_on'] = BackendModel::getUTCDate();

						// found a conflict, overwrite it with the imported translation
						if($overwriteConflicts && in_array($application . $module . $type . $language . $name, $currentLocale))
						{
							// overwrite
							BackendModel::getDB(true)->update('locale',
																$locale,
																'application = ? AND module = ? AND type = ? AND language = ? AND name = ?',
																array($application, $module, $type, $language, $name));
						}

						// insert translation that doesnt exists yet
						elseif(!in_array($application . $module . $type . $language . $name, $currentLocale))
						{
							// insert
							BackendModel::getDB(true)->insert('locale', $locale);
						}
					}
				}
			}
		}

		// rebuild cache
		foreach($possibleApplications as $application)
		{
			foreach($possibleLanguages[$application] as $language) self::buildCache($language, $application);
		}
	}


	/**
	 * Insert a new locale item.
	 *
	 * @return	int
	 * @param	array $item		The data to insert.
	 */
	public static function insert(array $item)
	{
		// insert item
		$item['id'] = (int) BackendModel::getDB(true)->insert('locale', $item);

		// rebuild the cache
		self::buildCache($item['language'], $item['application']);

		// return the new id
		return $item['id'];
	}


	/**
	 * Update a locale item.
	 *
	 * @return	void
	 * @param	array $item		The new data.
	 */
	public static function update(array $item)
	{
		// update category
		$updated = BackendModel::getDB(true)->update('locale', $item, 'id = ?', array($item['id']));

		// rebuild the cache
		self::buildCache($item['language'], $item['application']);

		// return
		return $updated;
	}
}

?>