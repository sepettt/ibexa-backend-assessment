<?php

declare(strict_types=1);

namespace App\Command;

use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:content-types:create-simple',
    description: 'Creates simplified content types for testing'
)]
class CreateSimpleContentTypesCommand extends Command
{
    public function __construct(
        private Repository $repository,
        private ContentTypeService $contentTypeService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creating Simplified Content Types');

        try {
            $this->repository->sudo(function () use ($io) {
                $this->createNewsContentType($io);
                $this->createInsightsContentType($io);
                $this->createRedirectContentType($io);
            });

            $io->success('All content types created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create content types: ' . $e->getMessage());
            $io->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function createNewsContentType(SymfonyStyle $io): void
    {
        $io->section('Creating News Content Type');

        try {
            $this->contentTypeService->loadContentTypeByIdentifier('news');
            $io->note('News content type already exists, skipping...');
            return;
        } catch (NotFoundException $e) {
            // Content type doesn't exist, create it
        }

        $contentTypeGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeCreateStruct = $this->contentTypeService->newContentTypeCreateStruct('news');

        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->nameSchema = '<title>';
        $contentTypeCreateStruct->urlAliasSchema = '<title>';
        $contentTypeCreateStruct->names = [
            'eng-GB' => 'News',
        ];
        $contentTypeCreateStruct->descriptions = [
            'eng-GB' => 'News articles',
        ];
        $contentTypeCreateStruct->isContainer = false;

        // Title
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('title', 'ezstring');
        $field->names = [
            'eng-GB' => 'Title',
        ];
        $field->position = 10;
        $field->isTranslatable = true;
        $field->isRequired = true;
        $field->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        // Summary
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('summary', 'eztext');
        $field->names = [
            'eng-GB' => 'Summary',
        ];
        $field->position = 20;
        $field->isTranslatable = true;
        $field->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        // Body
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('body', 'ezrichtext');
        $field->names = [
            'eng-GB' => 'Body',
        ];
        $field->position = 30;
        $field->isTranslatable = true;
        $field->isRequired = true;
        $field->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        // Publication Date
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('publication_date', 'ezdatetime');
        $field->names = [
            'eng-GB' => 'Publication Date',
        ];
        $field->position = 40;
        $field->isRequired = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        $contentTypeDraft = $this->contentTypeService->createContentType($contentTypeCreateStruct, [$contentTypeGroup]);
        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $io->success('News content type created');
    }

    private function createInsightsContentType(SymfonyStyle $io): void
    {
        $io->section('Creating Insights Content Type');

        try {
            $this->contentTypeService->loadContentTypeByIdentifier('insights');
            $io->note('Insights content type already exists, skipping...');
            return;
        } catch (NotFoundException $e) {
            // Content type doesn't exist, create it
        }

        $contentTypeGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeCreateStruct = $this->contentTypeService->newContentTypeCreateStruct('insights');

        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->nameSchema = '<title>';
        $contentTypeCreateStruct->urlAliasSchema = '<title>';
        $contentTypeCreateStruct->names = [
            'eng-GB' => 'Insights',
        ];
        $contentTypeCreateStruct->descriptions = [
            'eng-GB' => 'Insights and analysis articles',
        ];
        $contentTypeCreateStruct->isContainer = false;

        // Title
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('title', 'ezstring');
        $field->names = [
            'eng-GB' => 'Title',
        ];
        $field->position = 10;
        $field->isTranslatable = true;
        $field->isRequired = true;
        $field->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        // Body
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('body', 'ezrichtext');
        $field->names = [
            'eng-GB' => 'Body',
        ];
        $field->position = 20;
        $field->isTranslatable = true;
        $field->isRequired = true;
        $field->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        // Publication Date
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('publication_date', 'ezdatetime');
        $field->names = [
            'eng-GB' => 'Publication Date',
        ];
        $field->position = 30;
        $field->isRequired = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        $contentTypeDraft = $this->contentTypeService->createContentType($contentTypeCreateStruct, [$contentTypeGroup]);
        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $io->success('Insights content type created');
    }

    private function createRedirectContentType(SymfonyStyle $io): void
    {
        $io->section('Creating Redirect Content Type');

        try {
            $this->contentTypeService->loadContentTypeByIdentifier('redirect');
            $io->note('Redirect content type already exists, skipping...');
            return;
        } catch (NotFoundException $e) {
            // Content type doesn't exist, create it
        }

        $contentTypeGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeCreateStruct = $this->contentTypeService->newContentTypeCreateStruct('redirect');

        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->nameSchema = '<source_url>';
        $contentTypeCreateStruct->names = [
            'eng-GB' => 'Redirect',
        ];
        $contentTypeCreateStruct->descriptions = [
            'eng-GB' => 'URL Redirect mapping',
        ];
        $contentTypeCreateStruct->isContainer = false;

        // Source URL
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('source_url', 'ezstring');
        $field->names = [
            'eng-GB' => 'Source URL',
        ];
        $field->position = 10;
        $field->isRequired = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        // Target URL
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('target_url', 'ezurl');
        $field->names = [
            'eng-GB' => 'Target URL',
        ];
        $field->position = 20;
        $field->isRequired = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        // Redirect Type (301 or 302)
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('redirect_type', 'ezinteger');
        $field->names = [
            'eng-GB' => 'Redirect Type',
        ];
        $field->descriptions = [
            'eng-GB' => '301 for permanent, 302 for temporary',
        ];
        $field->position = 30;
        $field->isRequired = true;
        $field->defaultValue = 301;
        $contentTypeCreateStruct->addFieldDefinition($field);

        // Active
        $field = $this->contentTypeService->newFieldDefinitionCreateStruct('active', 'ezboolean');
        $field->names = [
            'eng-GB' => 'Active',
        ];
        $field->position = 40;
        $field->defaultValue = true;
        $contentTypeCreateStruct->addFieldDefinition($field);

        $contentTypeDraft = $this->contentTypeService->createContentType($contentTypeCreateStruct, [$contentTypeGroup]);
        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $io->success('Redirect content type created');
    }
}
