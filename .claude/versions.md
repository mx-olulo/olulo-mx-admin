# Project Tech Stack Versions

## ⚠️ CRITICAL: Use Latest Versions Only

- **Filament**: 4.x (NOT 3.x)
- **Tailwind CSS**: 4.x (NOT 3.x)

## 🚨 Common AI Mistakes to Avoid

When suggesting code, AI assistants often default to v3.x syntax. This document helps prevent that.

---

## Filament 3.x → 4.x Key Differences

### 1. **Schema Structure Changes**
- ❌ **v3**: Forms/Infolists directly in resource
- ✅ **v4**: Separated into Schema classes (but can be embedded with config)

### 2. **Static Methods → Constructor**
- ❌ **v3**: `Form::make()`, `Table::make()`
- ✅ **v4**: Still uses `::make()` but with new signatures

### 3. **Namespace Changes**
- ❌ **v3**: `Filament\Forms\Components\Section`
- ✅ **v4**: `Filament\Schemas\Components\Section`

### 4. **Layout Component Behavior**
- ❌ **v3**: `Grid`, `Section`, `Fieldset` auto span full width
- ✅ **v4**: Only span 1 column by default, use `columnSpanFull()` if needed

### 5. **Table Filters**
- ❌ **v3**: Filters apply immediately
- ✅ **v4**: Filters are deferred by default (must click button)
    - Disable with: `$table->deferFilters(false)`

### 6. **ColumnSpan Behavior**
- ❌ **v3**: `columnSpan(2)` affects all devices
- ✅ **v4**: `columnSpan(2)` affects `>= lg` devices by default

### 7. **Unique Validation**
- ❌ **v3**: `unique()` doesn't ignore current record by default
- ✅ **v4**: `unique()` ignores current record by default
    - Use `ignoreRecord: false` to restore v3 behavior

### 8. **Enum Field State**
- ❌ **v3**: Returns enum value OR instance (inconsistent)
- ✅ **v4**: Always returns enum instance

### 9. **Radio Component**
- ❌ **v3**: `inline()` makes buttons inline AND with label
- ✅ **v4**: `inline()` only makes buttons inline
    - Use `inline()->inlineLabel()` for v3 behavior

### 10. **URL Parameters (Resource Pages)**
- ❌ **v3**: `activeRelationManager`, `activeTab`, `tableFilters`, `tableSearch`
- ✅ **v4**: `relation`, `tab`, `filters`, `search` (cleaner names)

---

## Tailwind CSS 3.x → 4.x Key Differences

### 1. **Import Syntax**
```css
/* ❌ v3 */
@tailwind base;
@tailwind components;
@tailwind utilities;

/* ✅ v4 */
@import "tailwindcss";
```

### 2. **Configuration Location**
- ❌ **v3**: JavaScript config (`tailwind.config.js`)
- ✅ **v4**: CSS-first config using `@theme`

```css
/* v4 */
@import "tailwindcss";

@theme {
  --font-display: "Satoshi", "sans-serif";
  --breakpoint-3xl: 120rem;
  --color-brand-500: oklch(0.5 0.2 250);
}
```

### 3. **Custom Theme in Filament**
```css
/* ❌ v3 */
@import '../../../../vendor/filament/filament/resources/css/theme.css';
@config 'tailwind.config.js';

/* ✅ v4 */
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament';
@source '../../../../resources/views/filament';
```

### 4. **Renamed Utilities**
| v3 | v4 |
|---|---|
| `shadow-sm` | `shadow-xs` |
| `shadow` | `shadow-sm` |
| `rounded-sm` | `rounded-xs` |
| `rounded` | `rounded-sm` |
| `outline-none` | `outline-hidden` |
| `ring` | `ring-3` |
| `blur-sm` | `blur-xs` |
| `blur` | `blur-sm` |

### 5. **Removed Utilities (Use Opacity Modifiers)**
- ❌ **v3**: `bg-opacity-50`, `text-opacity-75`
- ✅ **v4**: `bg-black/50`, `text-black/75`

### 6. **Border & Ring Defaults**
- ❌ **v3**: `border` uses `gray-200`, `ring` uses 3px blue
- ✅ **v4**: `border` uses `currentColor`, `ring` uses 1px currentColor
    - Use explicit colors: `border-gray-200`, `ring-3 ring-blue-500`

### 7. **Custom Utilities**
```css
/* ❌ v3 */
@layer utilities {
  .tab-4 {
    tab-size: 4;
  }
}

/* ✅ v4 */
@utility tab-4 {
  tab-size: 4;
}
```

### 8. **@apply Deprecation**
- ❌ **v3**: `@apply` commonly used
- ✅ **v4**: Discouraged, use utility classes directly in HTML
    - Or use CSS variables: `color: var(--color-red-500);`

### 9. **Variant Order**
- ❌ **v3**: Right-to-left (`hover:focus:underline`)
- ✅ **v4**: Left-to-right (more intuitive)

### 10. **CSS Variables as Values**
```html
<!-- ❌ v3 -->
<div class="bg-[--brand-color]"></div>

<!-- ✅ v4 -->
<div class="bg-(--brand-color)"></div>
```

### 11. **No Sass/Less Support**
- ❌ **v3**: Works with Sass, Less, Stylus
- ✅ **v4**: NOT compatible with CSS preprocessors
    - Tailwind v4 IS the preprocessor

---

## Migration Commands

### Filament
```bash
# Automated upgrade script
composer require filament/upgrade:"^4.0" -W --dev
vendor/bin/filament-v4
composer require filament/filament:"^4.0" -W --no-update
composer update

# Directory structure migration (optional)
php artisan filament:upgrade-directory-structure-to-v4 --dry-run
php artisan filament:upgrade-directory-structure-to-v4

# Cleanup
composer remove filament/upgrade --dev
```

### Tailwind CSS
```bash
# Automated upgrade tool
npx @tailwindcss/upgrade

# Manual package updates
npm install -D @tailwindcss/postcss @tailwindcss/cli
# or for Vite
npm install -D @tailwindcss/vite
```

---

## Important References

- [Filament 4.x Upgrade Guide](https://filamentphp.com/docs/4.x/upgrade-guide)
- [Tailwind CSS 4.x Upgrade Guide](https://tailwindcss.com/docs/upgrade-guide)

---

## 🎯 Quick Reference for AI

When generating code:
1. **Never** use Filament 3.x syntax
2. **Never** use `@tailwind` directives (use `@import "tailwindcss"`)
3. **Never** suggest `shadow`, `rounded`, `ring` without size modifiers
4. **Always** use `columnSpanFull()` explicitly for full-width layouts
5. **Always** specify border/ring colors explicitly
6. **Always** use opacity modifiers (`/50`) instead of opacity utilities

