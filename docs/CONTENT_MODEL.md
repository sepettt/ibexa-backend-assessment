# Content Model Documentation

## Overview

This document describes the content types, page structures, and content organization for the Ibexa Experience project. This section will be fully implemented in **Section 2** of the assessment.

## Content Type Groups

Content types are organized into logical groups:

### 1. Editorial
- News (Article)
- Insights (Article)
- Media content

### 2. Page Structure
- Landing pages
- Content pages with Page Builder

### 3. System
- Redirect entries
- Configuration content

## Planned Content Types

### News Article
**Purpose**: News and media articles with rich content

**Fields** (to be implemented):
- Title (Text Line) - required
- Summary (Text Block)
- Body (Rich Text) - required
- Featured Image (Image)
- Publication Date (Date & Time)
- Author (Content Relation - User)
- Categories (Content Relations - Taxonomy)
- SEO Meta Title (Text Line)
- SEO Meta Description (Text Block)

**Field Groups**:
- Content
- Media
- Metadata
- SEO

### Insights Article
**Purpose**: Thought leadership and analysis content

**Fields** (to be implemented):
- Title (Text Line) - required
- Subtitle (Text Line)
- Introduction (Rich Text)
- Body (Rich Text) - required
- Featured Image (Image)
- Gallery (Image Asset)
- Publication Date (Date & Time)
- Authors (Content Relations - User)
- Topics (Content Relations - Taxonomy)
- Related Insights (Content Relations)
- SEO Meta Title (Text Line)
- SEO Meta Description (Text Block)

**Field Groups**:
- Content
- Media
- Relations
- SEO

### Redirect
**Purpose**: Manage URL redirects for pre-launch and URL changes

**Fields** (to be implemented):
- Source URL (Text Line) - required
- Target URL (URL) - required
- Redirect Type (Selection: 301/302) - required, default 301
- Active (Checkbox) - required, default true
- Notes (Text Block)

**Admin UI**:
- Simple create/edit interface
- List view with source/target URLs
- Filter by redirect type and status
- Bulk operations support

### Landing Page (Page Builder)
**Purpose**: Flexible page layouts with drag-and-drop blocks

**Features**:
- Page Builder integration
- Multiple layout options
- Locale-specific content variations
- SEO fields

**Default Layouts** (to be implemented):
- Single column
- Two column (sidebar)
- Three column
- Full width

### Content Page
**Purpose**: Generic content pages with structured sections

**Fields** (to be implemented):
- Title (Text Line) - required
- Navigation Title (Text Line)
- Hero Section (Page Builder Block)
- Content Sections (Page Builder Field)
- Sidebar Content (Rich Text)
- Call to Action (Page Builder Block)
- SEO Meta Title (Text Line)
- SEO Meta Description (Text Block)

## Page Builder Blocks

### Planned Reusable Blocks

#### 1. Hero Block
**Purpose**: Page header with image, title, and CTA

**Attributes**:
- Background Image (Image)
- Headline (Text)
- Subheadline (Text)
- CTA Text (Text)
- CTA Link (URL)
- Overlay Opacity (Range)
- Text Alignment (Selection)

**Localization**: All text fields localizable

#### 2. Rich Text Block
**Purpose**: Formatted text content with WYSIWYG editing

**Attributes**:
- Content (Rich Text)
- Container Width (Selection: full/contained)
- Background Color (Color Picker)

**Localization**: Content localizable

#### 3. Content Listing Block
**Purpose**: Dynamic list of content items

**Attributes**:
- Content Type Filter (Selection)
- Section Filter (Selection)
- Limit (Integer)
- Sort By (Selection)
- Layout (Selection: grid/list/carousel)
- Show Filters (Checkbox)

**Localization**: 
- Filter labels localizable
- Content items respect language fallback

#### 4. Image Gallery Block
**Purpose**: Responsive image gallery

**Attributes**:
- Images (Image Asset - multiple)
- Layout (Selection: grid/masonry/slider)
- Columns (Integer)
- Lightbox Enabled (Checkbox)
- Caption Display (Checkbox)

#### 5. CTA Block
**Purpose**: Call-to-action button or banner

**Attributes**:
- Title (Text)
- Description (Rich Text)
- Button Text (Text)
- Button Link (URL)
- Style (Selection: primary/secondary/outline)
- Alignment (Selection)

**Localization**: All text fields localizable

#### 6. Video Embed Block
**Purpose**: Embedded video content

**Attributes**:
- Video URL (URL)
- Poster Image (Image)
- Autoplay (Checkbox)
- Loop (Checkbox)
- Controls (Checkbox)

## Sections

Logical content organization:

### Editorial Section
- News articles
- Insights articles
- Blog posts

### Media Section
- Images
- Videos
- Documents

### System Section
- Redirects
- Configuration content
- Taxonomy

### Page Structure Section
- Landing pages
- Content pages
- Campaign pages

## Taxonomy Structure

### Categories
For categorizing news and insights:
- Business Units
- Industries
- Topics
- Regions

### Tags
Free-form tagging:
- Technology keywords
- Product names
- Event names

## Content Relations

### Relation Types

**1. Author Relations**
- Content → User
- Used in: News, Insights
- Cardinality: Multiple

**2. Category Relations**
- Content → Taxonomy
- Used in: News, Insights, Pages
- Cardinality: Multiple

**3. Related Content**
- Content → Content (same type)
- Used in: Insights, News
- Cardinality: Multiple (max 5)

**4. Media Relations**
- Content → Image/Video
- Used in: All content types
- Cardinality: Single or Multiple

## Editorial Workflow

### Content Creation Flow

1. **Draft Creation**
   - Editor creates content in admin UI
   - All required fields must be filled
   - Draft automatically saved

2. **Preview**
   - Preview in context of site
   - Check responsive layouts
   - Review SEO metadata

3. **Review (Optional)**
   - Submit for review
   - Reviewer comments
   - Request changes or approve

4. **Publication**
   - Publish immediately or schedule
   - Cache invalidation automatic
   - Search index updated

5. **Updates**
   - Create new version
   - Compare with published version
   - Publish or discard

### Content States
- **Draft**: Work in progress
- **Pending Review**: Awaiting approval
- **Published**: Live on site
- **Archived**: No longer active

## Validation Rules

### Title Fields
- Minimum length: 3 characters
- Maximum length: 150 characters
- No HTML tags allowed

### Rich Text Fields
- Allowed tags: p, h2-h6, ul, ol, li, a, strong, em, img
- Image upload restrictions: 5MB max
- Link validation enabled

### URL Fields
- Valid URL format required
- Protocol required (http/https)

### Image Fields
- Formats: JPG, PNG, WebP
- Maximum size: 10MB
- Recommended dimensions documented per field

### Date Fields
- Cannot be in the past (publication dates)
- Timezone: UTC stored, displayed in user timezone

## Content Type Inheritance

### Base Content Type
All content types inherit from a base with:
- Internal Name (auto-generated slug)
- Created Date (auto)
- Modified Date (auto)
- Owner (auto)
- Section (required)

### Page Type Base
Page types additionally inherit:
- URL Alias (auto-generated)
- Navigation Visible (checkbox)
- Search Indexable (checkbox)
- Sitemap Include (checkbox)

## Admin UI Enhancements

### Planned Improvements

1. **Content Type Icons**
   - Visual content type identification
   - Custom icons per type

2. **Field Help Text**
   - Inline guidance for editors
   - Character counters
   - Format examples

3. **Batch Operations**
   - Bulk publish/unpublish
   - Bulk move/copy
   - Bulk metadata updates

4. **Custom Dashboards**
   - Recent content widget
   - Pending reviews widget
   - Broken links report

## Performance Considerations

### Indexing Strategy
- Full-text search on title, summary, body
- Faceted search on categories, dates
- Boost recent content in rankings

### Caching Strategy
- View cache: 1 hour (public pages)
- ESI for personalized blocks
- Cache invalidation on publish

### Image Optimization
- Automatic responsive variants
- WebP format generation
- Lazy loading enabled
- CDN integration ready

## Migration Strategy

Content types will be defined via:

1. **YAML Definitions**
   - Version controlled
   - Applied via migrations
   - Environment-agnostic

2. **Migration Classes**
   - Symfony migrations
   - Up/down support
   - Data migrations included

## Implementation Checklist (Section 2)

- [ ] Define all content types in YAML
- [ ] Create migration for content types
- [ ] Create sections and content type groups
- [ ] Implement Page Builder layouts
- [ ] Create reusable blocks (Hero, RichText, Listing)
- [ ] Implement redirect content type
- [ ] Add redirect admin UI customization
- [ ] Create sample content via fixtures
- [ ] Add field validation rules
- [ ] Write admin user documentation
- [ ] Add screenshots/screencaps
- [ ] Test content creation workflows

## References

- Ibexa Content Types: https://doc.ibexa.co/en/latest/content_management/content_types/
- Page Builder: https://doc.ibexa.co/en/latest/content_management/page/
- Field Types: https://doc.ibexa.co/en/latest/api/field_types_reference/

---

**Note**: This document outlines the planned content model. Full implementation will occur in Section 2 of the assessment.
