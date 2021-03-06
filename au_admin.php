<?php
/**
 * Скрипт предназначен для безопасного использования запросов с токеном админа.
 * Идея в том, что токен хранится в этом файле, а файл работает с вашего хостинга.
 * Сторонний сервис может обратиться к скрипту с неким запросом, тогда скрипт подписывает запрос токеном админа
 * и пересылает его ВКонтакту, а сервису возвращает ответ. Таким образом сохраняется безопасность токена и полный
 * контроль над выполнением запросов с его помощью.
 *
 * Вопросы, предложения, жалобы - направлять в техподдержку сервиса ActiveUsers
 * https://vk.com/im?sel=-164298418
 *
 * ActiveUsers (c) Доброслав
 */

/* В строчке надо заменить значение в кавычках на токен. Получить его можно следующим образом:
1. Идём по ссылке
https://oauth.vk.com/authorize?client_id=ИД_ВАШЕГО_STANDALONE_ПРИЛОЖЕНИЯ&display=page&redirect_uri=https://oauth.vk.com/blank.html&scope=offline,groups,photos,wall,video,docs,ads,wall,status&response_type=token&v=5.78&state=1

2. Разрешаем доступ приложению. Нас перебрасывает на страничку с надписью "Пожалуйста, не копируйте данные из адресной
строки для сторонних сайтов...". Всё верно, для сторонних не стоит, но вам для себя.

3. Забираем содержимое адресной строки браузера между
https://oauth.vk.com/blank.html#access_token=
и
&expires_in=0&user_id=1234567&state=1

Подсказка: обычно она выглядит примерно как
d0d035e3e26858f87e3287167c3c0623e26858f87e3287167c3c062858f87e3287167c3c062
и содержит в себе только символы "0123456789abcdef",
больше ничего там быть не должно.
*/

$_AU_ADMIN_TOKEN = 'ВОТ_ТУТ_НАПИШИТЕ_ТОКЕН';

/*
* Иногда возникает необходимость использовать несколько токенов, например для интенсивной работы со стеной. Они могут
* быть от разных администраторов и может быть даже от разных приложений. Тогда используем функцию, отдающей случайный токен из набора
* Записываем токены в функцию и раскомментируем строку, где она используется.
*/

function get_random_token() {
    $tokens = [
        'ВОТ_ТУТ_НАПИШИТЕ_ПЕРВЫЙ_ТОКЕН',
        'ВОТ_ТУТ_НАПИШИТЕ_ВТОРОЙ_ТОКЕН',
        'ВОТ_ТУТ_НАПИШИТЕ_ТРЕТИЙ_ТОКЕН',
        // И так сколько надо, добавляем строчки, в конце не забываем запятые
    ];
    return $tokens[array_rand($tokens)];
}

// $_AU_ADMIN_TOKEN = get_random_token();


/*
* Когда AU присылает запрос на подпись - в поле $_POST['au_key'] у него находится произвольная строка-пароль,
* которую вы настроили в сообществе на стороне АЮ, и которая гарантирует, что запрос пришёл именно от сервиса.
*/

$_AU_KEY = 'А_ВОТ_ТУТ_НАДО_НАПИСАТЬ_КЛЮЧ_АДМИНА';

/*
 * Для безопасности проверяем адрес источника, от которого пришёл запрос.
 * По-умолчанию тут указаны адрес серверов АЮ, но может возникнуть необходимость их дополнить,
 * если у вас выделеный сервер или мы введём в эксплуатацию ещё сервера.
 * */

$_AU_IP = [
    '85.143.221.242',
    '85.143.220.242',
    '89.223.30.206'
];

$method = $_GET['method'];
$url = 'https://api.vk.com/method/' . $method;          // Адрес АПИ ВКонтакте

if(in_array($_SERVER['REMOTE_ADDR'], $_AU_IP) or 1 ) {        // Проверка IP
    if($_POST['au_key'] == $_AU_KEY) {                  // Проверка ключа

        $_POST['access_token'] = $_AU_ADMIN_TOKEN;
        unset($_POST['au_key']);

        $html_params = http_build_query($_POST);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 100000);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $html_params);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        $str = curl_exec($ch) or die('ERROR ' . curl_error($ch));
        curl_close($ch);

        // Раскомментируйте, если хотите писать логи
        //file_put_contents('admin_log.txt', date('Y-m-d H:i:s') . ' ' . $str . "\n", FILE_APPEND);

        echo $str;
    } else {
        die('Неверный ключ сервиса');
    }
} else {
    die('Неверный источник запроса');
}

