<?php

namespace app\core\utils;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SESSION['path']['root'] . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Classe para envio de e-mails utilizando PHPMailer.
 */
class Mail{
    /**
     * @var string|array
     */
    private $to;
    
    /**
     * @var string
     */
    private $subject;
    
    /**
     * @var string
     */
    private $body;
    
    /**
     * @var array
     */
    private $headers = [];
    
    /**
     * @var array
     */
    private $attachments = [];
    
    /**
     * @var string
     */
    private $lastError = '';
    
    /**
     * Construtor.
     *
     * @param string|array $to
     * @param string $subject
     * @param string $body
     */
    public function __construct($to, $subject, $body){
        $this->to      = $to;
        $this->subject = $subject;
        $this->body    = $body;
    }
    
    /**
     * Adiciona cabeçalho personalizado.
     *
     * @param string $header
     * @return void
     */
    public function addHeader($header){
        $this->headers[] = $header;
    }
    
    /**
     * Adiciona anexo por arquivo.
     *
     * @param string $file
     * @return void
     */
    public function addAttachment($file){
        if (file_exists($file)) {
            $this->attachments[] = [
                'type' => 'file',
                'file' => $file
            ];
        }
    }
    
    /**
     * Adiciona anexo a partir de conteúdo base64.
     *
     * @param string $filename
     * @param string $data
     * @param string $mimeType
     * @return void
     */
    public function addAttachmentBase64(
        $filename,
        $data,
        $mimeType = 'application/octet-stream'
        ) {
            $this->attachments[] = [
                'type'     => 'string',
                'filename' => $filename,
                'content'  => base64_decode($data),
                'mime'     => $mimeType
            ];
    }
    
    /**
     * Adiciona destinatário em cópia.
     *
     * @param string $email
     * @return void
     */
    public function addCC($email){
        $this->headers[] = [
            'type'  => 'cc',
            'email' => $email
        ];
    }
    
    /**
     * Adiciona destinatário em cópia oculta.
     *
     * @param string $email
     * @return void
     */
    public function addBCC($email){
        $this->headers[] = [
            'type'  => 'bcc',
            'email' => $email
        ];
    }
    
    /**
     * Configura SMTP.
     *
     * @param PHPMailer $mailer
     * @return void
     */
    private function configureSMTP(PHPMailer $mailer){
        $mailer->isSMTP();
        
        $mailer->Host       = $_SESSION['email']['host'];
        $mailer->SMTPAuth   = true;
        $mailer->Username   = $_SESSION['email']['username'];
        $mailer->Password   = $_SESSION['email']['password'];
        $mailer->Port       = (int) $_SESSION['email']['port'];
        $mailer->CharSet    = 'UTF-8';
        
        if ((int) $_SESSION['email']['port'] === 465) {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $mailer->setFrom(
            $_SESSION['email']['username'],
            $_SESSION['system']['name'] ?? 'Sistema'
            );
    }
    
    /**
     * Retorna o último erro.
     *
     * @return string
     */
    public function getLastError(){
        return $this->lastError;
    }
    
    /**
     * Envia o e-mail.
     *
     * @return bool
     */
    public function send(){
        try {
            $mailer = new PHPMailer(true);
            
            $this->configureSMTP($mailer);
            
            $mailer->isHTML(true);
            
            $mailer->Subject = $this->subject;
            $mailer->Body    = $this->body;
            $mailer->AltBody = strip_tags($this->body);
            
            // Destinatários
            if (is_array($this->to)) {
                
                foreach ($this->to as $email) {
                    
                    if (!empty($email)) {
                        $mailer->addAddress(trim($email));
                    }
                }
                
            } else {
                
                $mailer->addAddress(trim($this->to));
            }
            
            // Cabeçalhos / CC / BCC
            foreach ($this->headers as $header) {
                
                if (is_array($header)) {
                    
                    if ($header['type'] === 'cc') {
                        $mailer->addCC($header['email']);
                    }
                    
                    if ($header['type'] === 'bcc') {
                        $mailer->addBCC($header['email']);
                    }
                    
                } else {
                    
                    if (strpos($header, ':') !== false) {
                        
                        list($name, $value) = explode(':', $header, 2);
                        
                        $mailer->addCustomHeader(
                            trim($name),
                            trim($value)
                            );
                    }
                }
            }
            
            // Anexos
            foreach ($this->attachments as $attachment) {
                
                if ($attachment['type'] === 'file') {
                    
                    $mailer->addAttachment(
                        $attachment['file']
                        );
                    
                } elseif ($attachment['type'] === 'string') {
                    
                    $mailer->addStringAttachment(
                        $attachment['content'],
                        $attachment['filename'],
                        PHPMailer::ENCODING_BASE64,
                        $attachment['mime']
                        );
                }
            }
            
            $mailer->send();
            
            return true;
            
        } catch (Exception $e) {
            
            $this->lastError = $mailer->ErrorInfo ?: $e->getMessage();
            
            error_log(
                '[MAIL ERROR] ' .
                $this->lastError
                );
            
            return false;
        }
    }
}