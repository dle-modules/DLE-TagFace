# DLE-TagFace
![version](https://img.shields.io/badge/version-1.1.2-red.svg?style=flat-square "Version")
![DLE](https://img.shields.io/badge/DLE-8.2-green.svg?style=flat-square "DLE Version")
[![MIT License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://github.com/dle-modules/DLE-StarterKit/blob/master/LICENSE)

Модуль TagFace – SEO оптимизация тегов для DLE Datalife Engine

## Установка модуля:

- Распакуйте [архив с модулем](https://github.com/dle-modules/DLE-TagFace/releases/latest) в корень сайта;
- Скопируйте содержимое архива (кроме /templates/) на сервер;
- Содержимое папки /templates/Default/ поместите в папку своего шаблона;
- Запустите файл tagface_installer.php и следуйте его инструкциям;
- Удалите файл tagface_installer.php с сервера;
- Откройте файл main.tpl своего шаблона и в нужное место добавьте следующий код:`[aviable=tags]{include file="engine/modules/tagface.php"}[/aviable]`
- Процесс установки завершен, переходите к настройке модуля.

## Удаление модуля

- Загрузите файл tagface_uninstaller.php на сервер, в папку где установлен DLE;
- Запустите файл tagface_uninstaller.php и следуйте инструкциям;
- Удалите все файлы модуля, загруженные при установке;
- Не забудьте также удалить файл tagface_uninstaller.php.