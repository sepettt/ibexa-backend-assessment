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
    name: 'app:content-types:create',
    description: 'Creates all custom content types for the project'
)]
class CreateContentTypesCommand extends Command
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

        $io->title('Creating Content Types');

        try {
            // Use sudo to execute with admin permissions without loading user
            $this->repository->sudo(function () use ($io) {
                $this->createNewsContentType($io);
                $this->createInsightsContentType($io);
                $this->createRedirectContentType($io);
                $this->createLandingPageContentType($io);
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
            $contentType = $this->contentTypeService->loadContentTypeByIdentifier('news');
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
            'eng-GB' => 'News and media articles',
        ];
        $contentTypeCreateStruct->isContainer = false;
        $contentTypeCreateStruct->defaultSortField = 1; // PATH
        $contentTypeCreateStruct->defaultSortOrder = 1; // ASC
        $contentTypeCreateStruct->defaultAlwaysAvailable = false;

        // Title field
        $titleField = $this->contentTypeService->newFieldDefinitionCreateStruct('title', 'ezstring');
        $titleField->names = [
            'eng-GB' => 'Title',
        ];
        $titleField->descriptions = [
            'eng-GB' => 'Article title',
        ];
        $titleField->fieldGroup = 'content';
        $titleField->position = 10;
        $titleField->isTranslatable = true;
        $titleField->isRequired = true;
        $titleField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($titleField);

        // Summary field
        $summaryField = $this->contentTypeService->newFieldDefinitionCreateStruct('summary', 'eztext');
        $summaryField->names = [
            'eng-GB' => 'Summary',
        ];
        $summaryField->descriptions = [
            'eng-GB' => 'Short summary or lead',
        ];
        $summaryField->fieldGroup = 'content';
        $summaryField->position = 20;
        $summaryField->isTranslatable = true;
        $summaryField->isRequired = false;
        $summaryField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($summaryField);

        // Body field
        $bodyField = $this->contentTypeService->newFieldDefinitionCreateStruct('body', 'ezrichtext');
        $bodyField->names = [
            'eng-GB' => 'Body',
        ];
        $bodyField->descriptions = [
            'eng-GB' => 'Main article content',
        ];
        $bodyField->fieldGroup = 'content';
        $bodyField->position = 30;
        $bodyField->isTranslatable = true;
        $bodyField->isRequired = true;
        $bodyField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($bodyField);

        // Featured Image field
        $imageField = $this->contentTypeService->newFieldDefinitionCreateStruct('featured_image', 'ezimage');
        $imageField->names = [
            'eng-GB' => 'Featured Image',
        ];
        $imageField->descriptions = [
            'eng-GB' => 'Main article image',
        ];
        $imageField->fieldGroup = 'media';
        $imageField->position = 40;
        $imageField->isTranslatable = false;
        $imageField->isRequired = false;
        $imageField->isSearchable = false;
        $contentTypeCreateStruct->addFieldDefinition($imageField);

        // Publication Date field
        $dateField = $this->contentTypeService->newFieldDefinitionCreateStruct('publication_date', 'ezdatetime');
        $dateField->names = [
            'eng-GB' => 'Publication Date',
        ];
        $dateField->descriptions = [
            'eng-GB' => 'When the article is published',
        ];
        $dateField->fieldGroup = 'metadata';
        $dateField->position = 50;
        $dateField->isTranslatable = false;
        $dateField->isRequired = true;
        $dateField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($dateField);

        // SEO Title field
        $seoTitleField = $this->contentTypeService->newFieldDefinitionCreateStruct('seo_title', 'ezstring');
        $seoTitleField->names = [
            'eng-GB' => 'SEO Meta Title',
        ];
        $seoTitleField->descriptions = [
            'eng-GB' => 'Title for search engines (max 60 chars)',
        ];
        $seoTitleField->fieldGroup = 'seo';
        $seoTitleField->position = 80;
        $seoTitleField->isTranslatable = true;
        $seoTitleField->isRequired = false;
        $seoTitleField->isSearchable = false;
        $seoTitleField->validatorConfiguration = [
            'StringLengthValidator' => [
                'maxStringLength' => 60,
            ],
        ];
        $contentTypeCreateStruct->addFieldDefinition($seoTitleField);

        // SEO Description field
        $seoDescField = $this->contentTypeService->newFieldDefinitionCreateStruct('seo_description', 'eztext');
        $seoDescField->names = [
            'eng-GB' => 'SEO Meta Description',
        ];
        $seoDescField->descriptions = [
            'eng-GB' => 'Description for search engines (max 160 chars)',
        ];
        $seoDescField->fieldGroup = 'seo';
        $seoDescField->position = 90;
        $seoDescField->isTranslatable = true;
        $seoDescField->isRequired = false;
        $seoDescField->isSearchable = false;
        $seoDescField->validatorConfiguration = [
            'StringLengthValidator' => [
                'maxStringLength' => 160,
            ],
        ];
        $contentTypeCreateStruct->addFieldDefinition($seoDescField);

        $contentTypeDraft = $this->contentTypeService->createContentType($contentTypeCreateStruct, [$contentTypeGroup]);
        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $io->success('News content type created');
    }

    private function createInsightsContentType(SymfonyStyle $io): void
    {
        $io->section('Creating Insights Content Type');

        try {
            $contentType = $this->contentTypeService->loadContentTypeByIdentifier('insights');
            $io->note('Insights content type already exists, skipping...');
            return;
        } catch (NotFoundException $e) {
            // Content type doesn't exist, create it
        }

        $contentTypeGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeCreateStruct = $this->contentTypeService->newContentTypeCreateStruct('insights');

        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->nameSchema = '<title>';
        $contentTypeCreateStruct->urlAliasSchema = 'insights/<title>';
        $contentTypeCreateStruct->names = [
            'eng-GB' => 'Insights',
        ];
        $contentTypeCreateStruct->descriptions = [
            'eng-GB' => 'Thought leadership and analysis content',
        ];
        $contentTypeCreateStruct->isContainer = false;
        $contentTypeCreateStruct->defaultAlwaysAvailable = false;

        // Title
        $titleField = $this->contentTypeService->newFieldDefinitionCreateStruct('title', 'ezstring');
        $titleField->names = [
            'eng-GB' => 'Title',
        ];
        $titleField->fieldGroup = 'content';
        $titleField->position = 10;
        $titleField->isTranslatable = true;
        $titleField->isRequired = true;
        $titleField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($titleField);

        // Subtitle
        $subtitleField = $this->contentTypeService->newFieldDefinitionCreateStruct('subtitle', 'ezstring');
        $subtitleField->names = [
            'eng-GB' => 'Subtitle',
        ];
        $subtitleField->fieldGroup = 'content';
        $subtitleField->position = 20;
        $subtitleField->isTranslatable = true;
        $subtitleField->isRequired = false;
        $subtitleField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($subtitleField);

        // Introduction
        $introField = $this->contentTypeService->newFieldDefinitionCreateStruct('introduction', 'ezrichtext');
        $introField->names = [
            'eng-GB' => 'Introduction',
        ];
        $introField->fieldGroup = 'content';
        $introField->position = 30;
        $introField->isTranslatable = true;
        $introField->isRequired = false;
        $introField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($introField);

        // Body
        $bodyField = $this->contentTypeService->newFieldDefinitionCreateStruct('body', 'ezrichtext');
        $bodyField->names = [
            'eng-GB' => 'Body',
        ];
        $bodyField->fieldGroup = 'content';
        $bodyField->position = 40;
        $bodyField->isTranslatable = true;
        $bodyField->isRequired = true;
        $bodyField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($bodyField);

        // Featured Image
        $imageField = $this->contentTypeService->newFieldDefinitionCreateStruct('featured_image', 'ezimage');
        $imageField->names = [
            'eng-GB' => 'Featured Image',
        ];
        $imageField->fieldGroup = 'media';
        $imageField->position = 50;
        $imageField->isTranslatable = false;
        $imageField->isRequired = false;
        $contentTypeCreateStruct->addFieldDefinition($imageField);

        // Publication Date
        $dateField = $this->contentTypeService->newFieldDefinitionCreateStruct('publication_date', 'ezdatetime');
        $dateField->names = [
            'eng-GB' => 'Publication Date',
        ];
        $dateField->fieldGroup = 'metadata';
        $dateField->position = 70;
        $dateField->isTranslatable = false;
        $dateField->isRequired = true;
        $dateField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($dateField);

        // SEO fields
        $seoTitleField = $this->contentTypeService->newFieldDefinitionCreateStruct('seo_title', 'ezstring');
        $seoTitleField->names = [
            'eng-GB' => 'SEO Meta Title',
        ];
        $seoTitleField->fieldGroup = 'seo';
        $seoTitleField->position = 110;
        $seoTitleField->isTranslatable = true;
        $seoTitleField->validatorConfiguration = [
            'StringLengthValidator' => [
                'maxStringLength' => 60,
            ],
        ];
        $contentTypeCreateStruct->addFieldDefinition($seoTitleField);

        $seoDescField = $this->contentTypeService->newFieldDefinitionCreateStruct('seo_description', 'eztext');
        $seoDescField->names = [
            'eng-GB' => 'SEO Meta Description',
        ];
        $seoDescField->fieldGroup = 'seo';
        $seoDescField->position = 120;
        $seoDescField->isTranslatable = true;
        $seoDescField->validatorConfiguration = [
            'StringLengthValidator' => [
                'maxStringLength' => 160,
            ],
        ];
        $contentTypeCreateStruct->addFieldDefinition($seoDescField);

        $contentTypeDraft = $this->contentTypeService->createContentType($contentTypeCreateStruct, [$contentTypeGroup]);
        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $io->success('Insights content type created');
    }

    private function createRedirectContentType(SymfonyStyle $io): void
    {
        $io->section('Creating Redirect Content Type');

        try {
            $contentType = $this->contentTypeService->loadContentTypeByIdentifier('redirect');
            $io->note('Redirect content type already exists, skipping...');
            return;
        } catch (NotFoundException $e) {
            // Content type doesn't exist, create it
        }

        $contentTypeGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeCreateStruct = $this->contentTypeService->newContentTypeCreateStruct('redirect');

        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->nameSchema = '<source_url> → <target_url>';
        $contentTypeCreateStruct->names = [
            'eng-GB' => 'Redirect',
        ];
        $contentTypeCreateStruct->descriptions = [
            'eng-GB' => 'URL redirect entry',
        ];
        $contentTypeCreateStruct->isContainer = false;
        $contentTypeCreateStruct->defaultAlwaysAvailable = true;

        // Source URL
        $sourceField = $this->contentTypeService->newFieldDefinitionCreateStruct('source_url', 'ezstring');
        $sourceField->names = [
            'eng-GB' => 'Source URL',
        ];
        $sourceField->fieldGroup = 'content';
        $sourceField->position = 10;
        $sourceField->isRequired = true;
        $sourceField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($sourceField);

        // Target URL
        $targetField = $this->contentTypeService->newFieldDefinitionCreateStruct('target_url', 'ezurl');
        $targetField->names = [
            'eng-GB' => 'Target URL',
        ];
        $targetField->fieldGroup = 'content';
        $targetField->position = 20;
        $targetField->isRequired = true;
        $contentTypeCreateStruct->addFieldDefinition($targetField);

        // Redirect Type
        $typeField = $this->contentTypeService->newFieldDefinitionCreateStruct('redirect_type', 'ezselection');
        $typeField->names = [
            'eng-GB' => 'Redirect Type',
        ];
        $typeField->fieldGroup = 'content';
        $typeField->position = 30;
        $typeField->isRequired = true;
        $typeField->fieldSettings = [
            'isMultiple' => false,
            'options' => ['301 Permanent', '302 Temporary'],
        ];
        $contentTypeCreateStruct->addFieldDefinition($typeField);

        // Active
        $activeField = $this->contentTypeService->newFieldDefinitionCreateStruct('active', 'ezboolean');
        $activeField->names = [
            'eng-GB' => 'Active',
        ];
        $activeField->fieldGroup = 'content';
        $activeField->position = 40;
        $activeField->isRequired = true;
        $activeField->defaultValue = true;
        $contentTypeCreateStruct->addFieldDefinition($activeField);

        // Notes
        $notesField = $this->contentTypeService->newFieldDefinitionCreateStruct('notes', 'eztext');
        $notesField->names = [
            'eng-GB' => 'Notes',
        ];
        $notesField->fieldGroup = 'content';
        $notesField->position = 50;
        $notesField->isRequired = false;
        $contentTypeCreateStruct->addFieldDefinition($notesField);

        $contentTypeDraft = $this->contentTypeService->createContentType($contentTypeCreateStruct, [$contentTypeGroup]);
        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $io->success('Redirect content type created');
    }

    private function createLandingPageContentType(SymfonyStyle $io): void
    {
        $io->section('Creating Landing Page Content Type');

        try {
            $contentType = $this->contentTypeService->loadContentTypeByIdentifier('landing_page');
            $io->note('Landing Page content type already exists, skipping...');
            return;
        } catch (NotFoundException $e) {
            // Content type doesn't exist, create it
        }

        $contentTypeGroup = $this->contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeCreateStruct = $this->contentTypeService->newContentTypeCreateStruct('landing_page');

        $contentTypeCreateStruct->mainLanguageCode = 'eng-GB';
        $contentTypeCreateStruct->nameSchema = '<title>';
        $contentTypeCreateStruct->names = [
            'eng-GB' => 'Landing Page',
        ];
        $contentTypeCreateStruct->descriptions = [
            'eng-GB' => 'Page with Page Builder',
        ];
        $contentTypeCreateStruct->isContainer = true;
        $contentTypeCreateStruct->defaultAlwaysAvailable = false;

        // Title
        $titleField = $this->contentTypeService->newFieldDefinitionCreateStruct('title', 'ezstring');
        $titleField->names = [
            'eng-GB' => 'Title',
        ];
        $titleField->fieldGroup = 'content';
        $titleField->position = 10;
        $titleField->isTranslatable = true;
        $titleField->isRequired = true;
        $titleField->isSearchable = true;
        $contentTypeCreateStruct->addFieldDefinition($titleField);

        // Page field - use ezrichtext (ezlandingpage not available in Ibexa OSS)
        $io->note('Using ezrichtext for page content (ezlandingpage is a commercial feature)');
        $pageField = $this->contentTypeService->newFieldDefinitionCreateStruct('page', 'ezrichtext');
        $pageField->names = [
            'eng-GB' => 'Page Content',
        ];
        $pageField->fieldGroup = 'content';
        $pageField->position = 20;
        $pageField->isTranslatable = true;
        $pageField->isRequired = true;
        $contentTypeCreateStruct->addFieldDefinition($pageField);

        // SEO fields (simplified, no validators)
        $seoTitleField = $this->contentTypeService->newFieldDefinitionCreateStruct('seo_title', 'ezstring');
        $seoTitleField->names = [
            'eng-GB' => 'SEO Meta Title',
        ];
        $seoTitleField->fieldGroup = 'seo';
        $seoTitleField->position = 30;
        $seoTitleField->isTranslatable = true;
        $seoTitleField->isRequired = false;
        $contentTypeCreateStruct->addFieldDefinition($seoTitleField);

        $seoDescField = $this->contentTypeService->newFieldDefinitionCreateStruct('seo_description', 'eztext');
        $seoDescField->names = [
            'eng-GB' => 'SEO Meta Description',
        ];
        $seoDescField->fieldGroup = 'seo';
        $seoDescField->position = 40;
        $seoDescField->isTranslatable = true;
        $seoDescField->isRequired = false;
        $contentTypeCreateStruct->addFieldDefinition($seoDescField);

        $contentTypeDraft = $this->contentTypeService->createContentType($contentTypeCreateStruct, [$contentTypeGroup]);
        $this->contentTypeService->publishContentTypeDraft($contentTypeDraft);

        $io->success('Landing Page content type created');
    }
}
