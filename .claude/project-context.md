# Project Context & AI Guidelines

## 🎯 Purpose
This file helps AI assistants understand the project's tech stack and avoid suggesting outdated code patterns.

---

## Framework Versions

### Core Stack
- **Filament**: 4.x
- **Tailwind CSS**: 4.x  

### Critical Note
⚠️ **AI assistants frequently suggest v3.x code because it's more common in training data. Always use v4.x syntax.**

---

## Filament 4.x Patterns

### Resource Structure
```php
<?php

namespace App\Filament\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;

class UserResource extends Resource
{
    // ✅ v4: Form schema
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columnSpanFull() // Explicitly span full width
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->columnSpan(2), // Targets >= lg by default
                        
                        TextInput::make('email')
                            ->email()
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }
    
    // ✅ v4: Table configuration
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                // Filters are deferred by default in v4
            ])
            ->deferFilters(false); // Optional: restore v3 behavior
    }
}
```

### Form Patterns

#### Layout Components
```php
use Filament\Schemas\Components\{Section, Grid, Fieldset};

// ✅ v4: Must explicitly span full width
Section::make('User Details')
    ->columnSpanFull() // Required for full width
    ->schema([...])
    ->columns(2);

Grid::make(2)
    ->columnSpanFull()
    ->schema([...]);

Fieldset::make('Address')
    ->columnSpanFull()
    ->schema([...]);
```

#### Column Spanning
```php
// ✅ v4: Targets >= lg devices by default
TextInput::make('title')
    ->columnSpan(2); // Applies to lg, xl, 2xl screens

// Still works: explicit breakpoints
TextInput::make('title')
    ->columnSpan([
        'lg' => 3,
        'xl' => 2,
        '2xl' => 1,
    ]);
```

#### Enum Fields
```php
use App\Enums\Status;
use Filament\Forms\Components\Select;

// ✅ v4: Always returns enum instance
Select::make('status')
    ->options(Status::class)
    ->afterStateUpdated(function (?Status $state) {
        // $state is ALWAYS an instance or null
        if ($state === Status::Active) {
            // ...
        }
    });
```

#### Unique Validation
```php
// ✅ v4: Ignores current record by default
TextInput::make('email')
    ->unique(); // Automatically ignores current record

// Restore v3 behavior if needed
TextInput::make('email')
    ->unique(ignoreRecord: false);
```

### Table Patterns

#### Filters
```php
public function table(Table $table): Table
{
    return $table
        ->filters([
            Filter::make('verified')
                ->query(fn ($query) => $query->whereNotNull('email_verified_at')),
        ])
        // ✅ v4: Filters deferred by default (user must click button)
        ->deferFilters(false); // Optional: apply immediately like v3
}
```

#### URL Parameters
```php
// ✅ v4: Cleaner parameter names
// relation (was: activeRelationManager)
// tab (was: activeTab)  
// filters (was: tableFilters)
// search (was: tableSearch)
// sort (was: tableSort)

// Example: Generating URLs
return UserResource::getUrl('edit', [
    'record' => $user,
    'relation' => 'posts', // v4 parameter name
]);
```

---

## Tailwind CSS 4.x Patterns

### CSS Entry Point
```css
/* ✅ v4: app.css or theme.css */
@import "tailwindcss";

/* Theme configuration in CSS */
@theme {
  /* Fonts */
  --font-sans: "Inter", "system-ui", "sans-serif";
  --font-display: "Satoshi", "sans-serif";
  
  /* Breakpoints */
  --breakpoint-3xl: 120rem;
  
  /* Colors using OKLCH (recommended) */
  --color-primary-500: oklch(0.5 0.2 250);
  --color-brand-50: oklch(0.98 0.02 250);
  --color-brand-100: oklch(0.95 0.04 250);
  
  /* Or traditional RGB/HSL */
  --color-accent-500: #3b82f6;
  
  /* Spacing (if custom needed) */
  --spacing-18: 4.5rem;
}

/* Source paths for class detection */
@source "../app/**/*.php";
@source "../resources/**/*.blade.php";
```

### Filament Custom Theme
```css
/* resources/css/filament/admin/theme.css */
@import '../../../../vendor/filament/filament/resources/css/theme.css';

/* ✅ v4: Specify source directories */
@source '../../../../app/Filament';
@source '../../../../resources/views/filament';

@theme {
  /* Custom colors */
  --color-primary-500: oklch(0.5 0.2 250);
  
  /* Custom fonts */
  --font-display: "Satoshi", "sans-serif";
}
```

### Common Utility Updates

#### Shadows
```html
<!-- ❌ v3 -->
<div class="shadow-sm">...</div>
<div class="shadow">...</div>

<!-- ✅ v4 -->
<div class="shadow-xs">...</div>
<div class="shadow-sm">...</div>
```

#### Rounded
```html
<!-- ❌ v3 -->
<div class="rounded-sm">...</div>
<div class="rounded">...</div>

<!-- ✅ v4 -->
<div class="rounded-xs">...</div>
<div class="rounded-sm">...</div>
```

#### Outline
```html
<!-- ❌ v3 -->
<button class="outline outline-2 focus:outline-none">

<!-- ✅ v4 -->
<button class="outline-2 focus:outline-hidden">
```

#### Ring
```html
<!-- ❌ v3 -->
<input class="focus:ring focus:ring-blue-500">

<!-- ✅ v4 -->
<input class="focus:ring-3 focus:ring-blue-500">
```

#### Border & Ring with Colors
```html
<!-- ❌ v3: Default colors provided -->
<div class="border">...</div>
<button class="ring">...</button>

<!-- ✅ v4: Must specify colors -->
<div class="border border-gray-200">...</div>
<button class="ring-3 ring-blue-500">...</button>
```

#### Opacity
```html
<!-- ❌ v3 -->
<div class="bg-black bg-opacity-50">...</div>
<div class="text-blue-500 text-opacity-75">...</div>

<!-- ✅ v4 -->
<div class="bg-black/50">...</div>
<div class="text-blue-500/75">...</div>
```

### Custom Utilities
```css
/* ❌ v3 */
@layer utilities {
  .text-balance {
    text-wrap: balance;
  }
}

/* ✅ v4 */
@utility text-balance {
  text-wrap: balance;
}
```

### Using CSS Variables
```css
/* ✅ v4: Preferred over @apply */
.custom-button {
  background-color: var(--color-primary-500);
  padding: var(--spacing-4);
  border-radius: var(--radius-sm);
}

/* ❌ v4: @apply discouraged but still works */
.custom-button {
  @apply bg-primary-500 p-4 rounded-sm;
}
```

---

## Common AI Mistakes

### ❌ Don't Suggest
1. `@tailwind base;` / `@tailwind components;` / `@tailwind utilities;`
2. `shadow`, `rounded`, `ring` without size suffixes
3. `outline-none` (use `outline-hidden`)
4. `bg-opacity-*`, `text-opacity-*` utilities
5. Layout components without explicit `columnSpanFull()`
6. `border` or `ring` without explicit colors
7. Sass/SCSS files for Tailwind (not supported in v4)
8. `tailwind.config.js` for new projects (use CSS `@theme`)

### ✅ Always Suggest
1. `@import "tailwindcss";`
2. `shadow-xs`, `shadow-sm`, `rounded-xs`, `ring-3`
3. `outline-hidden`
4. `bg-black/50`, `text-blue-500/75`
5. `->columnSpanFull()` for full-width layouts
6. `border-gray-200`, `ring-3 ring-blue-500`
7. Pure CSS files
8. `@theme` in CSS for configuration

---

## File Structure

### Typical Filament 4.x Project
```
app/
├── Filament/
│   ├── Resources/
│   │   ├── UserResource/
│   │   │   ├── Pages/
│   │   │   │   ├── CreateUser.php
│   │   │   │   ├── EditUser.php
│   │   │   │   └── ListUsers.php
│   │   │   └── UserResource.php (main resource)
│   │   └── ...
│   └── ...
resources/
├── css/
│   └── filament/
│       └── admin/
│           └── theme.css (Tailwind v4 entry)
└── views/
    └── filament/
        └── ...
```

---

## Testing & Validation

### Before Committing Code
1. ✅ No `@tailwind` directives
2. ✅ All borders have explicit colors
3. ✅ All shadows use new naming (`-xs`, `-sm`)
4. ✅ Layout components use `columnSpanFull()` when needed
5. ✅ No deprecated opacity utilities
6. ✅ No v3 Filament patterns

---

## Resources

### Official Documentation
- [Filament 4.x Docs](https://filamentphp.com/docs/4.x)
- [Tailwind CSS 4.x Docs](https://tailwindcss.com/docs)

### Upgrade Guides
- [Filament 3 → 4 Upgrade Guide](https://filamentphp.com/docs/4.x/upgrade-guide)
- [Tailwind 3 → 4 Upgrade Guide](https://tailwindcss.com/docs/upgrade-guide)

---

## Summary

This project uses **Filament 4.x** and **Tailwind CSS 4.x**. When AI tools suggest code:

1. **Verify the version** - Check that suggested code matches v4.x patterns
2. **Use this file as reference** - Compare suggestions against examples here
3. **Test in browser** - Always verify generated code works as expected
4. **Report issues** - If AI continues suggesting v3 patterns, provide this file as context

**Remember**: Most AI training data contains v3.x code. Always validate suggestions against this guide.

