# Abilities API Extension for Rank Math SEO

Exposes Rank Math SEO functionality through the WordPress Abilities API, enabling AI agents to discover, read, and manage SEO settings programmatically.

## Features

-   **SEO Meta Management** - Read and update meta titles, descriptions, and focus keywords
-   **Social Media Meta** - Manage Facebook Open Graph and Twitter Card settings
-   **SEO Analysis** - Get SEO scores, recommendations, and find posts with issues
-   **Content Analysis** - Retrieve post content with metadata for context understanding
-   **Image Alt Text** - Audit and update image alt text with automatic sync to post content
-   **Bulk Operations** - Update multiple posts or images in a single operation

## Requirements

-   WordPress 6.9 or higher
-   PHP 7.4 or higher
-   [WordPress Abilities API](https://developer.wordpress.org/abilities-api/) enabled
-   [Rank Math SEO](https://rankmath.com/) plugin installed and activated

## Installation

### 1. Install Dependencies

First, install the required plugins:

1. **WordPress Abilities API** - Included in WordPress 6.9+
2. **Rank Math SEO** - Download and activate from [WordPress.org](https://wordpress.org/plugins/seo-by-rank-math/) or [RankMath.com](https://rankmath.com/)

### 2. Install Abilities API Extension for Rank Math SEO

**Option A: From Release (Recommended)**

1. Download the latest release zip file (`wp-abilities-seo-extension-latest.zip`)
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin
3. Upload the zip file and click "Install Now"
4. Activate the plugin

**Option B: From Source**

1. Clone this repository to `wp-content/plugins/wp-abilities-seo-extension`
2. Run composer to install dependencies:
    ```bash
    cd wp-content/plugins/wp-abilities-seo-extension
    composer install
    ```
3. Activate the plugin in WordPress admin

## MCP Setup Guide

The MCP Adapter bridges WordPress abilities with AI clients like Claude Desktop, Cursor, or VS Code. There are two transport methods available:

### Option A: STDIO Transport (Recommended for Local Development)

Uses WP-CLI to communicate directly with WordPress. Best for local development environments.

**Prerequisites:**

-   [WP-CLI](https://wp-cli.org/) installed and accessible in your PATH

**Configuration:**

Add this to your MCP client configuration file:

**Claude Desktop** (`~/Library/Application Support/Claude/claude_desktop_config.json` on macOS):

```json
{
    "mcpServers": {
        "wordpress": {
            "command": "wp",
            "args": ["--path=/path/to/your/wordpress", "mcp-adapter", "serve", "--server=mcp-adapter-default-server", "--user=admin"]
        }
    }
}
```

**Claude Code** (`.claude/settings.local.json` in your project or `~/.claude.json` globally):

```json
{
    "mcpServers": {
        "wordpress": {
            "command": "wp",
            "args": ["--path=/path/to/your/wordpress", "mcp-adapter", "serve", "--server=mcp-adapter-default-server", "--user=admin"]
        }
    }
}
```

Replace:

-   `/path/to/your/wordpress` with the absolute path to your WordPress installation
-   `admin` with a valid WordPress username that has appropriate permissions

### Option B: HTTP Transport (For Remote Sites)

Uses HTTP requests to communicate with WordPress. Works with any WordPress site accessible via URL.

**Prerequisites:**

-   Node.js and npm installed
-   An Application Password for your WordPress user

**Creating an Application Password:**

1. Go to WordPress Admin > Users > Profile
2. Scroll down to "Application Passwords"
3. Enter a name (e.g., "MCP Adapter") and click "Add New Application Password"
4. Copy the generated password (you won't see it again)

**Configuration:**

```json
{
    "mcpServers": {
        "wordpress-http": {
            "command": "npx",
            "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
            "env": {
                "WP_API_URL": "https://your-site.com/wp-json/mcp/mcp-adapter-default-server",
                "WP_API_USERNAME": "your-username",
                "WP_API_PASSWORD": "your-application-password"
            }
        }
    }
}
```

Replace:

-   `https://your-site.com` with your WordPress site URL
-   `your-username` with your WordPress username
-   `your-application-password` with the Application Password you created

### Verifying the Setup

After configuring your MCP client, restart it and verify the connection:

1. The MCP client should show the WordPress server as connected
2. You should see tools available from the `seo-abilities` namespace
3. Try discovering abilities:
    ```
    Use the mcp-adapter-discover-abilities tool to list available abilities
    ```

## Available Abilities

All abilities use the `seo-abilities` namespace.

### Read Operations

| Ability                      | Description                                                         | Category     |
| ---------------------------- | ------------------------------------------------------------------- | ------------ |
| `get-seo-meta`               | Retrieves SEO meta title, description, and focus keyword for a post | seo-meta     |
| `get-social-meta`            | Retrieves Facebook Open Graph and Twitter Card meta data            | seo-meta     |
| `get-seo-score`              | Gets SEO score and detailed analysis recommendations                | seo-analysis |
| `find-posts-with-seo-issues` | Finds posts with missing meta, alt text, or low scores              | seo-analysis |
| `get-post-content`           | Retrieves post content with metadata for context understanding      | seo-analysis |
| `get-post-images`            | Gets all images in a post with their alt text status                | seo-images   |

### Write Operations

| Ability                      | Description                                                   | Category   |
| ---------------------------- | ------------------------------------------------------------- | ---------- |
| `update-seo-meta`            | Updates SEO meta title, description, and/or focus keyword     | seo-meta   |
| `update-social-meta`         | Updates Facebook Open Graph and Twitter Card meta data        | seo-meta   |
| `bulk-update-seo-meta`       | Updates SEO meta for multiple posts in one operation          | seo-meta   |
| `update-image-alt-text`      | Updates alt text for an image and syncs to all posts using it | seo-images |
| `bulk-update-image-alt-text` | Updates alt text for multiple images with sync                | seo-images |

## Configuration

### Settings

Navigate to **Settings > SEO Abilities** in the WordPress admin to configure:

-   **Enabled Post Types** - Select which post types can be managed via abilities (default: Posts and Pages)
-   **Bulk Operation Limit** - Maximum items per bulk operation (default: 10)

### Filter Hooks

```php
// Modify supported post types programmatically
add_filter( 'seo_abilities_supported_post_types', function( $post_types ) {
    $post_types[] = 'product'; // Add WooCommerce products
    return $post_types;
});

// Modify bulk operation limit
add_filter( 'seo_abilities_bulk_limit', function( $limit ) {
    return 25; // Allow up to 25 items per bulk operation
});
```

## Usage Examples

Here are example use cases for the SEO abilities:

### Audit SEO Issues Across Your Site

```
Find all posts that are missing meta descriptions and have SEO scores below 70
```

The AI will use `find-posts-with-seo-issues` to identify problematic content.

### Optimize a Single Post

```
Get the SEO score for post ID 123, read its content, and suggest improvements for the meta description
```

The AI will use `get-seo-score`, `get-post-content`, and can then use `update-seo-meta` to apply changes.

### Bulk Update Image Alt Text

```
Find all images without alt text in post ID 456 and generate descriptive alt text for each
```

The AI will use `get-post-images` to find images, then `bulk-update-image-alt-text` to update them (automatically syncing to post content).

### Social Media Optimization

```
Update the Facebook and Twitter meta for post ID 789 to optimize for social sharing
```

The AI will use `get-social-meta` to see current values, then `update-social-meta` to apply improvements.

## Troubleshooting

### "WordPress Abilities API not available" Error

Ensure you are running WordPress 6.9 or later with the Abilities API enabled. This is a core WordPress feature.

### "No supported SEO plugin is active" Warning

This extension requires Rank Math SEO to be installed and activated. Other SEO plugins are not currently supported.

### Abilities Not Appearing in MCP Client

1. Verify the plugin is activated in WordPress
2. Check that your MCP configuration has the correct WordPress path
3. Ensure the WordPress user has `edit_posts` capability
4. Restart your MCP client after configuration changes

### Alt Text Not Syncing to Post Content

The sync feature updates alt text in posts that use `wp-image-{id}` class or `data-id` attribute. Images inserted through page builders or custom implementations may not be detected.

## Building for Distribution

### Local Build

To create a standalone distributable zip file locally:

```bash
# Make sure you're in the plugin directory
cd wp-content/plugins/wp-abilities-seo-extension

# Run the build script
./build.sh
```

The script will:

1. Create a clean build directory
2. Copy only the necessary plugin files
3. Install production dependencies via Composer (excludes dev dependencies)
4. Create a zip archive in the `dist/` directory

**Output files:**

-   `dist/wp-abilities-seo-extension-{version}.zip` - Versioned release
-   `dist/wp-abilities-seo-extension-latest.zip` - Latest release (convenience copy)

**Requirements:**

-   [Composer](https://getcomposer.org/) must be installed and available in your PATH
-   Bash shell (macOS, Linux, or WSL on Windows)

### GitHub Releases (Automated)

This repository includes a GitHub Actions workflow that automatically builds and publishes releases when you push a version tag.

**To create a new release:**

1. Update the version number in `wp-abilities-seo-extension.php` (both in the header and the constant)

2. Commit your changes:

    ```bash
    git add .
    git commit -m "Bump version to X.Y.Z"
    ```

3. Create and push a version tag:

    ```bash
    git tag vX.Y.Z
    git push origin main --tags
    ```

4. GitHub Actions will automatically:
    - Build the plugin
    - Create a new GitHub Release
    - Attach the zip files to the release
    - Generate release notes from commits

**View releases:** Go to your repository's "Releases" page on GitHub to download the built plugin zip files.

## License

GPL-2.0-or-later

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
