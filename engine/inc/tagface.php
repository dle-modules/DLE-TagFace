<?php

/*
=============================================================================
 Файл: tagface.php (backend) версия 1.1.1
-----------------------------------------------------------------------------
 Автор: Фомин Александр Алексеевич, mail@mithrandir.ru
-----------------------------------------------------------------------------
 Назначение: настройка SEO для тегов
=============================================================================
*/

    // Антихакер
    if( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
            die( "Hacking attempt!" );
    }

    /*
     * Класс настройки SEO для тегов
     */
    class TagFaceAdmin
    {
        /*
         * Конструктор класса TagFaceAdmin - задаёт значение свойства dle_api и editor
         * @param $dle_api - объект класса DLE_API
         */
        public function __construct()
        {
            // Подключаем DLE_API
            global $db, $config;
            include ('engine/api/api.class.php');
            $this->dle_api = $dle_api;
        }


        /*
         * Главный метод класса TagFaceAdmin - в зависимости от запроса, вызывает те или иные действия
         */
        public function run()
        {
            // Ловим параметр action из запроса; по умолчанию action=list (список позиций)
            $action = !empty($_REQUEST['action'])?$_REQUEST['action']:'list';

            // В зависимости от параметра action, выполняем те или иные действия
            switch($action)
            {
                // Просмотр списка позиций
                case 'list':
                    $output = $this->actionList();
                    $headerText = 'Список тегов';
                    break;

                // Форма редактирования одной позици из списка
                case 'form':
                    $output = $this->actionForm();
                    $headerText = '<a href="?mod=tagface"><< Вернуться к списку тегов</a>';
                    break;

                // Созранение позиции
                case 'save':
                    $output = $this->actionSave();
                    $headerText = 'Сохранение информации';
                    break;

                // Ошибка - не существующий action
                default:
                    $headerText = 'Ошибка! Запрошено неизвестное действие!';
                    break;
            }

            $this->showOutput($headerText, $output);
        }


        /*
         * Метод генерирует список тегов и возвращает разметку для вывода
         * @return string
         */
        public function actionList()
        {
            return '
            <table id="tagslist" class="table table-normal" width="100%">
                <tbody>
                    <tr>
                        <th>Тег</th>
                        <th>Действие:</th>
                    </tr>
                    <tr class="list_item">
                        <td colspan="3"><div class="hr_line"></div></td>
                    </tr>
                    '.$this->createTagsTable().'
                </tbody>
            </table>
            <script type="text/javascript">
                $(function(){
                        $("#tagslist").delegate("tr.list_item", "hover", function(){
                          $(this).toggleClass("hoverRow");
                        });
                });
            </script>
            
            ';
        }


        /*
         * Метод генерирует строки с тегами в системе новостей
         * @return string таблицу всех тегов
         */
        public function createTagsTable()
        {
            // Получаем список тегов
            $tags = $this->dle_api->load_table (PREFIX."_tags", 'tag', '1 GROUP BY tag', true, 0, false, 'tag', 'ASC');

            // В переменную $tagsTable будем складывать строки в таблицу тегов для вывода
            $tagsTable = '';

            // Если что-то найдено, перебираем все найденные теги
            if($tags)
            {
                foreach($tags as $tag)
                {
                    // Добавляем в таблицу текущий тег
                    $tagsTable .= '
                    <tr class="list_item">
                        <td height="20">&nbsp;<a href="?mod=tagface&action=form&tag_id='.urlencode($tag['tag']).'">'.$tag['tag'].'</a></td>
                        <td height="20">&nbsp;[<a href="?mod=tagface&action=form&tag_id='.urlencode($tag['tag']).'">редактировать</a>]</td>
                    </tr>';
                }
            }

            return $tagsTable;
        }



        /*
         * Метод генерирует форму редактирования информаци
         * @return string
         */
        public function actionForm()
        {
            // Подхватываем id тега из запроса
            $tag_id = urldecode($_REQUEST['tag_id']);
            
            // Ищем соответствующую запись в таблице tag_face
            $tagFace = $this->dle_api->load_table (PREFIX."_tag_face", '*', 'tag_id = "'.$tag_id.'"', false);

            // Подхватываем глобальные переменные
            global $lang, $config, $user_group, $member_id, $dle_login_hash;
            
            // Подключаем парсер
            include_once ENGINE_DIR . '/classes/parse.class.php';
            $parse = new ParseFilter( Array (), Array (), 1, 1 );

            // Подключаем редактор wysiwyg
            if($this->dle_api->dle_config['allow_admin_wysiwyg'] && ($this->dle_api->dle_config['allow_admin_wysiwyg'] != "no") )
            {
                $tagFace['description'] = $parse->decodeBBCodes($tagFace['description'], true, $this->dle_api->dle_config['allow_admin_wysiwyg']);
                $tagFace['description_pages'] = $parse->decodeBBCodes($tagFace['description_pages'], true, $this->dle_api->dle_config['allow_admin_wysiwyg']);
                
		ob_start();
                include (ENGINE_DIR . '/editor/tagface_description.php');
                ob_implicit_flush(false);
                $editor_description = ob_get_clean();
                
                ob_start();
                include (ENGINE_DIR . '/editor/tagface_description_pages.php');
                ob_implicit_flush(false);
                $editor_description_pages = ob_get_clean();
            }

            // Подключаем редактор bbcode
            else
            {
                $tagFace['description'] = $parse->decodeBBCodes($tagFace['description'], false);
                $tagFace['description_pages'] = $parse->decodeBBCodes($tagFace['description_pages'], false);
                
                $bb_editor = true;
                include (ENGINE_DIR . '/inc/include/inserttag.php');
		$editor_description = '
                <div class="form-group">
                    <label class="control-label col-xs-2">Описание тега:</label>
                    <div class="col-xs-10">
						'.$bb_code.'<textarea class="bk" style="width:100%;max-width:950px;height:300px;" name="description" id="description"  onclick=setFieldName(this.name)>'.$tagFace['description'].'</textarea><script type=text/javascript>var selField  = "description";</script>
					</div>
                </div>';

		$editor_description_pages = '
                <div id="description_pages_line" class="form-group">
                    <label class="control-label col-xs-2">Описание для остальных страниц:</label>
                    <div class="col-xs-10">
						'.$bb_code.'<textarea class="bk" style="width:100%;max-width:950px;height:300px;" name="description_pages" id="description_pages"  onclick=setFieldName(this.name)>'.$tagFace['description_pages'].'</textarea><script type=text/javascript>var selField  = "description_pages";</script>
					</div>
                </div>';
            }

            return '
            <form method="POST" action="?mod=tagface&action=save" class="form-horizontal">
                <div class="row box-section">
                        <div class="form-group">
                            <label class="control-label col-xs-2">Где активировать модуль:</label>
                            <div class="col-xs-3">
                                <input id="module_placement_nowhere" type="radio" name="module_placement" value="nowhere"'.(($tagFace['module_placement'] == 'nowhere')?' checked':'').'> <label for="module_placement_nowhere">нигде</label><br />
                                <input id="module_placement_first_page" type="radio" name="module_placement" value="first_page"'.(($tagFace['module_placement'] == 'first_page')?' checked':'').'> <label for="module_placement_first_page">на первой странице</label><br />
                                <input id="module_placement_all_pages" type="radio" name="module_placement" value="all_pages"'.(($tagFace['module_placement'] == 'all_pages')?' checked':'').'> <label for="module_placement_all_pages">на всех страницах</label>
							</div>
							<div class="col-xs-6 note large">
                                Данная опция позволяет скрыть на страницах списка новостей по даному тегу не только название и описание, но и все остальное содержимое tpl-шаблона:<br />
								<strong>нигде</strong> - Деактивация модуля в рамках списка новостей по даному тегу.<br />
								<strong>на первой странице</strong> - Модуль будет активирован на первой странице списка новостей по данному тегу.<br />
								<strong>на всех страницах</strong> - Модуль будет отображаться на всех страницах списка новостей по данному тегу.
                            </div>
                        </div>

						<hr />

                        <div class="form-group">
                            <label class="control-label col-xs-2">Заголовок тега:</label>
                            <div class="col-xs-10">
								<input type="text" value="'.$tagFace['name'].'" class="edit bk" style="width:98%;" size="25" name="name">
							</div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-xs-2">Отображать заголовок:</label>
                            <div class="col-xs-3">
                                <input id="show_name_show" type="radio" name="show_name" value="show"'.(($tagFace['show_name'] == 'show')?' checked':'').'> <label for="show_name_show">показывать</label><br />
                                <input id="show_name_default" type="radio" name="show_name" value="default"'.(($tagFace['show_name'] == 'default')?' checked':'').'> <label for="show_name_default">по умолчанию</label><br />
                                <input id="show_name_hide" type="radio" name="show_name" value="hide"'.(($tagFace['show_name'] == 'hide')?' checked':'').'> <label for="show_name_hide">скрывать</label>
                            </div>
							<div class="col-xs-6 note large">
                                <strong>показывать</strong> - Активирует заголовок, он будет отображаться в соответствии с заполненным полем выше.<br />
								<strong>по умолчанию</strong> - Использовать в качестве заголовка сам тег.<br />
								<strong>скрывать</strong> - Деактивирует заголовок, т.е. на странице он отображаться не будет.
							</div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-xs-2">Где отображать заголовок:</label>
                            <div class="col-xs-3">
                                <input id="name_placement_first_page" type="radio" name="name_placement" value="first_page"'.(($tagFace['name_placement'] == 'first_page')?' checked':'').'> <label for="name_placement_first_page">на первой странице</label><br />
                                <input id="name_placement_all_pages" type="radio" name="name_placement" value="all_pages"'.(($tagFace['name_placement'] == 'all_pages')?' checked':'').'> <label for="name_placement_all_pages">на всех страницах</label>
                            </div>
							<div class="col-xs-6 note large">
                                <strong>на первой странице</strong> - Заголовок будет отображаться только на главной странице списка новостей по даному тегу.<br />
								<strong>на всех страницах</strong> - Сквозной заголовок, т.е. будет отображаться на всех страницах списка новостей по даному тегу.
                            </div>
                        </div>
                        <div id="name_pages_separator"></div>
                        <div id="name_pages_line" class="form-group">
                            <label class="control-label col-xs-2">Заголовок для остальных страниц:</label>
                            <div class="col-xs-10">
								<input type="text" value="'.$tagFace['name_pages'].'" class="edit bk" style="width:98%;" size="25" name="name_pages">
							</div>
                        </div>

						<hr />

                        <div class="form-group">
                            <label class="control-label col-xs-2">Отображать описание:</label>
                            <div class="col-xs-3">
                                <input id="show_description_show" type="radio" name="show_description" value="show"'.(($tagFace['show_description'] == 'show')?' checked':'').'> <label for="show_description_show">показывать</label><br />
                                <input id="show_description_hide" type="radio" name="show_description" value="hide"'.(($tagFace['show_description'] == 'hide')?' checked':'').'> <label for="show_description_hide">скрывать</label>
                            </div>
							<div class="col-xs-6 note large">
                                <strong>показывать</strong> - Активирует описание, оно будет браться из текстового поля выше.<br />
								<strong>скрывать</strong> - Деактивирует описание, т.е. на странице оно отображаться не будет.
                            </div>
                        </div>

                        '.$editor_description.'

                        <div class="form-group">
                            <label class="control-label col-xs-2">Где отображать описание:</label>
                            <div class="col-xs-3">
                                <input id="description_placement_first_page" type="radio" name="description_placement" value="first_page"'.(($tagFace['description_placement'] == 'first_page')?' checked':'').'> <label for="description_placement_first_page">на первой странице</label><br />
                                <input id="description_placement_all_pages" type="radio" name="description_placement" value="all_pages"'.(($tagFace['description_placement'] == 'all_pages')?' checked':'').'> <label for="description_placement_all_pages">на всех страницах</label>
                            </div>
							<div class="col-xs-6 note large">
                                <strong>на первой странице</strong> - Описание будет отображаться только на главной странице списка новостей по даному тегу.<br />
								<strong>на всех страницах</strong> - Сквозное описание, т.е. будет отображаться на всех страницах списка новостей по даному тегу.
                            </div>
                        </div>

                        <div id="description_pages_separator"></div>
                        '.$editor_description_pages.'
                </div>

				<div style="text-align:center;padding:15px;"><input type="submit" class="btn btn-lg btn-green" value="Сохранить"></div>

                <input type="hidden" name="user_hash" value="'.$dle_login_hash.'" />
                <input type="hidden" name="tag_id" value="'.$tag_id.'" />
            </form>
            
            <script type="text/javascript">
                if(!document.getElementById("name_placement_first_page").checked)
                {
                    document.getElementById("name_pages_separator").style.display = "none";
                    document.getElementById("name_pages_line").style.display = "none";
                }
                if(!document.getElementById("description_placement_first_page").checked)
                {
                    document.getElementById("description_pages_separator").style.display = "none";
                    document.getElementById("description_pages_line").style.display = "none";
                }
                
                document.getElementById("name_placement_first_page").onclick = function(){
                    document.getElementById("name_pages_separator").style.display = "inherit";
                    document.getElementById("name_pages_line").style.display = "inherit";
                }
                document.getElementById("name_placement_all_pages").onclick = function(){
                    document.getElementById("name_pages_separator").style.display = "none";
                    document.getElementById("name_pages_line").style.display = "none";
                }
                
                document.getElementById("description_placement_first_page").onclick = function(){
                    document.getElementById("description_pages_separator").style.display = "inherit";
                    document.getElementById("description_pages_line").style.display = "inherit";
                }
                document.getElementById("description_placement_all_pages").onclick = function(){
                    document.getElementById("description_pages_separator").style.display = "none";
                    document.getElementById("description_pages_line").style.display = "none";
                }
            </script>
            ';
        }


        /*
         * Метод сохраняет SEO - информацию о теге в таблицу tag_face
         * @return string
         */
        public function actionSave()
        {
            // Подхватываем глобальные переменные
            global $dle_login_hash, $user_group, $member_id;

            // Проверка ключа
            if( $_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash )
            {
                die( "Hacking attempt! User not found" );
            }

            // Проверяем наличие id тега
            if($_POST['tag_id'] == '')
            {
                die('Тег не найден!');
            }

            // Подключаем класс парсера
            include_once ENGINE_DIR . '/classes/parse.class.php';
            $parse = new ParseFilter( Array (), Array (), 1, 1 );

            // Подхватываем данные из формы
            $tag_id = $_POST['tag_id'];
            $name = !empty($_POST['name'])?$_POST['name']:'';
            $name_pages = !empty($_POST['name_pages'])?$_POST['name_pages']:'';
            $module_placement = !empty($_POST['module_placement'])?$_POST['module_placement']:'all_pages';
            $show_name = !empty($_POST['show_name'])?$_POST['show_name']:'show';
            $name_placement = !empty($_POST['name_placement'])?$_POST['name_placement']:'all_pages';
            $description = !empty($_POST['description'])?$_POST['description']:'';
            $description_pages = !empty($_POST['description_pages'])?$_POST['description_pages']:'';
            $show_description = !empty($_POST['show_description'])?$_POST['show_description']:'show';
            $description_placement = !empty($_POST['description_placement'])?$_POST['description_placement']:'first_page';
            
            // Обрабатываем данные из формы
            $tag_id = $this->dle_api->db->safesql($parse->process(trim($tag_id)));
            $name = $this->dle_api->db->safesql($parse->process(trim(($name))));
            $name_pages = $this->dle_api->db->safesql($parse->process(trim(($name_pages))));
            $module_placement = $this->dle_api->db->safesql($parse->process(trim(($module_placement))));
            $show_name = $this->dle_api->db->safesql($parse->process(trim(($show_name))));
            $name_placement = $this->dle_api->db->safesql($parse->process(trim(($name_placement))));
            $show_description = $this->dle_api->db->safesql($parse->process(trim(($show_description))));
            $description_placement = $this->dle_api->db->safesql($parse->process(trim(($description_placement))));

            // Обрабатываем текст описания
            if (!$user_group[$member_id['user_group']]['allow_html'] )
            {
		$description = strip_tags($description);
                $description_pages = strip_tags($description_pages);
            }
            if($this->dle_api->dle_config['allow_admin_wysiwyg'] && ($this->dle_api->dle_config['allow_admin_wysiwyg'] != "no"))
            {
                $parse->allow_code = false;
            }

            $description = $parse->process($description);
            $description_pages = $parse->process($description_pages);

            if($this->dle_api->dle_config['allow_admin_wysiwyg'] && ($this->dle_api->dle_config['allow_admin_wysiwyg'] != "no"))
            {
		$description = $this->dle_api->db->safesql($parse->BB_Parse($description));
                $description_pages = $this->dle_api->db->safesql($parse->BB_Parse($description_pages));
            }
            else
            {
		$description = $this->dle_api->db->safesql($parse->BB_Parse($description, false));
                $description_pages = $this->dle_api->db->safesql($parse->BB_Parse($description_pages, false));
            }

            // Ошибка в случае, если что-то не прошло проверку
            if($parse->not_allowed_text)
            {
		msg( "error", 'Ошибка при сохранении', 'Недопустимые символы', "javascript:history.go(-1)" );
            }

            // Определяем, существует ли соответствующая запись в таблице tag_face
            $tagFace = $this->dle_api->load_table (PREFIX."_tag_face", 'tag_id', 'tag_id = "'.$tag_id.'"', false);

            // Если запись уже существовала, обновляем её
            if(!empty($tagFace))
            {
                $this->dle_api->db->query(
                    "UPDATE ".PREFIX."_tag_face SET ".
                        "`name` = '$name', ".
                        "`name_pages` = '$name_pages', ".
                        "`module_placement` = '$module_placement', ".
                        "`show_name` = '$show_name', ".
                        "`name_placement` = '$name_placement', ".
                        "`description` = '$description', ".
                        "`description_pages` = '$description_pages', ".
                        "`show_description` = '$show_description', ".
                        "`description_placement` = '$description_placement' ".
                        "WHERE `tag_id` = '$tag_id'"
                );
            }

            // Если записи не существовало, добавляем её
            else
            {
                $this->dle_api->db->query(
                    "INSERT INTO ".PREFIX."_tag_face ".
                        "(`tag_id`, `name`, `name_pages`, `module_placement`, `show_name`, `name_placement`, `description`, `description_pages`, `show_description`, `description_placement`) ".
                        "VALUES('$tag_id', '$name', '$name_pages', '$module_placement', '$show_name', '$name_placement', '$description', '$description_pages', '$show_description', '$description_placement')"
                );
            }

            // Выводим сообщение об успешном добавлении
            msg("info", 'Информация о теге успешно сохранена!', 'Информация о теге успешно сохранена!', '?mod=tagface');
        }


        /*
         * Метод выводит интерфейс в браузер
         * @param $headerText - текст заголовка страницы
         * @param $output - содержит отформатированный контент для вывода в браузер
         */
        public function showOutput($headerText, $output)
        {
            // Отображение шапки админского интерфейса
            echoheader('TagFace', 'Модуль SEO-оптимизации тегов');
            echo '

'.($config['version_id'] >= 10.2 ? '<style>.uniform, div.selector {min-width: 250px;}</style>' : '<style>
@import url("engine/skins/application.css");

.box {
margin:10px;
}
.uniform {
position: relative;
padding-left: 5px;
overflow: hidden;
min-width: 250px;
font-size: 12px;
-webkit-border-radius: 0;
-moz-border-radius: 0;
-ms-border-radius: 0;
-o-border-radius: 0;
border-radius: 0;
background: whitesmoke;
background-image: url("data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgi…pZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JhZCkiIC8+PC9zdmc+IA==");
background-size: 100%;
background-image: -webkit-gradient(linear, 50% 0%, 50% 100%, color-stop(0%, #ffffff), color-stop(100%, #f5f5f5));
background-image: -webkit-linear-gradient(top, #ffffff, #f5f5f5);
background-image: -moz-linear-gradient(top, #ffffff, #f5f5f5);
background-image: -o-linear-gradient(top, #ffffff, #f5f5f5);
background-image: linear-gradient(top, #ffffff, #f5f5f5);
-webkit-box-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
-moz-box-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
box-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
border: 1px solid #ccc;
font-size: 12px;
height: 28px;
line-height: 28px;
color: #666;
}
</style>').'

<div class="box">
	<div class="box-header">
		<div class="title">'.$headerText.'</div>
		<ul class="box-toolbar">
			<li class="toolbar-link">
			<a target="_blank" href="http://alaev.info/blog/post/3857?from=TagFaceAdmin">TagFace v.1.1.1 © 2015 Блог АлаичЪ\'а - разработка и поддержка модуля</a>
			</li>
		</ul>
	</div>

	<div class="box-content">
		'.$output.'
	</div>
</div>

			';

            // Отображение подвала админского интерфейса
            echofooter();
        }
    }
    /*---End Of TagFaceAdmin Class---*/

    // Создаём объект класса TagFaceAdmin
    $tagFaceAdmin = new TagFaceAdmin;

    // Запускаем главный метод класса
    $tagFaceAdmin->run();
    
?>