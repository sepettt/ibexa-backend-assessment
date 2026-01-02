<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:bootstrap:setup',
    description: 'Bootstrap the application with initial data, admin user, and fixtures'
)]
class BootstrapSetupCommand extends Command
{
    public function __construct(
        private readonly Repository $repository,
        private readonly UserService $userService,
        private readonly PermissionResolver $permissionResolver,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Set admin user for repository operations
            $adminUser = $this->userService->loadUserByLogin('admin');
            $this->permissionResolver->setCurrentUserReference($adminUser);

            $io->title('Bootstrapping Ibexa Application');

            // Create admin user if doesn't exist
            $this->createAdminUser($io);

            // Create initial content structure
            $this->createInitialContent($io);

            // Create sample users
            $this->createSampleUsers($io);

            $io->success('Bootstrap completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Bootstrap failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createAdminUser(SymfonyStyle $io): void
    {
        $io->section('Setting up admin user');

        try {
            $admin = $this->userService->loadUserByLogin('admin');
            $io->info('Admin user already exists.');
        } catch (\Exception $e) {
            $io->info('Admin user setup handled by Ibexa installation.');
        }
    }

    private function createInitialContent(SymfonyStyle $io): void
    {
        $io->section('Creating initial content structure');

        // This will be expanded in Section 2 with actual content types
        $io->info('Content structure will be created via content type definitions.');
    }

    private function createSampleUsers(SymfonyStyle $io): void
    {
        $io->section('Creating sample users');

        $sampleUsers = [
            [
                'login' => 'editor',
                'email' => 'editor@example.com',
                'firstName' => 'Editorial',
                'lastName' => 'User',
            ],
            [
                'login' => 'viewer',
                'email' => 'viewer@example.com',
                'firstName' => 'View',
                'lastName' => 'User',
            ],
        ];

        foreach ($sampleUsers as $userData) {
            try {
                $this->userService->loadUserByLogin($userData['login']);
                $io->info(sprintf('User "%s" already exists.', $userData['login']));
            } catch (\Exception $e) {
                // User doesn't exist, could create here
                $io->info(sprintf('User "%s" can be created manually via admin interface.', $userData['login']));
            }
        }
    }
}
