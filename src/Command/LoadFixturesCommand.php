<?php

namespace App\Command;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Core\Base\Exceptions\ContentFieldValidationException;
use Ibexa\Core\FieldType\DateAndTime\Value as DateTimeValue;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fixtures:load',
    description: 'Load sample fixtures and content'
)]
class LoadFixturesCommand extends Command
{
    public function __construct(
        private readonly Repository $repository,
        private readonly UserService $userService,
        private readonly PermissionResolver $permissionResolver,
        private readonly ContentService $contentService,
        private readonly ContentTypeService $contentTypeService,
        private readonly LocationService $locationService,
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

            $io->title('Loading Fixtures');

            // Create sample News content
            $io->section('Creating News content');
            $newsCount = $this->createNewsFixtures($io);
            $io->success(sprintf('Created %d News items', $newsCount));

            // Create sample Insights content
            $io->section('Creating Insights content');
            $insightsCount = $this->createInsightsFixtures($io);
            $io->success(sprintf('Created %d Insights items', $insightsCount));

            // Create sample Redirects
            $io->section('Creating Redirects');
            $redirectCount = $this->createRedirectFixtures($io);
            $io->success(sprintf('Created %d Redirects', $redirectCount));

            $io->success('All fixtures loaded successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Loading fixtures failed: ' . $e->getMessage());
            $io->note('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function createNewsFixtures(SymfonyStyle $io): int
    {
        $newsContentType = $this->contentTypeService->loadContentTypeByIdentifier('news');
        $parentLocation = $this->locationService->loadLocation(2);

        $newsItems = [
            [
                'title' => 'Digital Transformation Success Story',
                'summary' => 'How our client achieved 300% efficiency improvement',
                'body' => '<?xml version="1.0" encoding="UTF-8"?><section xmlns="http://docbook.org/ns/docbook"><para>Digital transformation delivers measurable results.</para></section>',
                'days_ago' => 5,
            ],
            [
                'title' => 'Cloud Migration Best Practices',
                'summary' => 'Essential strategies for successful cloud migration',
                'body' => '<?xml version="1.0" encoding="UTF-8"?><section xmlns="http://docbook.org/ns/docbook"><para>Successful cloud migrations require careful planning.</para></section>',
                'days_ago' => 3,
            ],
            [
                'title' => 'AI and Machine Learning in Business',
                'summary' => 'Practical applications of AI technology',
                'body' => '<?xml version="1.0" encoding="UTF-8"?><section xmlns="http://docbook.org/ns/docbook"><para>AI is transforming modern business operations.</para></section>',
                'days_ago' => 1,
            ],
        ];

        $count = 0;
        foreach ($newsItems as $data) {
            try {
                $contentCreate = $this->contentService->newContentCreateStruct($newsContentType, 'eng-GB');
                $contentCreate->setField('title', $data['title']);
                $contentCreate->setField('summary', $data['summary']);
                $contentCreate->setField('body', '<?xml version="1.0" encoding="UTF-8"?><section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0"><para>' . $data['summary'] . '</para></section>');
                $contentCreate->setField('publication_date', DateTimeValue::fromTimestamp((new \DateTime('-' . $data['days_ago'] . ' days'))->getTimestamp()));

                $locationCreate = $this->locationService->newLocationCreateStruct($parentLocation->id);
                $draft = $this->contentService->createContent($contentCreate, [$locationCreate]);
                $this->contentService->publishVersion($draft->versionInfo);

                $io->writeln('  - ' . $data['title']);
                $count++;
            } catch (ContentFieldValidationException $e) {
                $io->warning('Failed to create: ' . $data['title']);
                $io->writeln('  Field errors: ' . json_encode($e->getFieldErrors(), JSON_PRETTY_PRINT));
            } catch (\Exception $e) {
                // Solr errors don't prevent content creation
                if (strpos($e->getMessage(), 'solr') !== false) {
                    $io->writeln('  - ' . $data['title'] . ' (Solr warning)');
                    $count++;
                } else {
                    $io->warning('Failed to create: ' . $data['title'] . ' - ' . $e->getMessage());
                }
            }
        }

        return $count;
    }

    private function createInsightsFixtures(SymfonyStyle $io): int
    {
        $insightsContentType = $this->contentTypeService->loadContentTypeByIdentifier('insights');
        $parentLocation = $this->locationService->loadLocation(2);

        $insightsItems = [
            [
                'title' => 'The Future of Remote Work',
                'days_ago' => 7,
            ],
            [
                'title' => 'Cybersecurity Trends 2024',
                'days_ago' => 4,
            ],
        ];

        $count = 0;
        foreach ($insightsItems as $data) {
            try {
                $contentCreate = $this->contentService->newContentCreateStruct($insightsContentType, 'eng-GB');
                $contentCreate->setField('title', $data['title']);
                $contentCreate->setField('body', '<?xml version="1.0" encoding="UTF-8"?><section xmlns="http://docbook.org/ns/docbook" xmlns:xlink="http://www.w3.org/1999/xlink" version="5.0-variant ezpublish-1.0"><para>' . $data['title'] . '</para></section>');
                $contentCreate->setField('publication_date', DateTimeValue::fromTimestamp((new \DateTime('-' . $data['days_ago'] . ' days'))->getTimestamp()));

                $locationCreate = $this->locationService->newLocationCreateStruct($parentLocation->id);
                $draft = $this->contentService->createContent($contentCreate, [$locationCreate]);
                $this->contentService->publishVersion($draft->versionInfo);

                $io->writeln('  - ' . $data['title']);
                $count++;
            } catch (\Exception $e) {
                // Solr errors don't prevent content creation
                if (strpos($e->getMessage(), 'solr') !== false) {
                    $io->writeln('  - ' . $data['title'] . ' (Solr warning)');
                    $count++;
                } else {
                    $io->warning('Failed to create: ' . $data['title'] . ' - ' . $e->getMessage());
                }
            }
        }

        return $count;
    }

    private function createRedirectFixtures(SymfonyStyle $io): int
    {
        $redirectContentType = $this->contentTypeService->loadContentTypeByIdentifier('redirect');
        $parentLocation = $this->locationService->loadLocation(2);

        $redirects = [
            [
                'source_url' => '/old-blog/article-1',
                'target_url' => '/news/digital-transformation',
                'type' => 301,
                'active' => true,
            ],
            [
                'source_url' => '/services',
                'target_url' => '/about/our-services',
                'type' => 301,
                'active' => true,
            ],
            [
                'source_url' => '/promo/spring',
                'target_url' => '/promotions/current',
                'type' => 302,
                'active' => false,
            ],
        ];

        $count = 0;
        foreach ($redirects as $data) {
            try {
                $contentCreate = $this->contentService->newContentCreateStruct($redirectContentType, 'eng-GB');
                $contentCreate->setField('source_url', $data['source_url']);
                $contentCreate->setField('target_url', $data['target_url']);
                $contentCreate->setField('redirect_type', $data['type']);
                $contentCreate->setField('active', $data['active']);

                $locationCreate = $this->locationService->newLocationCreateStruct($parentLocation->id);
                $draft = $this->contentService->createContent($contentCreate, [$locationCreate]);
                $this->contentService->publishVersion($draft->versionInfo);

                $io->writeln(sprintf('  - %s → %s (%d)', $data['source_url'], $data['target_url'], $data['type']));
                $count++;
            } catch (\Exception $e) {
                // Solr errors don't prevent content creation
                if (strpos($e->getMessage(), 'solr') !== false) {
                    $io->writeln(sprintf('  - %s → %s (%d) (Solr warning)', $data['source_url'], $data['target_url'], $data['type']));
                    $count++;
                } else {
                    $io->warning('Failed to create redirect: ' . $e->getMessage());
                }
            }
        }

        return $count;
    }
}
