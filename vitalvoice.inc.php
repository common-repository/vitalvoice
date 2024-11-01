<?php
/* Класс для озвучивания контента сайта v 1.0 Кодировка UTF-8!!!
 *
 * Используемые переменные описаны ниже в секции var класса
 *
 * Для использования скопируйте директорию vitelvoice с 2мя файлами
 * в корневую директорию вашего веб-сервера
 * подключите в необходимом месте файл класса командой: require_once 'vitalvoice.inc.php'
 * создайте новый объект класса VitalVoice с Вашим ключем для синтеза
 * $voice = new VitalVoice("вашуникальныйидентификатор");
 * при необходимости установите глос (по-умолчанию используется Vladimir)
 * $voice->setVoice("Maria");
 *
 * В нужном месте вызывайте, описанные ниже, методы для получения необходимого контента
 *
 * Публичные методы
 * setVoice( String $voice ); //Установить голос по-умолчанию
 * getMp3FileURL( String $text, String $voice (opt) ) //Получить ссылку на mp3-файл озвученного текста $text голосом $voice
 * getPlayerHTML( String $text, String $voice (opt) ) //Получить код плеера озвученного текста $text голосом $voice
 * 
 */



class VitalVoice {

    var
    $vvwServer = "81.3.190.190",                 // Сервер синтеза
    $vvwPort = "80",                             // Порт
    $vvwServerPath = "VitalVoiceWeb/RssMP3.ashx",// Путь до файла
    $connMethod = "socket",                      // Метод получения xml ответа
    $vvwURL,
    $vvwXML,
    $textTTS, $voiceTTS;

    function VitalVoice($vvwKeyAPI) {
        $this->vvwURL = "/$this->vvwServerPath?KeyAPI=$vvwKeyAPI";
    }

    function setVoice($voice = "Vladimir") {
        $this->voiceTTS = $voice;
    }

    function getMp3FileURL($text = "", $voice = "") {
        if ($voice)
            $this->voiceTTS = $voice;
        if ($text) {
            $this->prepareText($text);
            $this->parseRequest();
            if ($this->vvwXML->getValue())
                return $this->vvwXML->getElementByPath('channel/item/link')->getValue();
        }
    }

    function getPlayerHTML($text = "", $voice = "") {
        if ($voice)
            $this->voiceTTS = $voice;
        if ($text) {
            $this->prepareText($text);
            $this->parseRequest();
            if ($this->vvwXML->getValue())
                return html_entity_decode($this->vvwXML->getElementByPath('channel/item/player')->getValue());
        }
    }

    private function prepareText($text) {
        $this->textTTS = urlencode(htmlspecialchars(strip_tags(mb_convert_encoding($text, "windows-1251", "utf-8"))));
    }

    private function parseRequest() {
        $this->vvwURL .= "&voice=$this->voiceTTS&text=$this->textTTS";
        switch ($this->connMethod) {
            case 'socket':
                $xmlfile = $this->get_content_socket();
                break;
            default :
                $xmlfile = $this->get_content_open();
                break;
        }
//        print $xmlfile;
        require_once 'minixml.inc.php';
        $this->vvwXML = new MiniXMLDoc();
        $this->vvwXML->fromString($xmlfile);
        $this->vvwXML = &$this->vvwXML->getRoot();
    }

    private function get_content_open() {  //подключение через fopen
//получаем дескриптор удаленной страницы
        $fd = fopen("http://$this->vvwServer$this->vvwURL", "r");
        if (!$fd)
            exit("Запрашиваемая страница не найдена");
        $content = "";
//чтение содержимого файла в переменную text
        while (!feof($fd)) {
            $content .= fgets($fd, 1024);
        }
//закрыть открытый указатель файла
        fclose($fd);
        return strstr($content, '<');
    }

    private function get_content_socket() {   //подключение через fsockopen
        $line = "";
//устанавливаем соединение, имя которого
//передано в параметре $hostname
        $fd = fsockopen($this->vvwServer, 80, $errno, $errstr, 30);
//проверяем успешность установки соединения
        if (!fd)
            echo "$errstr ($errno)<br>/>\n";
        else {
//формируем HTTP-запрос для передачи его серверу
            $headers = "GET $this->vvwURL HTTP/1.1\r\n";
            $headers.="Host: $this->vvwServer\r\n";
            $headers.="Connection: Close\r\n\r\n";
//отправляем HTTP-запрос серверу
            fwrite($fd, $headers);
            $out = "";
            while (!feof($fd)) {
                $out .= fgets($fd, 1024);
            }
            fclose($fd);
        }
        return strstr($out, '<');
    }

}

?>
