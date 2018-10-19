<?php

if (!class_exists("nc_System"))
    die("Unable to load file.");

class nc_Mail extends nc_System
{

    protected $core;
    protected $transport;
    protected $swiftMailer;
    protected $isSMTP = false;
    public $priority = 3;
    public $message;
    public $charset;

    public function __construct()
    {
        // load parent constructor
        parent::__construct();
        $this->core = nc_Core::get_object();
    }

    /**
     * Устанавливает приоритет письма
     * @param string $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * Устанавливает кодировку письма
     * @param string $charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    /**
     * 
     * Формирует тело письма
     * @param string $plain
     * @param string $html
     */
    public function mailbody($plain, $html = "")
    {
        $this->init();
        $this->body_plain = $plain;
        $this->body_html = $html;
        $this->isHtml = !empty($html) ? true : false;
    }

    /**
     * Позволяет пользователю прикрепить файл
     * 
     * @param string $fname
     * @param string $original_name
     * @param strings $content_type
     */
    public function attachFile($fname, $original_name = '', $content_type = '')
    {
        $this->init();
        $this->message->attach(Swift_Attachment::fromPath($fname, $content_type)->setFilename($original_name));
    }

    /**
     * 
     * Позволяет пользователю прикрепить файл в тело письма
     * 
     * @param string $fname
     * @param string $original_name
     * @param string $content_type
     * @return string cid для вставки в HTML-код письма
     */
    public function attachFileEmbed($fname, $original_name, $content_type)
    {
        $this->init();
        return $this->message->embed(Swift_EmbeddedFile::fromPath($fname, $content_type)->setFilename($original_name));
    }

    /**
     * 
     * Формирование и отправка письма
     * 
     * @param string $to
     * @param string $from
     * @param string $reply
     * @param string $subject
     * @param string $from_name
     * @param string $to_name
     * @return int
     */
    public function send($to = '', $from, $reply, $subject, $from_name = '', $to_name = '')
    {
        if (!empty($to)) {
            $this->init();
            $this->set_subject($subject);
            $this->set_from($from, $from_name);
            $this->set_to($to, $to_name);
            $this->message->setReplyTo($reply);

            if ($this->isHtml && $this->body_html) {
                $this->message->setBody($this->body_html, 'text/html');
                //временно убираем
//                $this->message->addPart($this->body_plain, 'text/plain');
            } else if ($this->body_plain) {
                $this->message->setBody($this->body_plain, 'text/plain');
            }
            else {
				$this->message->setBody();
			}
			
            $result = $this->swiftMailer->send($this->message);
            unset($this->message);
            return $result;
        }
    }

    /**
     * 
     * Устанавливает Тему письма
     * 
     * @param string $subject
     */
    public function set_subject($subject = '')
    {
        $subject = $this->encode_header($subject, $this->charset);
        $this->message->setSubject($subject);
    }

    /**
     * 
     * Устанавливает Кому
     * 
     * @param string $mail_to
     * @param string $name_to
     */
    public function set_to($mail_to, $name_to = '')
    {
        $mail_to_arr = array();
        $name_to_arr = array();
        
        $tmp_to = explode(",", $mail_to);
        if (count($tmp_to) > 1) {
            $mail_to_arr = $tmp_to;
        } else {
            $mail_to_arr = array($mail_to);
        }
        if ($name_to != '') {
            $tmp_name_to = explode(",", $name_to);
            if (count($tmp_name_to) > 1) {
                foreach($tmp_name_to as $value) {
                    array_push($name_to_arr, $this->encode_header($value, $this->charset));
                }
            } else {
                $name_to_arr = array($this->encode_header($name_to, $this->charset));
            }
        }
        if (count($name_to_arr) > 0) {
            foreach ($mail_to_arr as $key=>$mail_val) {
                $this->message->addTo($mail_val, $name_to_arr[$key]);
            }
        } else {
            $this->message->setTo($mail_to_arr);
        }
    }

    /**
     * Устанавливает От кого
     * 
     * @param string $mail_from
     * @param string $name_from
     */
    public function set_from($mail_from, $name_from = '')
    {

        if ($name_from != '') {
            $name_from = $this->encode_header($name_from, $this->charset);
        }
        if ($name_from != '') {
            $this->message->setFrom(array($mail_from => $name_from));
        } else {
            $this->message->setFrom($mail_from);
        }
    }

    /**
     * Инициализируем Swift
     * 
     */
    public function init($new_message = false)
    {
        //устанавливаем кодировку
        if (empty($this->charset)) {
            $this->charset = ($this->core->NC_UNICODE || empty($this->core->NC_CHARSET)) ? "utf-8" : $this->core->NC_CHARSET;
            if (!defined("MAIN_EMAIL_ENCODING")) {
                define("MAIN_EMAIL_ENCODING", $this->charset);
            }
        }
        if (empty($this->message) || $new_message == true) {
            if ($new_message == false) {
                //подключаем swift library
                require_once $this->core->INCLUDE_FOLDER . 'lib/Swift/swift_required.php';
                //активируем транспорт, в зависимости от выбранных на сайте настроек
                if ($this->core->get_settings('SpamUseTransport') == 'Smtp') {
                    //not used
                    $this->isSMTP = true;

                    $this->transport = Swift_SmtpTransport::newInstance()
                      ->setHost(trim($this->core->get_settings('SpamSmtpHost')))
                      ->setPort(trim($this->core->get_settings('SpamSmtpPort')));
                    if ($this->core->get_settings('SpamSmtpAuthUse') == 1) {
                        $this->transport->setUsername($this->core->get_settings('SpamSmtpUser'))->setPassword($this->core->get_settings('SpamSmtpPass'));
                    }
                    if ($this->core->get_settings('SpamSmtpEncryption') != '') {
                        $this->transport->setEncryption($this->core->get_settings('SpamSmtpEncryption'));
                    }
                } else if ($this->core->get_settings('SpamUseTransport') == 'Sendmail') {
                    $this->transport = Swift_SendmailTransport::newInstance($this->core->get_settings('SpamSendmailCommand'));
                } else {
                    $this->transport = Swift_MailTransport::newInstance();
                }
                //собственно Swift
                $this->swiftMailer = Swift_Mailer::newInstance($this->transport);
            }
            //объект сообщения
            $this->message = Swift_Message::newInstance(null)->setCharset($this->charset);
            $this->message->setPriority($this->priority);
        }
    }

    /**
     * 
     * Фунцкия для корректной работы Mail_Transport с заголовками
     * для SMTP_Transport необязательна
     * 
     * @param string $input
     * @param string $charset
     * @return string
     */
    public function encode_header($input, $charset = 'utf-8')
    {
        //$input = chunk_split($input);

        // add encoding to the beginning of each line
        $str = "=?$charset?B?" . base64_encode($input) . "?=";
        return $str;
    }

    /**
     * 
     * Убирает все аттачи из письма
     * 
     */
    public function clear()
    {
        $this->init();
        $parts = $this->message->getChildren();
        if (count($parts) > 0) {
            foreach ($parts as $part) {
                $this->message->detach($part);
            }
        }
    }

    /**
     * 
     * Аналог nc_mail_attachment_attach
     * 
     * @param type $body
     * @param type $types
     * @return type
     */
    public function attachment_attach($body, $types)
    {
        $types_escaped = array();

        if (!is_array($types)) {
            $types = array($types);
        }

        foreach ($types as $type) {
            $types_escaped[] = '\'' . $this->core->db->escape($type) . '\'';
        }

        $sql = "SELECT `Filename`, `Path`, `Content_Type`, `Extension` FROM `Mail_Attachment` WHERE `Type` IN (" . implode(',', $types_escaped) . ")";
        $attachments = (array) $this->core->db->get_results($sql, ARRAY_A);

        while (preg_match('/\%FILE_([-_a-z0-9]+)/i', $body, $match)) {
            $filename = $match[1];

            $file = false;

            foreach ($attachments as $index => $attachment) {
                if (strtolower($attachment['Filename']) == strtolower($filename)) {
                    $file = $attachment;
                    unset($attachments[$index]);
                    break;
                }
            }

            $replace = '';
            if ($file) {
                $absolute_path = $this->core->DOCUMENT_ROOT . $file['Path'];
                $replace = 'cid:' . $this->attachFileEmbed($absolute_path, $filename . '.' . $file['Extension'], $file['Content_Type']);
            }

            $body = preg_replace('/\%FILE_' . preg_quote($filename) . '/', $replace, $body);
        }

        foreach ($attachments as $attachment) {
            $absolute_path = $this->core->DOCUMENT_ROOT . $attachment['Path'];
            $this->attachFileEmbed($absolute_path, $attachment['Filename'] . '.' . $attachment['Extension'], $attachment['Content_Type']);
        }

        return $body;
    }

}
