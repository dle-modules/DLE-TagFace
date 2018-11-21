<?php

/*
=============================================================================
 Файл: tagface.php (frontend) версия 1.1.1
-----------------------------------------------------------------------------
 Автор: Фомин Александр Алексеевич, mail@mithrandir.ru
-----------------------------------------------------------------------------
 Назначение: вывод SEO текстов для тегов
=============================================================================
*/

    // Антихакер
    if( !defined( 'DATALIFEENGINE' )) {
            die( "Hacking attempt!" );
    }

    /*
     * Класс вывода SEO текстов для тегов
     */
    class TagFace 
    {
        /*
         * Конструктор класса TagFace - задаёт значение свойства dle_config и db
         */
	public function __construct() {
		global $db, $config;
		$this->dle_config = $config;
		$this->db = $db;
	}


        /*
         * Главный метод класса TagFace
         */
        public function run()
        {
            // Подхватываем глобальные переменные
            global $dle_module, $db;

            // Проверка на просмотр информации о теге
            if(($dle_module == 'tags'))
            {             
                // Получаем номер страницы и тег из запроса
                $page = intval($_REQUEST['cstart']);
                $tag_id = urldecode ($_REQUEST['tag']);
                // Проверка кодировки не работает на старых DLE
                //if($this->dle_api->dle_config['charset'] == "windows-1251" AND $this->dle_api->dle_config['charset'] != detect_encoding($tag_id) )
                //{
                //    $tag_id = iconv( "UTF-8", "windows-1251//IGNORE", $tag_id );
                //}
                $tag_id = @$db->safesql( ( strip_tags ( stripslashes ( trim ( $tag_id ) ) ) ) );

				// Пробуем подгрузить содержимое модуля из кэша
				$output = false;
                if ($this->dle_config['allow_cache'] && $this->dle_config['allow_cache'] != "no")
                {
					$output = dle_cache('tagface_', md5($tag_id . '_' . $page) . $this->dle_config['skin']);
                }

                // Если значение кэша для данной конфигурации получено, выводим содержимое кэша
                if($output !== false)
                {
                    $this->showOutput($output);
                    return;
                }
                
                // Ищем соответствующую запись в таблице tag_face
				$tagFace = $this->db->super_query("SELECT * FROM " . PREFIX . "_tag_face WHERE tag_id = '" . $tag_id . "'");

                // Формируем вывод только в том случае, если запись найдена и модуль активирован на текущей странице
                if(!empty($tagFace) && $tagFace['module_placement'] != 'nowhere' && ($tagFace['module_placement'] == 'all_pages' || $page < 2))
                {
                    // Вывод заголовка
                    if($tagFace['name_placement'] == 'all_pages' || $page < 2)
                    {
                        switch($tagFace['show_name'])
                        {
                            case 'show':
                                if($tagFace['name'] != '')
                                {
                                    $name = stripslashes($tagFace['name']);
                                }
                                break;
                            case 'default':
                                if($tag_id != '')
                                {
                                    $name = stripslashes($tag_id);
                                }
                                break;
                            case 'hide':
                                break;
                        }
                    }
                    
                    // Если указан альтернативный заголовок для остальных страниц, а основной отображается только на первой
                    elseif($page >= 2 && $tagFace['name_pages'] != '')
                    {
                        $name = stripslashes($tagFace['name_pages']);
                    }

                    // Вывод описания
                    if($tagFace['description_placement'] == 'all_pages' || $page < 2)
                    {
                        switch($tagFace['show_description'])
                        {
                            case 'show':
                                if($tagFace['description'] != '')
                                {
                                    $description = stripslashes($tagFace['description']);
                                }
                                break;
                            case 'hide':
                                break;
                        }
                    }
                    
                    // Если указано альтернативное описание для остальных страниц, а основное отображается только на первой
                    elseif($page >= 2 && $tagFace['description_pages'] != '')
                    {
                        $description = stripslashes($tagFace['description_pages']);
                    }
                }
                
                // Если модуль не активирован на данной странице или запись не найдена, не будем ничего показывать
                else
                {
                    return false;
                }
            }
            
            $output = $this->applyTemplate('tagface',
                array(
                    '{name}'          => $name,
                    '{description}'   => $description,
                ),
                array(
                    "'\[show_name\\](.*?)\[/show_name\]'si" => !empty($name)?"\\1":'',
                    "'\[show_description\\](.*?)\[/show_description\]'si" => !empty($description)?"\\1":'',
                )
            );
            
            // Если разрешено кэширование, сохраняем в кэш по данному тегу
            if ($this->dle_config['allow_cache'] && $this->dle_config['allow_cache'] != "no")
            {
				create_cache('tagface_', $output, md5($tag_id . '_' . $page) . $this->dle_config['skin']);
            }

            $this->showOutput($output);
        }


        /*
         * Метод подхватывает tpl-шаблон, заменяет в нём теги и выводит в браузер
         * @param $output - форматированный результат
         */
        public function showOutput($output)
        {
            echo $output;
        }
        
        
        

        /*
         * Метод подхватывает tpl-шаблон, заменяет в нём теги и возвращает отформатированную строку
         * @param $template - название шаблона, который нужно применить
         * @param $vars - ассоциативный массив с данными для замены переменных в шаблоне
         * @param $vars - ассоциативный массив с данными для замены блоков в шаблоне
         *
         * @return string tpl-шаблон, заполненный данными из массива $data
         */
        public function applyTemplate($template, $vars = array(), $blocks = array())
        {
            // Подключаем файл шаблона $template.tpl, заполняем его
            $tpl = new dle_template();
            $tpl->dir = TEMPLATE_DIR;
            $tpl->load_template($template.'.tpl');

            // Заполняем шаблон переменными
            foreach($vars as $var => $value)
            {
                $tpl->set($var, $value);
            }

            // Заполняем шаблон блоками
            foreach($blocks as $block => $value)
            {
                $tpl->set_block($block, $value);
            }

            // Компилируем шаблон (что бы это не означало ;))
            $tpl->compile($template);

            // Выводим результат
            return $tpl->result[$template];
        }
    }
    /*---End Of TagFace Class---*/

    // Создаём объект класса TagFace
    $tagFace = new TagFace;

    // Запускаем главный метод класса
    $tagFace->run();
    
?>