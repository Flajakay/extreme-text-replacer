# Extreme Text Replacer

A powerful WordPress plugin that allows you to replace raw text in posts, including text within Gutenberg blocks and HTML comments.

## Description

Extreme Text Replacer is designed for advanced WordPress users who need to perform precise text replacements across their site's content. Unlike typical search and replace tools, this plugin can search for and replace text that appears within WordPress Gutenberg block comments and HTML structure.

### Key Features

- Search and replace raw text strings across all posts
- Replace text within Gutenberg block comments (`<!-- wp:block -->`) 
- Support for HTML comments and structural elements
- Category filtering to limit replacements to specific sections
- Dry-run mode to preview changes before applying them
- Safety warnings when replacing potentially dangerous patterns
- Real-time previews of affected posts

### Use Cases

- Fixing broken Gutenberg blocks without editing each post individually
- Updating references to reusable blocks across your entire site
- Replacing outdated HTML patterns or comments
- Bulk fixing content issues after theme or plugin changes

## Installation

1. Download the plugin zip file
2. Navigate to your WordPress admin panel
3. Go to Plugins > Add New > Upload Plugin
4. Choose the downloaded zip file and click "Install Now"
5. Activate the plugin

## Usage

1. In your WordPress admin panel, go to Tools > Extreme Text Replacer
2. Enter the exact text you want to search for
3. Enter the text you want to replace it with (leave empty to remove the searched text)
4. Optionally select a specific category to limit the scope
5. Check "Dry Run" to preview changes without applying them
6. Click "Preview Changes" to see affected posts
7. If the preview looks good, uncheck "Dry Run" and click "Replace Text" to apply changes

## Important Safety Notes

- **Always backup your database before performing replacements**
- Use caution when replacing WordPress structural elements
- The plugin will display warnings when attempting to replace potentially dangerous patterns
- Always use the "Dry Run" feature first to preview changes

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Support

If you encounter any issues or have questions, please [open an issue](https://github.com/yourusername/extreme-text-replacer/issues) on the GitHub repository.

## Credits

Developed by Flajakay