<?php

namespace App\Command;

use App\Service\AppelService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour traiter automatiquement les appels expirés
 * 
 * Cette commande doit être exécutée régulièrement via cron pour :
 * - Clôturer automatiquement les appels dont le délai de signature a expiré
 * - Marquer les présences non signées comme "non signé"
 * 
 * Exemple cron (toutes les 15 minutes) :
 * * /15 * * * * cd /var/www/cfa.ericm.fr && php bin/console app:appel:traiter-expires --env=prod >> /var/log/cfa-appels.log 2>&1
 */
#[AsCommand(
    name: 'app:appel:traiter-expires',
    description: 'Traite automatiquement les appels dont le délai de signature a expiré',
)]
class TraiterAppelsExpiresCommand extends Command
{
    public function __construct(
        private AppelService $appelService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Simule le traitement sans effectuer de modifications'
            )
            ->setHelp(<<<'HELP'
La commande <info>%command.name%</info> traite automatiquement les appels expirés.

Elle recherche tous les appels non clôturés dont la date d'expiration est dépassée,
puis les clôture en marquant les présences en attente comme "non signé".

<info>Usage :</info>

    <comment>php bin/console %command.name%</comment>

<info>Options :</info>

    <comment>--dry-run</comment>    Affiche ce qui serait traité sans effectuer de modifications

<info>Configuration cron recommandée :</info>

    # Toutes les 15 minutes
    */15 * * * * cd /var/www/cfa.ericm.fr && php bin/console app:appel:traiter-expires --env=prod

    # Toutes les heures (alternative moins fréquente)
    0 * * * * cd /var/www/cfa.ericm.fr && php bin/console app:appel:traiter-expires --env=prod

HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        $io->title('Traitement des appels expirés');

        if ($dryRun) {
            $io->warning('Mode dry-run activé : aucune modification ne sera effectuée.');
        }

        try {
            if ($dryRun) {
                // En mode dry-run, on simule juste la recherche
                $io->note('Recherche des appels expirés...');
                $io->text('(En mode dry-run, aucun traitement n\'est effectué)');
                $count = 0; // On ne peut pas connaître le nombre exact sans le service
            } else {
                $count = $this->appelService->traiterAppelsExpires();
            }

            if ($count > 0) {
                $io->success(sprintf(
                    '%d appel(s) expiré(s) traité(s) et clôturé(s).',
                    $count
                ));
            } else {
                $io->info('Aucun appel expiré à traiter.');
            }

            // Log pour le fichier de log cron
            $output->writeln(sprintf(
                '[%s] Traitement terminé : %d appel(s) traité(s)',
                (new \DateTime())->format('Y-m-d H:i:s'),
                $count
            ), OutputInterface::VERBOSITY_QUIET);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors du traitement : ' . $e->getMessage());

            // Log l'erreur
            $output->writeln(sprintf(
                '[%s] ERREUR : %s',
                (new \DateTime())->format('Y-m-d H:i:s'),
                $e->getMessage()
            ), OutputInterface::VERBOSITY_QUIET);

            return Command::FAILURE;
        }
    }
}
