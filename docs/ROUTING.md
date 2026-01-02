# URL Routing and Alias Documentation

## Overview

This document describes the URL routing strategy, alias generation, and path structure for the multi-market Ibexa Experience project. Full implementation will occur in **Section 3** of the assessment.

## Routing Strategy

### Content-Based Routing
- URLs generated from content location tree
- Deterministic paths based on content hierarchy
- Virtual segments supported for business naming
- Automated alias creation and maintenance

### Route Patterns

```
/{siteaccess}/{path-to-content}

Examples:
/global-en/home
/my-en/home/business-units/performance-materials
/th-th/home/media
```

## Target URL Structure (Section 3)

### Content Tree Example

```
Home (/)
├── Business Units (/business-units) [virtual segment]
│   ├── Performance Materials (/performance-materials)
│   ├── Healthcare (/healthcare)
│   └── ...
├── Performance Materials (/performance-materials)
│   └── Industries (/industries) [virtual segment]
│       └── Specialty Chemicals (/specialty-chemicals)
├── Media (/media)
├── Insights (/insights)
└── About Us (/about-us)
    └── Who We Are (/who-we-are)
```

### Resulting URL Paths

Based on the assessment requirements:

```
/home
/home/business-units                                    [virtual: /business-units]
/home/business-units/performance-materials
/home/performance-materials/industries/specialty-chemicals [virtual: /industries]
/home/media
/home/insights
/home/about-us
/home/about-us/who-we-are
/home/business-units/healthcare
```

### Virtual Segments

**Purpose**: Insert business-friendly path segments that don't correspond to actual content locations.

**Examples**:
- `/business-units` - Groups related business unit pages
- `/industries` - Groups industry-specific content

**Implementation Approach**:
- Configure virtual segment mappings per content type or location
- Event subscriber intercepts URL generation
- Custom URL alias creation with virtual segments injected
- Maintain mapping configuration in YAML

## URL Alias Generation

### Automatic Generation

**On Content Publish**:
1. Event subscriber listens to `PublishVersionEvent`
2. Extract content location and parent locations
3. Build path from root to current location
4. Apply virtual segment rules
5. Generate slug for each segment
6. Create or update URL alias

**On Content Move**:
1. Event subscriber listens to `MoveSubtreeEvent`
2. Recalculate paths for moved content
3. Update URL aliases for entire subtree
4. Create redirects from old URLs (301)

### Slug Generation Rules

**Character Transformation**:
- Convert to lowercase
- Replace spaces with hyphens
- Remove or convert diacritics (é → e, ñ → n)
- Remove special characters (except hyphens)
- Remove stopwords (configurable)

**Examples**:
```
"Performance Materials"     → "performance-materials"
"Who We Are"               → "who-we-are"
"Specialty Chemicals & Co" → "specialty-chemicals-co"
"The Future of Technology" → "future-technology"  [stopword removed]
```

**Configuration**:
```yaml
# config/packages/app_routing.yaml
app_routing:
    slug:
        separator: '-'
        lowercase: true
        remove_diacritics: true
        stopwords: ['the', 'a', 'an', 'and', 'or', 'but']
        max_length: 100
```

## URL Alias Event Subscribers

### Planned Subscribers

#### 1. UrlAliasGenerator
**Purpose**: Generate URL aliases on content publish

```php
class UrlAliasGeneratorSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PublishVersionEvent::class => 'onPublishVersion',
        ];
    }
    
    public function onPublishVersion(PublishVersionEvent $event): void
    {
        // 1. Get content and location
        // 2. Build path from root
        // 3. Apply virtual segment rules
        // 4. Generate slugs
        // 5. Create URL alias
    }
}
```

#### 2. UrlAliasUpdater
**Purpose**: Update aliases when content is moved

```php
class UrlAliasUpdaterSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MoveSubtreeEvent::class => 'onMoveSubtree',
        ];
    }
    
    public function onMoveSubtree(MoveSubtreeEvent $event): void
    {
        // 1. Get affected locations
        // 2. Recalculate all paths
        // 3. Update aliases
        // 4. Create 301 redirects
    }
}
```

#### 3. RedirectCreator
**Purpose**: Create redirects for changed URLs

```php
class RedirectCreatorSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            URLAliasChangedEvent::class => 'onUrlAliasChanged',
        ];
    }
    
    public function onUrlAliasChanged(URLAliasChangedEvent $event): void
    {
        // 1. Get old URL
        // 2. Get new URL
        // 3. Create redirect content (301)
        // 4. Update redirect registry
    }
}
```

## Virtual Segment Configuration

### Configuration Format

```yaml
# config/packages/app_routing.yaml
app_routing:
    virtual_segments:
        # Content Type based
        content_types:
            business_unit:
                parent_segment: 'business-units'
            industry_page:
                parent_segment: 'industries'
        
        # Location based (specific content)
        locations:
            # Location ID: virtual segment
            123: 'business-units'
            456: 'industries'
        
        # Pattern based
        patterns:
            - match: '^/performance-materials/'
              inject: 'industries'
              position: 'after'  # after matched path
```

### Virtual Segment Service

```php
interface VirtualSegmentResolverInterface
{
    /**
     * Get virtual segments for a location
     */
    public function resolveSegments(Location $location): array;
    
    /**
     * Build full path with virtual segments
     */
    public function buildPath(Location $location): string;
}
```

## URL Structure Per Siteaccess

With locale prefixes (Section 4):

```
# Global English
/global-en/home
/global-en/home/business-units/performance-materials

# Malaysia English
/my-en/home
/my-en/home/business-units/performance-materials

# Thailand English
/th-en/home
/th-en/home/media

# Thailand Thai
/th-th/home
/th-th/home/media
```

## Path Stability

### Ensuring URL Consistency

**Automated Validation**:
- Unit tests for slug generation
- Integration tests for full path generation
- Functional tests for URL resolution

**Change Detection**:
- Log URL changes
- Alert on unexpected changes
- Automated redirect creation

**Manual Overrides**:
- Editors can override generated URLs
- Custom URL alias field in content edit
- Validation prevents duplicates

## Redirect Management

### Redirect Types

**301 Permanent Redirect**:
- Default for content moves
- For retired content
- For URL structure changes

**302 Temporary Redirect**:
- For A/B testing
- For temporary campaigns
- For maintenance pages

### Redirect Resolution

```php
interface RedirectResolverInterface
{
    /**
     * Find redirect for given URL
     */
    public function findRedirect(string $url): ?Redirect;
    
    /**
     * Apply redirect (301/302)
     */
    public function applyRedirect(Redirect $redirect): Response;
}
```

### Redirect Chain Prevention

- Detect redirect chains (A→B→C)
- Collapse to direct redirect (A→C)
- Alert on circular redirects

## URL Generation in Templates

### Twig Functions

```twig
{# Generate URL for content #}
{{ ibexa_path(content) }}

{# Generate URL for location #}
{{ ibexa_path(location) }}

{# With siteaccess override #}
{{ ibexa_path(content, {}, 'my-en') }}

{# Absolute URL #}
{{ ibexa_url(content) }}
```

### Custom Helpers

```twig
{# Generate localized URL #}
{{ app_localized_path(content, 'th-th') }}

{# Get virtual segment for location #}
{{ app_virtual_segment(location) }}

{# Get all available language URLs #}
{% for locale, url in app_language_urls(content) %}
    <link rel="alternate" hreflang="{{ locale }}" href="{{ url }}" />
{% endfor %}
```

## SEO Considerations

### URL Best Practices
- Keep URLs concise (< 100 characters)
- Use hyphens, not underscores
- Avoid unnecessary words
- Match content hierarchy
- Include keywords naturally

### Canonical URLs
```html
<link rel="canonical" href="{{ ibexa_url(content) }}" />
```

### Hreflang Tags
```html
<link rel="alternate" hreflang="en-GB" href="/global-en/home" />
<link rel="alternate" hreflang="en-MY" href="/my-en/home" />
<link rel="alternate" hreflang="en-TH" href="/th-en/home" />
<link rel="alternate" hreflang="th-TH" href="/th-th/home" />
```

### URL Parameters
- Avoid session IDs in URLs
- Use clean URLs for filtering
- Implement pagination properly

## Testing Strategy

### Unit Tests

```php
class SlugGeneratorTest extends TestCase
{
    public function testGeneratesLowercaseSlug(): void
    {
        $slug = $this->generator->generate('Performance Materials');
        $this->assertEquals('performance-materials', $slug);
    }
    
    public function testRemovesStopwords(): void
    {
        $slug = $this->generator->generate('The Future of Technology');
        $this->assertEquals('future-technology', $slug);
    }
}
```

### Integration Tests

```php
class UrlAliasGenerationTest extends TestCase
{
    public function testGeneratesCorrectPathWithVirtualSegments(): void
    {
        $location = $this->createLocation('Performance Materials');
        $path = $this->urlBuilder->buildPath($location);
        
        $this->assertEquals(
            '/home/business-units/performance-materials',
            $path
        );
    }
}
```

### Functional Tests

```php
class RoutingTest extends WebTestCase
{
    public function testContentAccessibleAtExpectedUrl(): void
    {
        $client = static::createClient();
        $client->request('GET', '/home/business-units/performance-materials');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Performance Materials');
    }
}
```

## Implementation Checklist (Section 3)

- [ ] Create `UrlAliasGeneratorSubscriber`
- [ ] Create `UrlAliasUpdaterSubscriber`
- [ ] Create `RedirectCreatorSubscriber`
- [ ] Implement `SlugGenerator` service
- [ ] Implement `VirtualSegmentResolver` service
- [ ] Configure virtual segments in YAML
- [ ] Create URL generation event listeners
- [ ] Implement redirect resolution
- [ ] Add Twig extensions for URL helpers
- [ ] Write unit tests for slug generation
- [ ] Write integration tests for path building
- [ ] Write functional tests for URL access
- [ ] Test content move scenarios
- [ ] Test redirect chain handling
- [ ] Document editor workflow for URL overrides
- [ ] Verify all example paths work correctly

## Monitoring

### Metrics to Track
- URL generation time
- Redirect hit rate
- 404 error rate
- URL length distribution
- Virtual segment usage

### Logging
```php
$this->logger->info('URL alias generated', [
    'content_id' => $content->id,
    'location_id' => $location->id,
    'old_url' => $oldAlias,
    'new_url' => $newAlias,
    'virtual_segments' => $segments,
]);
```

## References

- Ibexa URL Aliases: https://doc.ibexa.co/en/latest/guide/url_management/
- Symfony Routing: https://symfony.com/doc/current/routing.html
- SEO Best Practices: https://moz.com/learn/seo/url

---

**Note**: This document outlines the planned routing implementation. Full implementation will occur in Section 3 of the assessment.
