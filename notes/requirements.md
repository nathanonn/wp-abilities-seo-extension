# SEO Abilities for WordPress — Requirements Document

> **Version:** 1.0  
> **Last Updated:** December 2025  
> **Repository:** [wp-abilities-seo-extension](https://github.com/nathanonn/wp-abilities-seo-extension)

---

## Legal Notice

This project is an independent extension and is not affiliated with, maintained, authorized, or endorsed by any third-party SEO plugin vendors.

Rank Math® is a registered trademark of its respective owner. WordPress® is a registered trademark of the WordPress Foundation.

---

## Table of Contents

1. [Plugin Overview](#1-plugin-overview)
2. [Ability Categories](#2-ability-categories)
3. [Abilities List](#3-abilities-list)
4. [Admin Settings](#4-admin-settings)
5. [User Stories](#5-user-stories)
6. [Acceptance Criteria](#6-acceptance-criteria)
7. [Business Rules](#7-business-rules)
8. [Error Message Specifications](#8-error-message-specifications)
9. [Out of Scope (v2+)](#9-out-of-scope-v2)
10. [Summary of Abilities](#10-summary-of-abilities)

---

## 1. Plugin Overview

### 1.1 Purpose

A WordPress plugin that exposes SEO plugin functionality through the WordPress Abilities API, enabling AI agents (via MCP) to discover, read, and manage SEO settings programmatically.

**Version 1.0** supports Rank Math® SEO (Free) as the backend provider. The architecture is designed to support additional SEO plugins in future versions.

### 1.2 Plugin

| Attribute                     | Value                                      |
| ----------------------------- | ------------------------------------------ |
| **Plugin Name**               | Abilities API Extension for Rank Math SEO  |
| **Plugin Slug**               | `wp-abilities-seo-extension`               |
| **Text Domain**               | `wp-abilities-seo-extension`               |
| **Settings Location**         | Settings → SEO Abilities                   |
| **Primary Consumer**          | AI agents via MCP (Model Context Protocol) |
| **Supported SEO Plugin (v1)** | Rank Math® SEO (Free version)              |
| **License**                   | GPL-2.0-or-later                           |
| **Repository**                | `wp-abilities-seo-extension`               |

### 1.3 Core Principles

-   **AI-first design** with rich, descriptive schemas that help AI agents understand context and make intelligent decisions
-   **Verbose error messages** that help AI agents self-correct and provide actionable guidance
-   **Atomic operations** (single-item abilities) with optional bulk variants for efficiency
-   **WordPress permission model** compliance using `edit_post` capability
-   **Graceful degradation** when the required SEO plugin is unavailable
-   **Provider-agnostic ability naming** (`seo-abilities/*`) to support multiple SEO plugins in future versions

### 1.4 Requirements

-   WordPress 6.9+ (with Abilities API)
-   PHP 7.4+
-   Supported SEO plugin (v1: Rank Math® SEO Free)

### 1.5 Architecture Notes

The plugin uses a provider-based architecture:

-   **Abilities Layer:** Generic SEO abilities with consistent input/output schemas (`seo-abilities/*`)
-   **Provider Layer:** SEO plugin-specific implementations (v1: Rank Math provider)
-   **Settings Layer:** Plugin configuration including enabled post types and bulk limits

This separation allows future versions to add providers for other SEO plugins (Yoast SEO, All in One SEO, etc.) while maintaining backward-compatible ability signatures.

---

## 2. Ability Categories

The plugin registers three ability categories organized by function. Category slugs are intentionally generic to support multiple SEO plugin providers.

| Category Slug  | Label        | Description                                                                                                                                |
| -------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------ |
| `seo-meta`     | SEO Meta     | Abilities for reading and writing SEO meta fields (titles, descriptions, focus keywords) and social media meta (Open Graph, Twitter Cards) |
| `seo-analysis` | SEO Analysis | Abilities for retrieving SEO scores, recommendations, and finding posts with SEO issues                                                    |
| `seo-images`   | SEO Images   | Abilities for managing image alt text for SEO optimization                                                                                 |

---

## 3. Abilities List

All abilities use the `seo-abilities/` namespace prefix. This generic naming convention ensures consistent API signatures regardless of which SEO plugin provider is active.

### 3.1 Category: SEO Meta

#### 3.1.1 `seo-abilities/get-seo-meta`

**Purpose:** Retrieve current SEO meta fields for a post.

| Attribute   | Value                                                                                                                                                                                                                                                                    |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Label       | Get SEO Meta                                                                                                                                                                                                                                                             |
| Description | Retrieves the current SEO meta title, meta description, and focus keyword for a specified post, page, or custom post type. Returns null for fields that haven't been set. Use this ability to understand the current SEO state before making recommendations or changes. |
| Category    | `seo-meta`                                                                                                                                                                                                                                                               |

**Input Schema:**

| Field     | Type    | Required | Description                                                                                    |
| --------- | ------- | -------- | ---------------------------------------------------------------------------------------------- |
| `post_id` | integer | Yes      | The ID of the post to retrieve SEO meta for. Must be a valid post ID for an enabled post type. |

**Output Schema:**

| Field                        | Type           | Description                                           |
| ---------------------------- | -------------- | ----------------------------------------------------- |
| `post_id`                    | integer        | The post ID                                           |
| `post_title`                 | string         | The post's WordPress title (for reference)            |
| `post_type`                  | string         | The post type (post, page, or CPT slug)               |
| `post_url`                   | string         | The permalink of the post                             |
| `seo_title`                  | string \| null | The SEO meta title, or null if not set                |
| `seo_description`            | string \| null | The SEO meta description, or null if not set          |
| `focus_keyword`              | string \| null | The focus keyword, or null if not set                 |
| `is_seo_title_default`       | boolean        | True if using the SEO plugin's default title template |
| `is_seo_description_default` | boolean        | True if using the SEO plugin's default description    |

---

#### 3.1.2 `seo-abilities/update-seo-meta`

**Purpose:** Update SEO meta fields for a post.

| Attribute   | Value                                                                                                                                                                                                 |
| ----------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Label       | Update SEO Meta                                                                                                                                                                                       |
| Description | Updates the SEO meta title, meta description, and/or focus keyword for a specified post. Only provided fields are updated; omitted fields remain unchanged. Provide an empty string to clear a field. |
| Category    | `seo-meta`                                                                                                                                                                                            |

**Input Schema:**

| Field             | Type    | Required | Description                                                                                     |
| ----------------- | ------- | -------- | ----------------------------------------------------------------------------------------------- |
| `post_id`         | integer | Yes      | The ID of the post to update                                                                    |
| `seo_title`       | string  | No       | New SEO meta title. Recommended max 60 characters for optimal display in search results.        |
| `seo_description` | string  | No       | New SEO meta description. Recommended max 160 characters for optimal display in search results. |
| `focus_keyword`   | string  | No       | New focus keyword for SEO analysis                                                              |

**Output Schema:**

| Field                            | Type           | Description                                   |
| -------------------------------- | -------------- | --------------------------------------------- |
| `success`                        | boolean        | Whether the update was successful             |
| `post_id`                        | integer        | The post ID that was updated                  |
| `updated_fields`                 | array          | List of field names that were updated         |
| `current_values`                 | object         | Object containing current values after update |
| `current_values.seo_title`       | string \| null | Current SEO title                             |
| `current_values.seo_description` | string \| null | Current SEO description                       |
| `current_values.focus_keyword`   | string \| null | Current focus keyword                         |

---

#### 3.1.3 `seo-abilities/get-social-meta`

**Purpose:** Retrieve social media meta (Open Graph & Twitter Cards) for a post.

| Attribute   | Value                                                                                                                                                                                                      |
| ----------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Label       | Get Social Meta                                                                                                                                                                                            |
| Description | Retrieves Facebook Open Graph and Twitter Card meta data for a specified post, including titles, descriptions, and images. Use this to understand how content will appear when shared on social platforms. |
| Category    | `seo-meta`                                                                                                                                                                                                 |

**Input Schema:**

| Field     | Type    | Required | Description                                    |
| --------- | ------- | -------- | ---------------------------------------------- |
| `post_id` | integer | Yes      | The ID of the post to retrieve social meta for |

**Output Schema:**

| Field                  | Type            | Description                                    |
| ---------------------- | --------------- | ---------------------------------------------- |
| `post_id`              | integer         | The post ID                                    |
| `facebook`             | object          | Facebook Open Graph data                       |
| `facebook.title`       | string \| null  | OG title                                       |
| `facebook.description` | string \| null  | OG description                                 |
| `facebook.image`       | string \| null  | OG image URL                                   |
| `facebook.image_id`    | integer \| null | OG image attachment ID                         |
| `twitter`              | object          | Twitter Card data                              |
| `twitter.use_facebook` | boolean         | Whether Twitter uses Facebook data as fallback |
| `twitter.title`        | string \| null  | Twitter title (if different from Facebook)     |
| `twitter.description`  | string \| null  | Twitter description                            |
| `twitter.image`        | string \| null  | Twitter image URL                              |
| `twitter.image_id`     | integer \| null | Twitter image attachment ID                    |

---

#### 3.1.4 `seo-abilities/update-social-meta`

**Purpose:** Update social media meta for a post.

| Attribute   | Value                                                                                                                                                                                                                           |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Label       | Update Social Meta                                                                                                                                                                                                              |
| Description | Updates Facebook Open Graph and/or Twitter Card meta data for a specified post. Supports setting titles, descriptions, and images for social sharing. Set twitter_use_facebook to true to have Twitter inherit Facebook values. |
| Category    | `seo-meta`                                                                                                                                                                                                                      |

**Input Schema:**

| Field                  | Type    | Required | Description                                                               |
| ---------------------- | ------- | -------- | ------------------------------------------------------------------------- |
| `post_id`              | integer | Yes      | The ID of the post to update                                              |
| `facebook_title`       | string  | No       | Facebook OG title                                                         |
| `facebook_description` | string  | No       | Facebook OG description                                                   |
| `facebook_image_id`    | integer | No       | Attachment ID for Facebook image (must be a valid image in Media Library) |
| `twitter_use_facebook` | boolean | No       | Set to true to use Facebook data for Twitter                              |
| `twitter_title`        | string  | No       | Twitter-specific title (ignored if twitter_use_facebook is true)          |
| `twitter_description`  | string  | No       | Twitter-specific description (ignored if twitter_use_facebook is true)    |
| `twitter_image_id`     | integer | No       | Attachment ID for Twitter image (must be a valid image in Media Library)  |

**Output Schema:**

| Field            | Type    | Description                             |
| ---------------- | ------- | --------------------------------------- |
| `success`        | boolean | Whether the update was successful       |
| `post_id`        | integer | The post ID that was updated            |
| `updated_fields` | array   | List of field names that were updated   |
| `current_values` | object  | Current social meta values after update |

---

#### 3.1.5 `seo-abilities/bulk-update-seo-meta`

**Purpose:** Update SEO meta fields for multiple posts in a single request.

| Attribute   | Value                                                                                                                                                                                                                         |
| ----------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Label       | Bulk Update SEO Meta                                                                                                                                                                                                          |
| Description | Updates SEO meta fields for multiple posts in a single operation. Each post can have different values. Respects the configured maximum items limit (default: 10). Use this for efficient batch updates across multiple posts. |
| Category    | `seo-meta`                                                                                                                                                                                                                    |

**Input Schema:**

| Field                     | Type    | Required | Description                                                          |
| ------------------------- | ------- | -------- | -------------------------------------------------------------------- |
| `items`                   | array   | Yes      | Array of update objects. Maximum items determined by admin settings. |
| `items[].post_id`         | integer | Yes      | Post ID to update                                                    |
| `items[].seo_title`       | string  | No       | New SEO title                                                        |
| `items[].seo_description` | string  | No       | New SEO description                                                  |
| `items[].focus_keyword`   | string  | No       | New focus keyword                                                    |

**Output Schema:**

| Field                      | Type           | Description                                  |
| -------------------------- | -------------- | -------------------------------------------- |
| `success`                  | boolean        | True only if ALL updates succeeded           |
| `total_requested`          | integer        | Number of items in the request               |
| `total_processed`          | integer        | Number of items actually processed           |
| `results`                  | array          | Array of individual results per post         |
| `results[].post_id`        | integer        | Post ID                                      |
| `results[].success`        | boolean        | Whether this specific post was updated       |
| `results[].updated_fields` | array          | Fields updated for this post (if successful) |
| `results[].error`          | string \| null | Error message if this post failed            |

---

### 3.2 Category: SEO Analysis

#### 3.2.1 `seo-abilities/get-seo-score`

**Purpose:** Retrieve SEO analysis score and recommendations for a post.

| Attribute   | Value                                                                                                                                                                                                                             |
| ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Label       | Get SEO Score                                                                                                                                                                                                                     |
| Description | Retrieves the SEO score and detailed analysis recommendations for a specified post. Includes passed tests, warnings, and failed tests with actionable suggestions for improvement. A focus keyword must be set for full analysis. |
| Category    | `seo-analysis`                                                                                                                                                                                                                    |

**Input Schema:**

| Field     | Type    | Required | Description                   |
| --------- | ------- | -------- | ----------------------------- |
| `post_id` | integer | Yes      | The ID of the post to analyze |

**Output Schema:**

| Field                        | Type           | Description                                                   |
| ---------------------------- | -------------- | ------------------------------------------------------------- |
| `post_id`                    | integer        | The post ID                                                   |
| `seo_score`                  | integer        | Overall SEO score (0-100)                                     |
| `seo_rating`                 | string         | Rating label: "Good" (71-100), "OK" (51-70), or "Poor" (0-50) |
| `focus_keyword`              | string \| null | The focus keyword being analyzed (null if not set)            |
| `tests_passed`               | array          | List of passed SEO tests                                      |
| `tests_passed[].test_id`     | string         | Test identifier                                               |
| `tests_passed[].label`       | string         | Human-readable test name                                      |
| `tests_warning`              | array          | List of tests with warnings                                   |
| `tests_warning[].test_id`    | string         | Test identifier                                               |
| `tests_warning[].label`      | string         | Human-readable test name                                      |
| `tests_warning[].message`    | string         | Warning message explaining the issue                          |
| `tests_warning[].suggestion` | string         | Actionable suggestion for improvement                         |
| `tests_failed`               | array          | List of failed tests                                          |
| `tests_failed[].test_id`     | string         | Test identifier                                               |
| `tests_failed[].label`       | string         | Human-readable test name                                      |
| `tests_failed[].message`     | string         | Failure message explaining the issue                          |
| `tests_failed[].suggestion`  | string         | Actionable suggestion for improvement                         |

---

#### 3.2.2 `seo-abilities/find-posts-with-seo-issues`

**Purpose:** Find posts that have SEO issues or missing data.

| Attribute   | Value                                                                                                                                                                                                                                                |
| ----------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Label       | Find Posts with SEO Issues                                                                                                                                                                                                                           |
| Description | Searches for posts that have specific SEO issues such as missing meta titles, missing meta descriptions, missing focus keywords, images without alt text, or SEO scores below a specified threshold. Supports filtering by post type and pagination. |
| Category    | `seo-analysis`                                                                                                                                                                                                                                       |

**Input Schema:**

| Field             | Type          | Required | Description                                                                                                                         |
| ----------------- | ------------- | -------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| `issue_type`      | string (enum) | Yes      | Type of issue to find. One of: `missing_title`, `missing_description`, `missing_focus_keyword`, `missing_alt_text`, `low_seo_score` |
| `score_threshold` | integer       | No       | For `low_seo_score` only: posts with scores below this value are returned. Default: 50. Range: 1-100.                               |
| `post_type`       | string        | No       | Filter by specific post type slug. Default: all enabled post types.                                                                 |
| `limit`           | integer       | No       | Maximum results to return. Default: 20. Maximum: 100.                                                                               |
| `offset`          | integer       | No       | Number of results to skip for pagination. Default: 0.                                                                               |

**Output Schema:**

| Field                   | Type            | Description                                       |
| ----------------------- | --------------- | ------------------------------------------------- |
| `issue_type`            | string          | The issue type that was searched                  |
| `score_threshold`       | integer \| null | The score threshold used (for low_seo_score only) |
| `post_type_filter`      | string \| null  | The post type filter applied (null if all types)  |
| `total_found`           | integer         | Total posts matching criteria (for pagination)    |
| `returned_count`        | integer         | Number of posts in this response                  |
| `has_more`              | boolean         | True if more results exist beyond this page       |
| `posts`                 | array           | Array of posts with issues                        |
| `posts[].post_id`       | integer         | Post ID                                           |
| `posts[].post_title`    | string          | Post title                                        |
| `posts[].post_type`     | string          | Post type slug                                    |
| `posts[].post_url`      | string          | Permalink                                         |
| `posts[].edit_url`      | string          | WordPress admin edit URL                          |
| `posts[].seo_score`     | integer \| null | Current SEO score (if available)                  |
| `posts[].issue_details` | string          | Human-readable description of the specific issue  |

---

### 3.3 Category: SEO Images

#### 3.3.1 `seo-abilities/get-post-images`

**Purpose:** Retrieve all images in a post with their alt text status.

| Attribute   | Value                                                                                                                                                                                                                                                                     |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Label       | Get Post Images                                                                                                                                                                                                                                                           |
| Description | Retrieves all images within a post's content and featured image, along with their current alt text. Useful for identifying images that need alt text optimization for SEO and accessibility. External images (not in Media Library) are identified but cannot be updated. |
| Category    | `seo-images`                                                                                                                                                                                                                                                              |

**Input Schema:**

| Field     | Type    | Required | Description                           |
| --------- | ------- | -------- | ------------------------------------- |
| `post_id` | integer | Yes      | The ID of the post to scan for images |

**Output Schema:**

| Field                            | Type            | Description                                            |
| -------------------------------- | --------------- | ------------------------------------------------------ |
| `post_id`                        | integer         | The post ID                                            |
| `post_title`                     | string          | The post title (for reference)                         |
| `total_images`                   | integer         | Total number of images found                           |
| `images_with_alt`                | integer         | Count of images that have alt text                     |
| `images_without_alt`             | integer         | Count of images missing alt text                       |
| `featured_image`                 | object \| null  | Featured image data (null if no featured image)        |
| `featured_image.attachment_id`   | integer         | Attachment ID                                          |
| `featured_image.url`             | string          | Image URL                                              |
| `featured_image.alt_text`        | string \| null  | Current alt text (null if not set)                     |
| `featured_image.filename`        | string          | Original filename                                      |
| `content_images`                 | array           | Images found in post content                           |
| `content_images[].attachment_id` | integer \| null | Attachment ID (null if external image)                 |
| `content_images[].url`           | string          | Image URL                                              |
| `content_images[].alt_text`      | string \| null  | Current alt text                                       |
| `content_images[].is_external`   | boolean         | True if image is hosted externally (cannot be updated) |
| `content_images[].filename`      | string          | Filename extracted from URL                            |

---

#### 3.3.2 `seo-abilities/update-image-alt-text`

**Purpose:** Update alt text for a specific image.

| Attribute   | Value                                                                                                                                                                                                  |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Label       | Update Image Alt Text                                                                                                                                                                                  |
| Description | Updates the alt text for a specific image in the WordPress Media Library. The attachment must be a valid image type (not a document or video). External images cannot be updated through this ability. |
| Category    | `seo-images`                                                                                                                                                                                           |

**Input Schema:**

| Field           | Type    | Required | Description                                                                          |
| --------------- | ------- | -------- | ------------------------------------------------------------------------------------ |
| `attachment_id` | integer | Yes      | The attachment ID of the image in the Media Library                                  |
| `alt_text`      | string  | Yes      | New alt text for the image. Should be descriptive and relevant to the image content. |

**Output Schema:**

| Field               | Type           | Description                                     |
| ------------------- | -------------- | ----------------------------------------------- |
| `success`           | boolean        | Whether the update was successful               |
| `attachment_id`     | integer        | The attachment ID                               |
| `previous_alt_text` | string \| null | Previous alt text value (for verification/undo) |
| `new_alt_text`      | string         | New alt text value                              |
| `image_url`         | string         | URL of the updated image                        |
| `image_filename`    | string         | Filename of the image                           |

---

#### 3.3.3 `seo-abilities/bulk-update-image-alt-text`

**Purpose:** Update alt text for multiple images in a single request.

| Attribute   | Value                                                                                                                                                                                                                          |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Label       | Bulk Update Image Alt Text                                                                                                                                                                                                     |
| Description | Updates alt text for multiple images in a single operation. Each image receives its own unique alt text. Respects the configured maximum items limit (default: 10). Only images in the WordPress Media Library can be updated. |
| Category    | `seo-images`                                                                                                                                                                                                                   |

**Input Schema:**

| Field                   | Type    | Required | Description                                                         |
| ----------------------- | ------- | -------- | ------------------------------------------------------------------- |
| `items`                 | array   | Yes      | Array of image updates. Maximum items determined by admin settings. |
| `items[].attachment_id` | integer | Yes      | Attachment ID of the image                                          |
| `items[].alt_text`      | string  | Yes      | New alt text for this image                                         |

**Output Schema:**

| Field                         | Type           | Description                        |
| ----------------------------- | -------------- | ---------------------------------- |
| `success`                     | boolean        | True only if ALL updates succeeded |
| `total_requested`             | integer        | Number of items in the request     |
| `total_processed`             | integer        | Number of items actually processed |
| `results`                     | array          | Individual results for each image  |
| `results[].attachment_id`     | integer        | Attachment ID                      |
| `results[].success`           | boolean        | Whether this image was updated     |
| `results[].previous_alt_text` | string \| null | Previous alt text (if successful)  |
| `results[].new_alt_text`      | string         | New alt text (if successful)       |
| `results[].error`             | string \| null | Error message (if failed)          |

---

## 4. Admin Settings

### 4.1 Settings Page Location

**Settings → SEO Abilities**

The settings page is placed under the WordPress Settings menu as this is an independent extension, not part of any specific SEO plugin.

### 4.2 Settings Fields

| Setting                  | Type                 | Default      | Description                                                                                                                                      |
| ------------------------ | -------------------- | ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Enabled Post Types**   | Multi-checkbox       | Posts, Pages | Select which post types the SEO abilities can manage. Only public post types are shown.                                                          |
| **Bulk Operation Limit** | Number input (1-100) | 10           | Maximum number of items allowed per bulk operation request. Lower values reduce server load; higher values improve efficiency for large batches. |

### 4.3 Post Type Selection Logic

**Display:** All public post types registered in WordPress

**Exclude from list:**

-   `attachment`
-   `revision`
-   `nav_menu_item`
-   `custom_css`
-   `customize_changeset`
-   `oembed_cache`
-   `wp_block`
-   `wp_template`
-   `wp_template_part`
-   `wp_global_styles`
-   `wp_navigation`

**Default enabled:** `post`, `page`

**Display format:** Show post type label with slug in parentheses for clarity (e.g., "Products (product)")

---

## 5. User Stories

### 5.1 AI Agent Stories

| ID        | Story                                                                                                                                                          |
| --------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **US-01** | As an AI agent, I want to retrieve the current SEO meta fields for a post so that I can understand the current state before making recommendations or changes. |
| **US-02** | As an AI agent, I want to update SEO meta fields for a post so that I can help users optimize their content for search engines.                                |
| **US-03** | As an AI agent, I want to retrieve social sharing meta data so that I can understand how a post will appear when shared on Facebook and Twitter.               |
| **US-04** | As an AI agent, I want to update social sharing meta data so that I can optimize how posts appear on social media platforms.                                   |
| **US-05** | As an AI agent, I want to retrieve the SEO score and analysis results so that I can provide specific, actionable recommendations to improve a post's SEO.      |
| **US-06** | As an AI agent, I want to find posts with specific SEO issues so that I can help users identify and fix SEO problems across their site systematically.         |
| **US-07** | As an AI agent, I want to retrieve all images in a post with their alt text status so that I can identify images needing alt text for accessibility and SEO.   |
| **US-08** | As an AI agent, I want to update image alt text so that I can help users improve image SEO and accessibility.                                                  |
| **US-09** | As an AI agent, I want to update SEO meta for multiple posts in one request so that I can efficiently help users with large-scale SEO improvements.            |
| **US-10** | As an AI agent, I want to update alt text for multiple images in one request so that I can efficiently fix alt text issues across multiple images.             |

### 5.2 Administrator Stories

| ID        | Story                                                                                                                                                                |
| --------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **US-11** | As a site administrator, I want to configure which post types are available for SEO abilities so that I can control the scope of AI-assisted SEO management.         |
| **US-12** | As a site administrator, I want to set the maximum items for bulk operations so that I can prevent server timeouts or resource exhaustion on my hosting environment. |
| **US-13** | As a site administrator, I want to see a clear warning if the required SEO plugin is deactivated so that I understand why the SEO abilities are not functioning.     |

---

## 6. Acceptance Criteria

### 6.1 Ability: Get SEO Meta (US-01)

| #       | Criterion                                                                                                                                                                                                                         |
| ------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-01.1 | Given a valid post ID for an enabled post type, when the ability is executed, then it returns the complete SEO meta data structure with all fields populated                                                                      |
| AC-01.2 | Given a post with no custom SEO meta set, when the ability is executed, then it returns `null` for `seo_title`, `seo_description`, and `focus_keyword` with `is_seo_title_default` and `is_seo_description_default` set to `true` |
| AC-01.3 | Given a post ID that doesn't exist, when the ability is executed, then it returns a verbose error: "Post not found. The post ID {id} does not exist or has been deleted."                                                         |
| AC-01.4 | Given a post ID for a disabled post type, when the ability is executed, then it returns a verbose error: "Post type '{type}' is not enabled for SEO abilities. Enable it in Settings → SEO Abilities."                            |
| AC-01.5 | Given a user without edit permission for the post, when the ability is executed, then it returns a permission error: "You do not have permission to view SEO data for this post. Required capability: edit_post"                  |

### 6.2 Ability: Update SEO Meta (US-02)

| #       | Criterion                                                                                                                                                                                      |
| ------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-02.1 | Given valid input with one or more SEO fields, when the ability is executed, then only the provided fields are updated and omitted fields remain unchanged                                     |
| AC-02.2 | Given no SEO fields provided (only post_id), when the ability is executed, then it returns an error: "No fields to update. Provide at least one of: seo_title, seo_description, focus_keyword" |
| AC-02.3 | Given a user without edit permission for the post, when the ability is executed, then it returns a permission error with the required capability                                               |
| AC-02.4 | Given a successful update, when the ability completes, then the response includes the `updated_fields` array listing which fields changed and `current_values` object reflecting the new state |
| AC-02.5 | Given an empty string for a field value, when the ability is executed, then that field is cleared (set to empty/default)                                                                       |

### 6.3 Ability: Get Social Meta (US-03)

| #       | Criterion                                                                                                                                                                                         |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-03.1 | Given a valid post ID, when the ability is executed, then it returns both Facebook and Twitter meta data structures                                                                               |
| AC-03.2 | Given a post with Twitter set to use Facebook data, when the ability is executed, then `twitter.use_facebook` is `true` and Twitter-specific title/description fields reflect the Facebook values |
| AC-03.3 | Given an image attachment ID in the response, when that ID is used with other abilities, then it corresponds to a valid image attachment in the Media Library                                     |

### 6.4 Ability: Update Social Meta (US-04)

| #       | Criterion                                                                                                                                                                                                     |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-04.1 | Given valid Facebook meta fields, when the ability is executed, then the Open Graph meta tags are updated                                                                                                     |
| AC-04.2 | Given `twitter_use_facebook` set to `true`, when the ability is executed, then Twitter-specific fields are cleared and Twitter inherits Facebook data                                                         |
| AC-04.3 | Given an invalid attachment ID for image fields, when the ability is executed, then it returns an error: "Invalid attachment ID {id}. The attachment does not exist or is not an image in the Media Library." |

### 6.5 Ability: Get SEO Score (US-05)

| #       | Criterion                                                                                                                                                                                            |
| ------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-05.1 | Given a valid post ID with a focus keyword set, when the ability is executed, then it returns the SEO score (0-100) and categorized test results (passed, warning, failed)                           |
| AC-05.2 | Given a post without a focus keyword, when the ability is executed, then `focus_keyword` is `null` and the response indicates limited analysis is available with a suggestion to set a focus keyword |
| AC-05.3 | Given test results, when returned, then each test includes `test_id` and `label`, and warnings/failures additionally include `message` and `suggestion` fields                                       |
| AC-05.4 | Given the SEO score, when categorized into rating, then it follows: 0-50 = "Poor", 51-70 = "OK", 71-100 = "Good"                                                                                     |

### 6.6 Ability: Find Posts with SEO Issues (US-06)

| #       | Criterion                                                                                                                                                          |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| AC-06.1 | Given `issue_type` of `missing_title`, when executed, then it returns posts where SEO title is empty or not explicitly set                                         |
| AC-06.2 | Given `issue_type` of `missing_description`, when executed, then it returns posts where meta description is empty or not explicitly set                            |
| AC-06.3 | Given `issue_type` of `missing_focus_keyword`, when executed, then it returns posts where focus keyword is empty or not set                                        |
| AC-06.4 | Given `issue_type` of `missing_alt_text`, when executed, then it returns posts containing at least one image (in content or as featured image) that lacks alt text |
| AC-06.5 | Given `issue_type` of `low_seo_score` with `score_threshold` of 60, when executed, then it returns only posts with SEO score below 60                              |
| AC-06.6 | Given `post_type` filter parameter, when executed, then results are limited to that specific post type only                                                        |
| AC-06.7 | Given results exceed `limit`, when executed, then `total_found` reflects the true total count, `returned_count` matches `limit`, and `has_more` is `true`          |
| AC-06.8 | Given pagination with `offset`, when executed, then results skip the specified number of posts                                                                     |

### 6.7 Ability: Get Post Images (US-07)

| #       | Criterion                                                                                                                                     |
| ------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-07.1 | Given a post with a featured image, when executed, then `featured_image` object is fully populated with attachment details including alt text |
| AC-07.2 | Given a post without a featured image, when executed, then `featured_image` is `null`                                                         |
| AC-07.3 | Given images embedded in post content, when executed, then `content_images` array contains all `<img>` elements found                         |
| AC-07.4 | Given an external image (URL not in Media Library), when found, then `is_external` is `true` and `attachment_id` is `null`                    |
| AC-07.5 | Given the image counts, when calculated, then `images_with_alt` + `images_without_alt` equals `total_images`                                  |

### 6.8 Ability: Update Image Alt Text (US-08)

| #       | Criterion                                                                                                                                                                                    |
| ------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AC-08.1 | Given a valid image attachment ID and alt text, when executed, then the image's `_wp_attachment_image_alt` meta is updated                                                                   |
| AC-08.2 | Given an attachment ID that isn't an image (e.g., PDF, video), when executed, then it returns an error: "Attachment {id} is not an image. Only image attachments can have alt text updated." |
| AC-08.3 | Given a non-existent attachment ID, when executed, then it returns an error: "Attachment not found. ID {id} does not exist in the Media Library."                                            |
| AC-08.4 | Given a successful update, when completed, then response includes both `previous_alt_text` and `new_alt_text` for verification                                                               |

### 6.9 Ability: Bulk Update SEO Meta (US-09)

| #       | Criterion                                                                                                                                                                                                                                                                                        |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| AC-09.1 | Given items within the configured bulk limit, when executed, then all items are processed                                                                                                                                                                                                        |
| AC-09.2 | Given items exceeding the configured bulk limit, when executed, then the entire request is rejected with error: "Bulk operation limit exceeded. Maximum {limit} items allowed per request. Reduce your request size or ask the administrator to increase the limit in Settings → SEO Abilities." |
| AC-09.3 | Given some items fail (e.g., permission denied on specific posts), when executed, then overall `success` is `false` but `results` includes individual status for each item, allowing partial success tracking                                                                                    |
| AC-09.4 | Given all items succeed, when executed, then `success` is `true` and `total_processed` equals `total_requested`                                                                                                                                                                                  |

### 6.10 Ability: Bulk Update Image Alt Text (US-10)

| #       | Criterion                                                                                                                   |
| ------- | --------------------------------------------------------------------------------------------------------------------------- |
| AC-10.1 | Given items within the configured bulk limit, when executed, then all items are processed                                   |
| AC-10.2 | Given items exceeding the configured limit, when executed, then it returns the same bulk limit exceeded error format        |
| AC-10.3 | Given mixed success/failure results, when executed, then each item's result is reported individually in the `results` array |

### 6.11 Admin Settings (US-11, US-12)

| #       | Criterion                                                                                                                                |
| ------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| AC-11.1 | Given the settings page loads, when displayed, then all public post types are listed with checkboxes showing their label and slug        |
| AC-11.2 | Given a fresh plugin installation, when settings page loads, then "Posts" and "Pages" checkboxes are pre-selected                        |
| AC-11.3 | Given the bulk limit input, when set to a value between 1-100, then that value is saved and enforced by bulk abilities                   |
| AC-11.4 | Given a bulk limit value outside 1-100 range entered, when saved, then it is automatically clamped to the nearest valid value (1 or 100) |
| AC-11.5 | Given settings are saved, when an ability is subsequently executed, then it respects the current saved settings                          |

### 6.12 Dependency Handling (US-13)

| #       | Criterion                                                                                                                                                                                              |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| AC-12.1 | Given the required SEO plugin is active, when the plugin loads, then all abilities are registered and functional                                                                                       |
| AC-12.2 | Given the required SEO plugin is deactivated, when the WordPress admin dashboard loads, then a prominent, dismissible admin notice appears explaining which SEO plugin is required                     |
| AC-12.3 | Given the required SEO plugin is deactivated, when any SEO ability is called via the Abilities API, then it returns an error explaining the dependency with HTTP status 503                            |
| AC-12.4 | Given the required SEO plugin is reactivated after being deactivated, when the next WordPress request occurs, then abilities function normally without requiring plugin reactivation or cache clearing |

---

## 7. Business Rules

### 7.1 Permission Rules

| Rule  | Description                                                                                                                 |
| ----- | --------------------------------------------------------------------------------------------------------------------------- |
| BR-01 | A user can only read/write SEO data for posts they have the `edit_post` capability for (checked per-post)                   |
| BR-02 | Bulk operations check permissions for each individual item; items the user cannot edit are skipped and reported as failures |
| BR-03 | The plugin settings page requires `manage_options` capability (typically administrators only)                               |

### 7.2 Post Type Rules

| Rule  | Description                                                                                                                 |
| ----- | --------------------------------------------------------------------------------------------------------------------------- |
| BR-04 | Only post types explicitly enabled in plugin settings can be managed via abilities                                          |
| BR-05 | The post type selection list excludes WordPress internal/system post types                                                  |
| BR-06 | If a post type is disabled in settings after SEO data exists, the data remains but cannot be read or modified via abilities |

### 7.3 Data Validation Rules

| Rule  | Description                                                                                                                            |
| ----- | -------------------------------------------------------------------------------------------------------------------------------------- |
| BR-07 | SEO title has no hard character limit enforced, but responses should indicate if length exceeds 60 characters (recommended max)        |
| BR-08 | SEO description has no hard character limit enforced, but responses should indicate if length exceeds 160 characters (recommended max) |
| BR-09 | Focus keyword is stored exactly as provided; the SEO plugin handles its own keyword analysis                                           |
| BR-10 | Image attachment IDs must reference valid attachments with MIME type starting with `image/`                                            |

### 7.4 Bulk Operation Rules

| Rule  | Description                                                                                                                             |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------- |
| BR-11 | Bulk operations are atomic per-item: each item succeeds or fails independently of others                                                |
| BR-12 | If the number of items in a bulk request exceeds the configured limit, the entire request is rejected before processing (not truncated) |
| BR-13 | The configured bulk limit applies equally to all bulk abilities (SEO meta and image alt text)                                           |

### 7.5 Error Handling Rules

| Rule  | Description                                                                                                                   |
| ----- | ----------------------------------------------------------------------------------------------------------------------------- |
| BR-14 | All error messages must include actionable guidance that helps AI agents understand how to resolve the issue                  |
| BR-15 | Error messages should reference specific settings locations or capabilities needed to resolve permission/configuration issues |
| BR-16 | Missing optional input fields should not cause errors; they should be gracefully ignored                                      |

---

## 8. Error Message Specifications

All error messages follow consistent patterns designed for AI agent consumption, providing context, explanation, and resolution guidance.

### 8.1 Error Message Categories

| Error Type          | HTTP Status | Message Pattern                                                             |
| ------------------- | ----------- | --------------------------------------------------------------------------- |
| Not Found           | 404         | "{Entity} not found. {Specific details about what doesn't exist}."          |
| Permission Denied   | 403         | "You do not have permission to {action}. Required capability: {capability}" |
| Validation Error    | 400         | "Invalid {field}. {What's wrong}. {How to fix}."                            |
| Configuration Error | 400         | "{Setting} is not configured correctly. {How to fix}."                      |
| Dependency Error    | 503         | "{Dependency} is not available. {How to resolve}."                          |
| Limit Exceeded      | 400         | "{Limit type} exceeded. Maximum {n} allowed. {How to resolve}."             |

### 8.2 Standard Error Messages

**Post not found:**

```
Post not found. The post ID 12345 does not exist or has been deleted.
```

**Permission denied (view):**

```
You do not have permission to view SEO data for this post. Required capability: edit_post for post ID 456.
```

**Permission denied (update):**

```
You do not have permission to update SEO data for this post. Required capability: edit_post for post ID 456.
```

**Post type not enabled:**

```
Post type 'product' is not enabled for SEO abilities. Enable it in Settings → SEO Abilities, or contact your site administrator.
```

**Invalid attachment:**

```
Invalid attachment ID 789. The attachment does not exist or is not an image in the Media Library.
```

**Attachment not an image:**

```
Attachment 789 is not an image. Only image attachments (JPEG, PNG, GIF, WebP) can have alt text updated. This attachment is of type 'application/pdf'.
```

**Bulk limit exceeded:**

```
Bulk operation limit exceeded. Maximum 10 items allowed per request. You requested 25 items. Reduce your request size or ask the administrator to increase the limit in Settings → SEO Abilities.
```

**SEO plugin not active:**

```
Required SEO plugin is not active. This ability requires a supported SEO plugin to function. Please install and activate a supported SEO plugin.
```

**No fields to update:**

```
No fields to update. Provide at least one of: seo_title, seo_description, focus_keyword. All fields are optional, but at least one must be included in the request.
```

**Empty bulk items:**

```
No items provided. The 'items' array must contain at least one item to process.
```

---

## 9. Out of Scope (v2+)

The following features are explicitly excluded from version 1 and documented for future consideration:

| Feature                           | Reason for Deferral                                                                                     |
| --------------------------------- | ------------------------------------------------------------------------------------------------------- |
| Additional SEO Plugin Providers   | v1 focuses on Rank Math; Yoast SEO, All in One SEO, etc. are planned for v2+                            |
| Schema/Structured Data Management | Complex feature with many schema types (Article, Product, FAQ, etc.); requires separate detailed design |
| Multiple Focus Keywords           | Premium feature in most SEO plugins; free versions typically support single focus keyword               |
| AI-Powered Alt Text Generation    | Requires external AI API integration and associated costs; keeps plugin focused on core abilities       |
| Custom Field Image Support        | Significant complexity due to ACF, Meta Box, and other meta field plugin variations                     |
| Local Business SEO                | Premium feature with complex location/business data structures                                          |
| Redirect Management               | Separate concern from on-page SEO; would require different permission model                             |
| 404 Monitor Integration           | Separate concern from on-page SEO optimization                                                          |
| Sitemap Management                | Low value for AI agent use cases; SEO plugins handle this automatically                                 |
| WooCommerce Product SEO           | Would require WooCommerce-specific logic; better as separate extension                                  |
| Bulk Social Meta Updates          | Lower priority; can be added if there's demand                                                          |

---

## 10. Summary of Abilities

| Ability Slug                               | Category     | Operation  | Description                                              |
| ------------------------------------------ | ------------ | ---------- | -------------------------------------------------------- |
| `seo-abilities/get-seo-meta`               | seo-meta     | Read       | Get SEO title, description, and focus keyword for a post |
| `seo-abilities/update-seo-meta`            | seo-meta     | Write      | Update SEO title, description, and/or focus keyword      |
| `seo-abilities/get-social-meta`            | seo-meta     | Read       | Get Facebook Open Graph and Twitter Card data            |
| `seo-abilities/update-social-meta`         | seo-meta     | Write      | Update Facebook Open Graph and Twitter Card data         |
| `seo-abilities/bulk-update-seo-meta`       | seo-meta     | Bulk Write | Update SEO meta for multiple posts                       |
| `seo-abilities/get-seo-score`              | seo-analysis | Read       | Get SEO score and detailed recommendations               |
| `seo-abilities/find-posts-with-seo-issues` | seo-analysis | Read       | Find posts with specific SEO problems                    |
| `seo-abilities/get-post-images`            | seo-images   | Read       | List all images in post with alt text status             |
| `seo-abilities/update-image-alt-text`      | seo-images   | Write      | Update alt text for a single image                       |
| `seo-abilities/bulk-update-image-alt-text` | seo-images   | Bulk Write | Update alt text for multiple images                      |

**Total: 10 abilities across 3 categories**

---

## Appendix A: Ability Registration Summary

For implementation reference, here's the mapping of abilities to their Abilities API registration:

```
Namespace: seo-abilities
Categories: seo-meta, seo-analysis, seo-images

seo-abilities/get-seo-meta          → category: seo-meta
seo-abilities/update-seo-meta       → category: seo-meta
seo-abilities/get-social-meta       → category: seo-meta
seo-abilities/update-social-meta    → category: seo-meta
seo-abilities/bulk-update-seo-meta  → category: seo-meta
seo-abilities/get-seo-score         → category: seo-analysis
seo-abilities/find-posts-with-seo-issues → category: seo-analysis
seo-abilities/get-post-images       → category: seo-images
seo-abilities/update-image-alt-text → category: seo-images
seo-abilities/bulk-update-image-alt-text → category: seo-images
```

---

## Appendix B: Provider Architecture (v2+ Consideration)

The plugin is designed with future multi-provider support in mind:

```
┌─────────────────────────────────────────────────────────┐
│                    Abilities Layer                       │
│  (seo-abilities/get-seo-meta, seo-abilities/update-...)  │
└─────────────────────────┬───────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│                   Provider Interface                     │
│  get_seo_meta(), update_seo_meta(), get_seo_score()...  │
└─────────────────────────┬───────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          ▼               ▼               ▼
    ┌───────────┐   ┌───────────┐   ┌───────────┐
    │ Rank Math │   │   Yoast   │   │   AIOSEO  │
    │ Provider  │   │ Provider  │   │ Provider  │
    │   (v1)    │   │   (v2+)   │   │   (v2+)   │
    └───────────┘   └───────────┘   └───────────┘
```

This architecture allows:

-   Consistent ability signatures regardless of backend SEO plugin
-   Easy addition of new SEO plugin providers
-   Potential for auto-detection of active SEO plugin

---

## Appendix C: Related Documentation

-   [WordPress Abilities API Documentation](https://make.wordpress.org/core/)
-   [WordPress Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

---

_Document generated for the SEO Abilities WordPress plugin project._
_This is an independent project and is not affiliated with any third-party SEO plugin vendors or the WordPress Foundation._
