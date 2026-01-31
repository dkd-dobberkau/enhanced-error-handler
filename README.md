# Enhanced Error Handler for TYPO3

[![TYPO3](https://img.shields.io/badge/TYPO3-12.4%20%7C%2013.4-orange.svg)](https://typo3.org)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/dkd-dobberkau/enhanced-error-handler.svg)](https://packagist.org/packages/dkd-dobberkau/enhanced-error-handler)

A debug exception handler for TYPO3 with copy-to-clipboard functionality, using TYPO3's standard styling.

## Features

- **Copy-to-Clipboard Buttons**: Quickly copy exception messages, stack traces, code snippets, and file paths
- **TYPO3 Standard UI**: Matches the familiar TYPO3 backend look and feel
- **Collapsible Stack Frames**: Click on any frame to expand/collapse code context
- **Vendor Frame Highlighting**: Application code is visually distinct from vendor/framework code
- **Code Snippets**: See the relevant code around each error location
- **Keyboard Shortcuts**: Press `Ctrl+Shift+C` (or `Cmd+Shift+C` on Mac) to copy all exception details
- **Markdown Output**: Copy All generates Markdown-formatted output for easy pasting into issues
- **Toast Notifications**: Visual feedback when content is copied

## Requirements

- TYPO3 12.4 LTS or 13.4+
- PHP 8.1+

## Installation

### Via Composer

```bash
composer require dkd-dobberkau/enhanced-error-handler
```

### Manual Installation

1. Clone or download from [GitHub](https://github.com/dkd-dobberkau/enhanced-error-handler)
2. Extract to `packages/enhanced_error_handler/` or `typo3conf/ext/enhanced_error_handler/`
3. Add path repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/*"
        }
    ]
}
```

4. Then require the package:

```bash
composer require dkd-dobberkau/enhanced-error-handler:@dev
```

## Configuration

Add the following to your `config/system/additional.php`:

```php
<?php

// Register the enhanced debug exception handler
$GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] =
    \Dkd\EnhancedErrorHandler\Error\EnhancedDebugExceptionHandler::class;
```

### Recommended Development Settings

```php
<?php

// Enable error display
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;

// Allow all IPs to see debug output (development only!)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';

// Use enhanced handler
$GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] =
    \Dkd\EnhancedErrorHandler\Error\EnhancedDebugExceptionHandler::class;

// Convert more error types to exceptions for better debugging
$GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'] =
    E_ALL ^ E_NOTICE ^ E_WARNING ^ E_USER_ERROR ^ E_USER_NOTICE ^ E_USER_WARNING;
```

## Usage

### Copy Buttons

Each section of the error page has a copy button:

- **Copy All**: Copies the complete exception details in Markdown format
- **Message**: Copies just the exception message
- **Location**: Copies the file path and line number
- **Code Snippet**: Copies the code around the error
- **Stack Trace**: Copies the formatted stack trace

### Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+Shift+C` / `Cmd+Shift+C` | Copy all exception details |

### Stack Trace Navigation

- Click on any stack frame to expand/collapse it
- Expanded frames show the full file path and code context
- Vendor/framework frames are slightly dimmed for easier navigation
- The first frame is expanded by default

## Security Warning

⚠️ **Never use this handler in production!**

This extension displays detailed error information including:
- Full file paths
- Code snippets
- Stack traces with arguments
- Server environment details

Always ensure `displayErrors` is set to `0` in production, and that `devIPmask` is properly configured.

## Customization

### Extending the Handler

You can extend the handler to add custom functionality:

```php
<?php

namespace MyVendor\MyExtension\Error;

use Dkd\EnhancedErrorHandler\Error\EnhancedDebugExceptionHandler;

class MyCustomExceptionHandler extends EnhancedDebugExceptionHandler
{
    protected function getHeader(): string
    {
        return '<div class="header">
            <img src="/path/to/your/logo.svg" alt="Logo">
            <h1>Something went wrong</h1>
        </div>';
    }
}
```

### Custom Styling

Override the `getEnhancedStylesheet()` method to customize colors:

```php
protected function getEnhancedStylesheet(): string
{
    $css = parent::getEnhancedStylesheet();

    $css .= '
        :root {
            --accent-orange: #your-brand-color;
        }
    ';

    return $css;
}
```

## Changelog

### 1.0.0

- Initial release
- Copy-to-clipboard functionality
- TYPO3 standard styling
- Collapsible stack frames
- Code snippets with line highlighting
- Keyboard shortcuts
- Toast notifications
- Markdown-formatted copy output

## Contributing

Contributions are welcome! Please feel free to submit a [Pull Request](https://github.com/dkd-dobberkau/enhanced-error-handler/pulls).

## License

GPL-2.0-or-later

## Credits

- Inspired by [Laravel Ignition](https://github.com/spatie/laravel-ignition)
- Built for [TYPO3 CMS](https://typo3.org)
