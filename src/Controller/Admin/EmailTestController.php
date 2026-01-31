<?php

namespace App\Controller\Admin;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/email')]
#[IsGranted('ROLE_ADMIN')]
class EmailTestController extends AbstractController
{
    public function __construct(
        private EmailService $emailService
    ) {}

    #[Route('/test', name: 'admin_email_test', methods: ['GET', 'POST'])]
    public function testEmail(Request $request): Response
    {
        $result = null;
        $diagnostics = $this->getDiagnostics();
        
        // Adresse de test par défaut
        $defaultEmail = 'erictomcat1@googlemail.com';
        
        if ($request->isMethod('POST')) {
            $testEmail = $request->request->get('test_email', $defaultEmail);
            
            // Validation basique de l'email
            if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Adresse email invalide.');
            } else {
                // Envoi du test
                $result = $this->emailService->testConfiguration($testEmail);
                
                if ($result->success) {
                    $this->addFlash('success', 'Email de test envoyé avec succès à ' . $testEmail);
                } else {
                    $this->addFlash('error', 'Échec de l\'envoi: ' . $result->message);
                }
            }
        }

        return $this->render('admin/email/test.html.twig', [
            'result' => $result,
            'diagnostics' => $diagnostics,
            'default_email' => $defaultEmail,
        ]);
    }

    #[Route('/preview/{template}', name: 'admin_email_preview', methods: ['GET'])]
    public function previewTemplate(string $template): Response
    {
        // Templates disponibles pour prévisualisation
        $templates = [
            'test' => [
                'template' => 'email/test.html.twig',
                'context' => [
                    'test_date' => new \DateTime(),
                    'server_info' => [
                        'hostname' => gethostname(),
                        'php_version' => PHP_VERSION,
                        'mailer_dsn' => $_ENV['MAILER_DSN'] ?? 'sendmail://default',
                    ],
                ],
            ],
            'system' => [
                'template' => 'email/system_notification.html.twig',
                'context' => [
                    'message' => "Ceci est un exemple de notification système.\nLe système fonctionne correctement.",
                    'level' => 'info',
                    'timestamp' => new \DateTime(),
                ],
            ],
            'system_warning' => [
                'template' => 'email/system_notification.html.twig',
                'context' => [
                    'message' => "Attention: Espace disque faible sur le serveur.\nIl reste moins de 10% d'espace disponible.",
                    'level' => 'warning',
                    'timestamp' => new \DateTime(),
                ],
            ],
            'system_error' => [
                'template' => 'email/system_notification.html.twig',
                'context' => [
                    'message' => "Erreur critique détectée!\nLa connexion à la base de données a échoué.",
                    'level' => 'error',
                    'timestamp' => new \DateTime(),
                ],
            ],
        ];

        if (!isset($templates[$template])) {
            throw $this->createNotFoundException('Template non trouvé');
        }

        $config = $templates[$template];
        
        return $this->render($config['template'], $config['context']);
    }

    /**
     * Récupère les informations de diagnostic pour l'affichage
     */
    private function getDiagnostics(): array
    {
        $diagnostics = [
            'server' => [
                'label' => 'Serveur',
                'value' => gethostname(),
                'status' => 'ok',
            ],
            'php_version' => [
                'label' => 'Version PHP',
                'value' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '8.2.0', '>=') ? 'ok' : 'warning',
            ],
            'mailer_dsn' => [
                'label' => 'Configuration Mailer',
                'value' => $this->obfuscateDsn($_ENV['MAILER_DSN'] ?? 'non configuré'),
                'status' => !empty($_ENV['MAILER_DSN']) ? 'ok' : 'error',
            ],
            'from_address' => [
                'label' => 'Adresse expéditeur',
                'value' => $_ENV['MAILER_FROM_ADDRESS'] ?? 'noreply@cfa.ericm.fr',
                'status' => 'ok',
            ],
        ];

        // Vérification de sendmail/postfix
        $sendmailPath = ini_get('sendmail_path');
        $diagnostics['sendmail'] = [
            'label' => 'Sendmail Path',
            'value' => $sendmailPath ?: 'Non configuré',
            'status' => !empty($sendmailPath) ? 'ok' : 'warning',
        ];

        // Vérification si postfix est actif
        $postfixStatus = $this->checkPostfixStatus();
        $diagnostics['postfix'] = [
            'label' => 'Service Postfix',
            'value' => $postfixStatus['message'],
            'status' => $postfixStatus['status'],
        ];

        return $diagnostics;
    }

    /**
     * Vérifie le statut de Postfix
     */
    private function checkPostfixStatus(): array
    {
        // Sur un serveur Linux, on vérifie si postfix est en cours d'exécution
        if (PHP_OS_FAMILY === 'Linux') {
            exec('systemctl is-active postfix 2>/dev/null', $output, $returnCode);
            
            if ($returnCode === 0 && isset($output[0]) && $output[0] === 'active') {
                return ['status' => 'ok', 'message' => 'Actif'];
            } elseif ($returnCode === 0) {
                return ['status' => 'warning', 'message' => $output[0] ?? 'Statut inconnu'];
            }
            
            // Alternative: vérifier le processus
            exec('pgrep -x postfix 2>/dev/null', $output2, $returnCode2);
            if ($returnCode2 === 0) {
                return ['status' => 'ok', 'message' => 'En cours d\'exécution'];
            }
        }

        return ['status' => 'warning', 'message' => 'Non vérifié'];
    }

    /**
     * Masque les informations sensibles du DSN
     */
    private function obfuscateDsn(string $dsn): string
    {
        // Masque les mots de passe dans le DSN
        return preg_replace('/:([^@]+)@/', ':***@', $dsn);
    }
}
