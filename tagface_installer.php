<!DOCTYPE HTML>
<html>
    <head>
        <title>Установка модуля TagFace</title>
        <link rel="stylesheet" type="text/css" href="http://store.alaev.info/style.css" />
        <style type="text/css">
            #header {width: 100%; text-align: center;}
            .module_image {float: left; margin: 0 15px 15px 0;}
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

                            $output = module_installer();
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

    function module_installer()
    {
        // Стандартный текст
        $output = '<h2>Добро пожаловать в установщик модуля TagFace!</h2>';
        $output .= '<img class="module_image" src="/engine/skins/images/tagface.png" />';
        $output .= '<p><strong>Внимание!</strong> После установки модуля <strong>обязательно</strong> удалите файл <strong>tagface_installer.php</strong> с Вашего сервера!</p>';

        // Если через $_POST передаётся параметр tagface_install, производим инсталляцию, согласно параметрам
        if(!empty($_POST['tagface_install']))
        {
            // Подключаем config
            include_once ('engine/data/config.php');

            // Подключаем DLE API
            include ('engine/api/api.class.php');

            // Удаление таблицы с таким же названием (если существует)
            $query = "DROP TABLE IF EXISTS `".PREFIX."_tag_face`;";
            $dle_api->db->query($query);

            // Cоздание таблицы для модуля
            $query = "CREATE TABLE `".PREFIX."_tag_face` (
                          `tag_id` varchar(255) NOT NULL,
                          `name` varchar(255) NOT NULL,
                          `name_pages` varchar(255) NOT NULL,
                          `description` text NOT NULL,
                          `description_pages` text NOT NULL,
                          `module_placement` enum('nowhere','first_page','all_pages') NOT NULL,
                          `show_name` enum('show','default','hide') NOT NULL,
                          `show_description` enum('show','hide') NOT NULL,
                          `name_placement` enum('first_page','all_pages') NOT NULL,
                          `description_placement` enum('first_page','all_pages') NOT NULL,
                          PRIMARY KEY (`tag_id`)
                        ) DEFAULT CHARSET=cp1251;";
            $dle_api->db->query($query);

            // Устанавливаем модуль в админку
            $dle_api->install_admin_module('tagface', 'TagFace - SEO оптимизация тегов', 'Модуль позволяет прикрепить к тегам описание и заголовок, а так же регулировать их вывод на разных страницах', 'tagface.png');

            // Вывод
            $output .= '<p>';
            $output .= 'Модуль успешно установлен! Спасибо за Ваш выбор! Приятной работы!';
            $output .= '</p>';
        }

        // Если через $_POST ничего не передаётся, выводим форму для установки модуля
        else
        {
            // Вывод
            $output .= '<p>';
            $output .= '<form method="POST" action="tagface_installer.php">';
            $output .= '<input type="hidden" name="tagface_install" value="1" />';
            $output .= '<input type="submit" value="Установить модуль" />';
            $output .= '</form>';
            $output .= '</p>';
        }
        
        $output .= '<p>';
        $output .= '<a href="http://alaev.info/blog/post/3857?from=TagFaceInstaller">разработка и поддержка модуля</a>';
        $output .= '</p>';

        // Функция возвращает то, что должно быть выведено
        return $output;
    }

?>
