# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin that exposes Rank Math SEO functionality through the WordPress Abilities API, enabling AI agents (via MCP) to discover, read, and manage SEO settings programmatically.

- **Plugin Slug**: `wp-abilities-seo-extension`
- **Namespace**: `SeoAbilities`
- **PHP Version**: 7.4+
- **WordPress Version**: 6.9+ (requires Abilities API)

## Development Commands

```bash
# Install dependencies (required for autoloading)
composer install

# Regenerate autoloader after adding new classes
composer dump-autoload
```

There is no build step, test suite, or linting configured.

## Architecture

### Provider Pattern

The plugin uses a provider-based architecture to abstract SEO plugin-specific logic:

```
Abilities Layer (seo-abilities/*)
        ↓
Provider Interface (ProviderInterface.php)
        ↓
SEO Plugin Provider (RankMathProvider.php)
```

- **ProviderInterface**: Defines the contract for all SEO operations (get/update meta, scores, social)
- **ProviderFactory**: Uses class detection (`class_exists('\RankMath\Helper')`) to instantiate the correct provider
- **RankMathProvider**: Implements the interface using Rank Math's specific meta keys

This allows adding support for other SEO plugins (Yoast, AIOSEO) by creating new provider classes.

### Core Components

| Component | Purpose |
|-----------|---------|
| `Plugin` | Singleton orchestrator that initializes provider, services, and components |
| `AbilityRegistrar` | Registers 3 categories and 10 abilities with the WordPress Abilities API |
| `Settings` | Admin settings page (Settings → SEO Abilities) for post types and bulk limits |
| `AbstractAbility` | Base class providing validation helpers for all abilities |
| `PostService` / `ImageService` | Shared services for post and image operations |
| `ErrorFactory` / `ErrorCodes` | AI-friendly error messages with HTTP status mapping |

### Abilities Registered

Categories: `seo-meta`, `seo-analysis`, `seo-images`

Abilities use the `seo-abilities/` namespace:
- `get-seo-meta`, `update-seo-meta`, `bulk-update-seo-meta`
- `get-social-meta`, `update-social-meta`
- `get-seo-score`, `find-posts-with-seo-issues`
- `get-post-images`, `update-image-alt-text`, `bulk-update-image-alt-text`

### WordPress Abilities API Integration

- Categories register on `wp_abilities_api_categories_init` hook
- Abilities register on `wp_abilities_api_init` hook
- Each ability class has static `get_input_schema()` and `get_output_schema()` methods returning JSON Schema
- `meta.show_in_rest` and `meta.mcp.public` enable REST API and MCP discovery

REST API endpoint pattern: `/wp-json/wp-abilities/v1/abilities/{ability-name}/run`

### Rank Math Meta Keys

Rank Math stores SEO data with `rank_math_` prefix:
- Basic: `rank_math_title`, `rank_math_description`, `rank_math_focus_keyword`
- Facebook: `rank_math_facebook_title`, `rank_math_facebook_description`, `rank_math_facebook_image_id`
- Twitter: `rank_math_twitter_title`, `rank_math_twitter_use_facebook` (stores "on" for true)
- Score: `rank_math_seo_score` (integer 0-100)

## Key Design Principles

- **AI-first design**: Error messages are verbose and actionable to help AI agents self-correct
- **Atomic operations**: Single-item abilities with bulk variants for efficiency
- **Permission model**: Uses `edit_post` capability checked per-post
- **Graceful degradation**: Shows admin notice when Rank Math is not active
