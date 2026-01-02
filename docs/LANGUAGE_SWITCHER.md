# Language Switcher Implementation

## Overview

The language switcher enables users to navigate between different language versions of content across multiple markets (Global, Singapore, Malaysia).

## Components

### 1. LanguageSwitcherService (`src/Service/LanguageSwitcherService.php`)

Core service providing language switching logic:

**Methods:**
- `getAvailableLanguages()` - Returns all available languages for content with metadata
- `getLanguagesByMarket()` - Groups languages by market (Global, Singapore, Malaysia)
- `hasTranslation()` - Checks if content has a specific translation
- `getTranslation()` - Gets content in specific language (with fallback)
- `getAvailableMarkets()` - Returns markets where content is available
- `isFallbackLanguage()` - Checks if displaying fallback language
- `getDisplayedLanguage()` - Returns actual language code being shown

**Return Structure:**
```php
[
    'eng-GB' => [
        'siteaccess' => 'global_eng',
        'locale' => 'eng-GB',
        'market' => 'global',
        'displayName' => 'English (Global)',
        'url' => '/news/article-title',
        'available' => true,
        'current' => true,
    ],
    // ... other languages
]
```

### 2. LanguageSwitcherExtension (`src/Twig/LanguageSwitcherExtension.php`)

Twig extension exposing language switcher functions to templates:

**Functions:**
- `get_available_languages(content, location)` - All available languages
- `get_languages_by_market(content, location)` - Languages grouped by market
- `has_translation(content, languageCode)` - Check translation exists
- `is_fallback_language(content, currentLocale)` - Check if using fallback
- `get_displayed_language(content, requestedLocale)` - Get actual language shown

### 3. Template Component (`templates/themes/standard/components/language_switcher.html.twig`)

Dropdown language switcher UI component:

**Features:**
- Organized by market (Global, Singapore, Malaysia)
- Shows current language
- Indicates unavailable translations
- Shows fallback indicator when applicable
- Accessible keyboard navigation
- Proper hreflang attributes for SEO

**Usage:**
```twig
{% include '@ibexadesign/components/language_switcher.html.twig' with {
    content: content,
    location: location
} %}
```

### 4. Styles (`assets/css/components/language-switcher.css`)

Complete CSS styling:
- Dropdown positioning and animation
- Market grouping visual hierarchy
- Current language highlighting
- Unavailable language styling
- Fallback badges
- Responsive design
- Accessibility features (reduced motion support)

### 5. JavaScript (`assets/js/components/language-switcher.js`)

Interactive behavior:

**Features:**
- Dropdown toggle on click
- Close on outside click
- Keyboard navigation (Arrow keys, Home, End, Tab, Escape)
- Focus management
- ARIA state management

## Language Configuration

### Supported Locales

| Locale | Siteaccess | Market | Display Name |
|--------|-----------|--------|--------------|
| eng-GB | global_eng | global | English (Global) |
| eng-SG | sg_eng | sg | English (Singapore) |
| eng-MY | my_eng | my | English (Malaysia) |
| may-MY | my_bm | my | Bahasa Melayu |

### URL Structure

- Global: `/news/article-title`
- Singapore: `/sg/news/article-title`
- Malaysia English: `/my/eng/news/article-title`
- Malaysia Bahasa: `/my/bm/news/article-title`

## Fallback Behavior

Language fallback chains by market:

1. **Global**: eng-GB only
2. **Singapore**: eng-SG → eng-GB
3. **Malaysia**: 
   - English: eng-MY → eng-GB
   - Bahasa: may-MY → eng-MY → eng-GB

When translation unavailable:
- Link shows "Not available" badge
- Can be hidden via configuration
- SEO: hreflang points to available version

## Integration Examples

### In Page Layout

```twig
{# templates/themes/standard/pagelayout.html.twig #}
<header>
    <nav class="main-nav">
        {# ... other navigation ... #}
        
        {% include '@ibexadesign/components/language_switcher.html.twig' with {
            content: content,
            location: location
        } %}
    </nav>
</header>
```

### Custom Implementation

```twig
{% set languages = get_available_languages(content, location) %}

<ul class="custom-lang-nav">
    {% for locale, lang in languages %}
        <li class="{{ lang.current ? 'active' : '' }}">
            {% if lang.available and not lang.current %}
                <a href="{{ lang.url }}" hreflang="{{ locale }}">
                    {{ lang.displayName }}
                </a>
            {% else %}
                <span>{{ lang.displayName }}</span>
            {% endif %}
        </li>
    {% endfor %}
</ul>
```

### Check Translation Status

```twig
{% if has_translation(content, 'may-MY') %}
    <a href="{{ path_to_malay_version }}">Bahasa Melayu</a>
{% else %}
    <span class="unavailable">Bahasa Melayu (Coming Soon)</span>
{% endif %}
```

### Show Fallback Indicator

```twig
{% if is_fallback_language(content, app.request.locale) %}
    <div class="fallback-notice">
        This content is not available in your language. 
        Showing {{ get_displayed_language(content, app.request.locale) }}.
    </div>
{% endif %}
```

## SEO Implementation

### hreflang Tags

Add to page `<head>`:

```twig
{% set languages = get_available_languages(content, location) %}
{% for locale, lang in languages %}
    {% if lang.available %}
        <link rel="alternate" hreflang="{{ locale|lower|replace({'_': '-'}) }}" href="{{ lang.url }}">
    {% endif %}
{% endfor %}

{# Default language #}
<link rel="alternate" hreflang="x-default" href="{{ languages['eng-GB'].url }}">
```

### Language Meta Tags

```twig
<meta property="og:locale" content="{{ app.request.locale }}">
{% set languages = get_available_languages(content, location) %}
{% for locale, lang in languages %}
    {% if lang.available and not lang.current %}
        <meta property="og:locale:alternate" content="{{ locale }}">
    {% endif %}
{% endfor %}
```

## Accessibility

The language switcher follows WCAG 2.1 AA standards:

- **Keyboard Navigation**: Full keyboard support
- **ARIA Attributes**: Proper role, aria-expanded, aria-current
- **Focus Management**: Visible focus indicators
- **Screen Readers**: Descriptive labels and announcements
- **Reduced Motion**: Respects prefers-reduced-motion

## Testing

### Manual Testing

1. Navigate to content page
2. Click language switcher
3. Verify all languages listed
4. Verify current language highlighted
5. Click different language
6. Verify URL changes correctly
7. Verify content translates (if available)
8. Test keyboard navigation (Tab, Arrow keys, Escape)

### Edge Cases

- Content with only one language
- Content without translation in requested language
- Content using fallback language
- Admin siteaccess (should not show)
- Different content types

## Performance

- Languages loaded once per request
- Cached with content
- Minimal DOM manipulation
- CSS transitions for smooth UX
- Lazy initialization of JavaScript

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with polyfills)
- Mobile browsers
- Progressive enhancement approach

## Future Enhancements

- [ ] Remember user language preference (cookie/session)
- [ ] Auto-detect browser language on first visit
- [ ] Language switcher in mobile menu
- [ ] Analytics tracking for language switches
- [ ] A/B testing different switcher placements
- [ ] Translation progress indicators
