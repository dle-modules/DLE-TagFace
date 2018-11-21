<!DOCTYPE HTML>
<html>
    <head>
        <title>Удаление модуля TagFace</title>
        <link rel="stylesheet" type="text/css" href="http://store.alaev.info/style.css" />
        <style type="text/css">
            #header {width: 100%; text-align: center;}
            .box-cnt{width: 100%; overflow: hidden;}
        </style>
    </head>

    <body>
        <div class="wrap">
            <div id="header">
                <h1>TagFace</h1>
            </div>
            <div class="box">
                <div class="box-t">&nbsp;</div>
                <div class="box-c">
                    <div class="box-cnt">
                        <?php

                            $output = module_uninstaller();
                            echo $output;

                        ?>
                    </div>
                </div>
                <div class="box-b">&nbsp;</div>
            </div>
        </div>
    </body>
</html>

<?php

    function module_uninstaller()
    {
        // Стандартный текст
        $output = '<h2>Добро пожаловать в скрипт для удаления модуля TagFace!</h2>';
        $output .= '<p><strong>Внимание!</strong> После удаления модуля <strong>обязательно</strong> удалите файл <strong>tagface_uninstaller.php</strong> с Вашего сервера!</p>';
        $output .= '<p>';
        $output .= '<strong>Кроме того, необходимо удалить следующие файлы:</strong>';
        $output .= '<ul>';
            $output .= '<li>/engine/modules/<strong>tagface.php</strong></li>';
            $output .= '<li>/engine/inc/<strong>tagface.php</strong></li>';
            $output .= '<li>/engine/editor/<strong>tagface_description.php</strong></li>';
            $output .= '<li>/engine/editor/<strong>tagface_description_pages.php</strong></li>';
            $output .= '<li>/engine/skins/images/<strong>tagface.png</strong></li>';
            $output .= '<li>/templates/<em>Имя Вашего Шаблона</em>/<strong>tagface.tpl</strong></li>';
        $output .= '</ul>';
        $output .= '</p>';

        // Если через $_POST передаётся параметр tagface_uninstall, производим инсталляцию, согласно параметрам
        if(!empty($_POST['tagface_uninstall']))
        {
            // Подключаем config
            include_once ('engine/data/config.php');

            // Подключаем DLE API
            include ('engine/api/api.class.php');
            
            // Удаление таблицы category_face
            $query = "DROP TABLE IF EXISTS `".PREFIX."_tag_face`;";
            $dle_api->db->query($query);

            // Удаляем модуль из админки
            $dle_api->uninstall_admin_module('tagface');

            // Вывод
            $output .= '<p>';
            $output .= 'Модуль успешно удалён!';
            $output .= '</p>';
        }

        // Если через $_POST ничего не передаётся, выводим форму для удаления модуля
        else
        {
            // Вывод
            $output .= '<p>';
            $output .= '<form method="POST" action="tagface_uninstaller.php">';
            $output .= '<input type="hidden" name="tagface_uninstall" value="1" />';
            $output .= '<input type="submit" value="Удалить модуль" />';
            $output .= '</form>';
            $output .= '</p>';
        }
        
        $output .= '<p>';
        $output .= '<a href="http://alaev.info/blog/post/3857?from=TagFaceUninstaller">разработка и поддержка модуля</a>';
        $output .= '</p>';

        // Функция возвращает то, что должно быть выведено
        return $output;
    }

?>