<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Service centralisé pour l'envoi d'emails
 * 
 * Ce service encapsule toute la logique d'envoi d'emails de l'application CFA.
 * Il gère les templates Twig, les pièces jointes, et la gestion des erreurs.
 */
class EmailService
{
    private string $fromAddress;
    private string $fromName;

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        string $fromAddress = 'noreply@cfa.ericm.fr',
        string $fromName = 'CFA Gestion'
    ) {
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    /**
     * Envoie un email simple (texte brut)
     */
    public function sendSimpleEmail(
        string $to,
        string $subject,
        string $textContent,
        ?string $htmlContent = null
    ): EmailResult {
        $email = (new Email())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->text($textContent);

        if ($htmlContent !== null) {
            $email->html($htmlContent);
        }

        return $this->doSend($email);
    }

    /**
     * Envoie un email basé sur un template Twig
     * 
     * @param string $to Adresse destinataire
     * @param string $subject Sujet de l'email
     * @param string $template Chemin du template Twig (ex: 'email/notification.html.twig')
     * @param array $context Variables à passer au template
     * @param array $attachments Pièces jointes [['path' => '/path/to/file', 'name' => 'filename.pdf'], ...]
     */
    public function sendTemplatedEmail(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        array $attachments = []
    ): EmailResult {
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        // Ajout des pièces jointes
        foreach ($attachments as $attachment) {
            if (isset($attachment['path']) && file_exists($attachment['path'])) {
                $name = $attachment['name'] ?? basename($attachment['path']);
                $email->attachFromPath($attachment['path'], $name);
            }
        }

        return $this->doSend($email);
    }

    /**
     * Envoie un email à plusieurs destinataires
     */
    public function sendBulkEmail(
        array $recipients,
        string $subject,
        string $template,
        array $context = []
    ): array {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $recipientEmail = is_array($recipient) ? $recipient['email'] : $recipient;
            
            // Personnalisation du contexte par destinataire si nécessaire
            $recipientContext = $context;
            if (is_array($recipient)) {
                $recipientContext = array_merge($context, $recipient);
            }
            
            $results[$recipientEmail] = $this->sendTemplatedEmail(
                $recipientEmail,
                $subject,
                $template,
                $recipientContext
            );
            
            // Petit délai pour éviter de surcharger le serveur SMTP
            usleep(100000); // 100ms
        }
        
        return $results;
    }

    /**
     * Envoie un email de notification système (pour les admins)
     */
    public function sendSystemNotification(
        string $subject,
        string $message,
        string $level = 'info'
    ): EmailResult {
        return $this->sendTemplatedEmail(
            $this->fromAddress, // S'envoie à lui-même pour les notifs système
            "[CFA System] $subject",
            'email/system_notification.html.twig',
            [
                'message' => $message,
                'level' => $level,
                'timestamp' => new \DateTime(),
            ]
        );
    }

    /**
     * Effectue l'envoi réel et gère les erreurs
     */
    private function doSend(Email $email): EmailResult
    {
        $result = new EmailResult();
        $result->recipient = $email->getTo()[0]->getAddress();
        $result->subject = $email->getSubject();
        $result->sentAt = new \DateTime();

        try {
            $this->mailer->send($email);
            $result->success = true;
            $result->message = 'Email envoyé avec succès';
            
            $this->logger->info('Email envoyé', [
                'to' => $result->recipient,
                'subject' => $result->subject,
            ]);
        } catch (TransportExceptionInterface $e) {
            $result->success = false;
            $result->message = 'Erreur de transport: ' . $e->getMessage();
            $result->errorCode = $e->getCode();
            
            $this->logger->error('Échec envoi email', [
                'to' => $result->recipient,
                'subject' => $result->subject,
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $result->success = false;
            $result->message = 'Erreur inattendue: ' . $e->getMessage();
            $result->errorCode = $e->getCode();
            
            $this->logger->error('Erreur email inattendue', [
                'to' => $result->recipient,
                'subject' => $result->subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $result;
    }

    /**
     * Teste la configuration email
     */
    public function testConfiguration(string $testEmail): EmailResult
    {
        return $this->sendTemplatedEmail(
            $testEmail,
            'Test de configuration email - CFA Gestion',
            'email/test.html.twig',
            [
                'test_date' => new \DateTime(),
                'server_info' => [
                    'hostname' => gethostname(),
                    'php_version' => PHP_VERSION,
                    'mailer_dsn' => $_ENV['MAILER_DSN'] ?? 'non configuré',
                ],
            ]
        );
    }
}

/**
 * Classe représentant le résultat d'un envoi d'email
 */
class EmailResult
{
    public bool $success = false;
    public string $message = '';
    public ?int $errorCode = null;
    public string $recipient = '';
    public string $subject = '';
    public ?\DateTime $sentAt = null;

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'sent_at' => $this->sentAt?->format('Y-m-d H:i:s'),
        ];
    }
}
