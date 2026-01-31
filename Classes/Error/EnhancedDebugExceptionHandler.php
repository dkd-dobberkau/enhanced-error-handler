<?php

declare(strict_types=1);

namespace Dkd\EnhancedErrorHandler\Error;

use TYPO3\CMS\Core\Error\DebugExceptionHandler;

/**
 * Enhanced Debug Exception Handler with Copy-to-Clipboard functionality
 * Similar to Laravel Ignition's error pages
 */
class EnhancedDebugExceptionHandler extends DebugExceptionHandler
{
    /**
     * Formats and echoes the exception as XHTML with enhanced copy functionality.
     */
    public function echoExceptionWeb(\Throwable $exception): void
    {
        $this->sendStatusHeaders($exception);
        $this->writeLogEntries($exception, self::CONTEXT_WEB);

        $content = $this->getEnhancedContent($exception);
        $stylesheet = $this->getEnhancedStylesheet();
        $javascript = $this->getCopyButtonScript();

        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TYPO3 Exception</title>
    <style>' . $stylesheet . '</style>
</head>
<body>
    ' . $content . '
    ' . $javascript . '
</body>
</html>';
    }

    /**
     * Generates the enhanced HTML content with copy buttons
     */
    protected function getEnhancedContent(\Throwable $throwable): string
    {
        $throwables = $this->getAllThrowables($throwable);
        $total = count($throwables);

        $content = '<div class="exception-container">';
        $content .= $this->getHeader();

        foreach ($throwables as $index => $t) {
            $content .= $this->getEnhancedSingleThrowableContent($t, $index, $total);
        }

        $content .= '</div>';

        return $content;
    }

    /**
     * Get header with logo
     */
    protected function getHeader(): string
    {
        return '
        <div class="header">
            <div class="logo">' . $this->getTypo3LogoAsSvg() . '</div>
            <h1>TYPO3 Exception</h1>
        </div>';
    }

    /**
     * Returns TYPO3 logo as SVG
     */
    protected function getTypo3LogoAsSvg(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill="#ff8700" d="M0 0h16v16H0z"/><path fill="#fff" d="M11.5 3.5h-7v1.75h2.563V12.5h1.875V5.25H11.5z"/></svg>';
    }

    /**
     * Format arguments for display
     */
    protected function formatArgs(array $args): string
    {
        if (empty($args)) {
            return '';
        }
        $parts = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $parts[] = 'array';
            } elseif (is_object($arg)) {
                $parts[] = get_class($arg);
            } else {
                $parts[] = $this->escapeHtml(substr((string)$arg, 0, 30));
            }
        }
        return implode(', ', array_slice($parts, 0, 3)) . (count($parts) > 3 ? ', ...' : '');
    }

    /**
     * Renders enhanced HTML for a single throwable with copy buttons
     */
    protected function getEnhancedSingleThrowableContent(\Throwable $throwable, int $index, int $total): string
    {
        $exceptionClass = get_class($throwable);
        $exceptionCode = $throwable->getCode();
        $message = $this->escapeHtml($throwable->getMessage());
        $file = $throwable->getFile();
        $line = $throwable->getLine();

        // Build unique IDs for copy functionality
        $uniqueId = 'exception-' . $index . '-' . substr(md5($message . $file . $line), 0, 8);

        $content = '<div class="exception-block">';

        // Exception header with copy all button
        $content .= '<div class="exception-header">';
        $content .= '<div class="exception-title">';
        if ($total > 1) {
            $content .= '<span class="exception-number">' . ($index + 1) . '/' . $total . '</span>';
        }
        $content .= '<span class="exception-class">' . $this->escapeHtml($exceptionClass) . '</span>';
        if ($exceptionCode > 0) {
            $content .= ' <span class="exception-code">(#' . $exceptionCode . ')</span>';
        }
        $content .= '</div>';
        $content .= '<button class="copy-btn copy-all" onclick="copyAll(\'' . $uniqueId . '\')" title="Copy all exception details">';
        $content .= '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
        $content .= '<span>Copy All</span>';
        $content .= '</button>';
        $content .= '</div>';

        // Exception message with copy button
        $content .= '<div class="exception-message-container">';
        $content .= '<div class="section-header">';
        $content .= '<span class="section-title">Message</span>';
        $content .= '<button class="copy-btn" onclick="copyElement(\'' . $uniqueId . '-message\')" title="Copy message">';
        $content .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
        $content .= '</button>';
        $content .= '</div>';
        $content .= '<div id="' . $uniqueId . '-message" class="exception-message">' . nl2br($message) . '</div>';
        $content .= '</div>';

        // File location with copy button
        $content .= '<div class="exception-location-container">';
        $content .= '<div class="section-header">';
        $content .= '<span class="section-title">Location</span>';
        $content .= '<button class="copy-btn" onclick="copyElement(\'' . $uniqueId . '-location\')" title="Copy file path">';
        $content .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
        $content .= '</button>';
        $content .= '</div>';
        $content .= '<div id="' . $uniqueId . '-location" class="exception-location">';
        $content .= '<span class="file-path">' . $this->escapeHtml($file) . '</span>';
        $content .= '<span class="line-number">:' . $line . '</span>';
        $content .= '</div>';
        $content .= '</div>';

        // Code snippet with copy button
        $codeSnippet = $this->getCodeSnippet($file, $line);
        if ($codeSnippet) {
            $content .= '<div class="code-snippet-container">';
            $content .= '<div class="section-header">';
            $content .= '<span class="section-title">Code Snippet</span>';
            $content .= '<button class="copy-btn" onclick="copyElement(\'' . $uniqueId . '-code\')" title="Copy code snippet">';
            $content .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
            $content .= '</button>';
            $content .= '</div>';
            $content .= '<div id="' . $uniqueId . '-code" class="code-snippet">' . $codeSnippet . '</div>';
            $content .= '</div>';
        }

        // Stack trace with copy button
        $trace = $throwable->getTrace();
        if (!empty($trace)) {
            $content .= '<div class="stack-trace-container">';
            $content .= '<div class="section-header">';
            $content .= '<span class="section-title">Stack Trace</span>';
            $content .= '<button class="copy-btn" onclick="copyStackTrace(\'' . $uniqueId . '-trace\')" title="Copy stack trace">';
            $content .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
            $content .= '</button>';
            $content .= '</div>';
            $content .= '<div id="' . $uniqueId . '-trace" class="stack-trace">';
            $content .= $this->getEnhancedBacktraceCode($trace, $uniqueId);
            $content .= '</div>';
            $content .= '</div>';
        }

        // Hidden element for "copy all" functionality - Markdown formatted
        $content .= '<div id="' . $uniqueId . '-all" style="display:none;">';
        $content .= "## " . $exceptionClass . "\n\n";
        if ($exceptionCode > 0) {
            $content .= "**Code:** `" . $exceptionCode . "`\n\n";
        }
        $content .= "**Message:**\n> " . str_replace("\n", "\n> ", $throwable->getMessage()) . "\n\n";
        $content .= "**Location:** `" . $file . ":" . $line . "`\n\n";
        
        // Code snippet as Markdown code block
        $content .= "**Code Snippet:**\n";
        $content .= "```php\n";
        $content .= $this->getPlainCodeSnippet($file, $line);
        $content .= "```\n\n";
        
        // Stack trace as code block
        $content .= "**Stack Trace:**\n";
        $content .= "```\n" . $throwable->getTraceAsString() . "\n```\n";
        $content .= '</div>';

        $content .= '</div>';

        return $content;
    }

    /**
     * Renders enhanced backtrace with collapsible frames
     */
    protected function getEnhancedBacktraceCode(array $trace, string $uniqueId): string
    {
        $content = '<div class="trace-frames">';

        foreach ($trace as $index => $frame) {
            $frameId = $uniqueId . '-frame-' . $index;
            $file = $frame['file'] ?? '[internal function]';
            $line = $frame['line'] ?? 0;
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';
            $function = $frame['function'] ?? '';
            $args = isset($frame['args']) ? $this->flattenArgs($frame['args']) : [];

            $isExpanded = $index === 0 ? 'expanded' : '';
            $isVendor = strpos($file, '/vendor/') !== false || strpos($file, '/typo3/') !== false;
            $vendorClass = $isVendor ? 'vendor-frame' : 'app-frame';

            $content .= '<div class="trace-frame ' . $vendorClass . ' ' . $isExpanded . '">';

            // Frame header (clickable)
            $content .= '<div class="frame-header" onclick="toggleFrame(\'' . $frameId . '\')">';
            $content .= '<span class="frame-index">#' . $index . '</span>';
            $content .= '<span class="frame-location">';
            if ($class) {
                $content .= '<span class="frame-class">' . $this->escapeHtml($class) . '</span>';
                $content .= '<span class="frame-type">' . $this->escapeHtml($type) . '</span>';
            }
            $content .= '<span class="frame-function">' . $this->escapeHtml($function) . '</span>';
            $content .= '<span class="frame-args">(' . $this->formatArgs($args) . ')</span>';
            $content .= '</span>';
            $content .= '<span class="frame-file">' . $this->escapeHtml(basename($file)) . ':' . $line . '</span>';
            $content .= '<span class="frame-toggle">▼</span>';
            $content .= '</div>';

            // Frame details (collapsible)
            $content .= '<div id="' . $frameId . '" class="frame-details">';

            // Full file path
            $content .= '<div class="frame-full-path">';
            $content .= '<span>' . $this->escapeHtml($file) . ':' . $line . '</span>';
            $content .= '<button class="copy-btn small" onclick="event.stopPropagation(); copyText(\'' . addslashes($file . ':' . $line) . '\')" title="Copy path">';
            $content .= '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
            $content .= '</button>';
            $content .= '</div>';

            // Code snippet for this frame
            if ($file !== '[internal function]' && file_exists($file)) {
                $frameCodeSnippet = $this->getCodeSnippet($file, $line);
                if ($frameCodeSnippet) {
                    $content .= '<div class="frame-code">' . $frameCodeSnippet . '</div>';
                }
            }

            $content .= '</div>';
            $content .= '</div>';
        }

        $content .= '</div>';

        return $content;
    }

    /**
     * Get all throwables (exception chain)
     */
    protected function getAllThrowables(\Throwable $throwable): array
    {
        $throwables = [];
        while ($throwable !== null) {
            $throwables[] = $throwable;
            $throwable = $throwable->getPrevious();
        }
        return $throwables;
    }

    /**
     * Returns a code snippet from the specified file with enhanced formatting
     */
    protected function getCodeSnippet(string $filePathAndName, int $lineNumber): string
    {
        if (!@file_exists($filePathAndName) || !is_readable($filePathAndName)) {
            return '';
        }

        $lines = @file($filePathAndName);
        if ($lines === false) {
            return '';
        }

        $startLine = max(1, $lineNumber - 8);
        $endLine = min(count($lines), $lineNumber + 8);

        $content = '<pre>';
        for ($i = $startLine; $i <= $endLine; $i++) {
            $lineContent = $lines[$i - 1] ?? '';
            $lineContent = $this->escapeHtml(rtrim($lineContent));

            $highlightClass = ($i === $lineNumber) ? ' highlight' : '';
            $content .= '<div class="code-line' . $highlightClass . '">';
            $content .= '<span class="line-num">' . $i . '</span>';
            $content .= '<span class="line-code">' . ($lineContent ?: ' ') . '</span>';
            $content .= '</div>';
        }
        $content .= '</pre>';

        return $content;
    }

    /**
     * Returns a plain text code snippet for Markdown copy functionality
     */
    protected function getPlainCodeSnippet(string $filePathAndName, int $lineNumber): string
    {
        if (!@file_exists($filePathAndName) || !is_readable($filePathAndName)) {
            return '';
        }

        $lines = @file($filePathAndName);
        if ($lines === false) {
            return '';
        }

        $startLine = max(1, $lineNumber - 5);
        $endLine = min(count($lines), $lineNumber + 5);

        $content = '';
        for ($i = $startLine; $i <= $endLine; $i++) {
            $lineContent = $lines[$i - 1] ?? '';
            $lineContent = rtrim($lineContent);
            $marker = ($i === $lineNumber) ? ' // <-- ERROR HERE' : '';
            $content .= sprintf("%4d | %s%s\n", $i, $lineContent, $marker);
        }

        return $content;
    }

    /**
     * Returns enhanced stylesheet - TYPO3 standard look
     */
    protected function getEnhancedStylesheet(): string
    {
        return '
        :root {
            --bg-primary: #f4f4f4;
            --bg-secondary: #ffffff;
            --bg-tertiary: #e8e8e8;
            --text-primary: #333333;
            --text-secondary: #666666;
            --accent-orange: #ff8700;
            --accent-red: #c83c3c;
            --accent-blue: #0078c6;
            --accent-green: #79a548;
            --border-color: #cdcdcd;
            --code-bg: #f8f8f8;
            --line-highlight: rgba(255, 135, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Verdana, Arial, Helvetica, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 12px;
            line-height: 1.5;
            min-height: 100vh;
        }

        .exception-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px 0;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .header .logo svg {
            width: 40px;
            height: 40px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            color: var(--accent-red);
        }

        .exception-block {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .exception-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: var(--accent-red);
            color: white;
        }

        .exception-title {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .exception-number {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            font-size: 11px;
        }

        .exception-class {
            font-weight: bold;
            font-size: 13px;
        }

        .exception-code {
            opacity: 0.9;
            font-size: 12px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 15px;
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-secondary);
        }

        .exception-message-container,
        .exception-location-container,
        .code-snippet-container,
        .stack-trace-container {
            border-bottom: 1px solid var(--border-color);
        }

        .exception-message {
            padding: 15px;
            font-size: 13px;
            word-break: break-word;
            background: #fff8f0;
        }

        .exception-location {
            padding: 10px 15px;
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            background: var(--bg-secondary);
        }

        .file-path {
            color: var(--accent-blue);
        }

        .line-number {
            color: var(--accent-red);
            font-weight: bold;
        }

        /* Copy Button Styles */
        .copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 11px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .copy-btn:hover {
            background: var(--bg-tertiary);
            border-color: var(--accent-orange);
            color: var(--text-primary);
        }

        .exception-header .copy-btn {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.3);
            color: white;
        }

        .exception-header .copy-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .copy-btn.copied {
            background: var(--accent-green) !important;
            border-color: var(--accent-green) !important;
            color: white !important;
        }

        .copy-btn.small {
            padding: 2px 6px;
            font-size: 10px;
        }

        /* Code Snippet Styles */
        .code-snippet {
            background: var(--code-bg);
            overflow-x: auto;
        }

        .code-snippet pre {
            margin: 0;
            padding: 0;
        }

        .code-line {
            display: flex;
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            line-height: 1.6;
        }

        .code-line.highlight {
            background: var(--line-highlight);
        }

        .line-num {
            min-width: 50px;
            padding: 0 10px;
            text-align: right;
            color: var(--text-secondary);
            background: var(--bg-tertiary);
            user-select: none;
            border-right: 1px solid var(--border-color);
        }

        .line-code {
            padding: 0 15px;
            white-space: pre;
            flex: 1;
        }

        /* Stack Trace Styles */
        .stack-trace {
            max-height: 500px;
            overflow-y: auto;
        }

        .trace-frames {
            padding: 0;
        }

        .trace-frame {
            border-bottom: 1px solid var(--border-color);
        }

        .trace-frame:last-child {
            border-bottom: none;
        }

        .trace-frame.vendor-frame .frame-header {
            background: #fafafa;
        }

        .trace-frame.app-frame .frame-header {
            background: #fffef5;
        }

        .frame-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .frame-header:hover {
            background: #fff8e0;
        }

        .frame-index {
            color: var(--text-secondary);
            font-size: 11px;
            min-width: 25px;
        }

        .frame-location {
            flex: 1;
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .frame-class {
            color: var(--accent-blue);
        }

        .frame-type {
            color: var(--text-secondary);
        }

        .frame-function {
            color: var(--accent-orange);
            font-weight: bold;
        }

        .frame-args {
            color: var(--text-secondary);
            font-size: 10px;
        }

        .frame-file {
            color: var(--text-secondary);
            font-size: 10px;
            white-space: nowrap;
        }

        .frame-toggle {
            color: var(--text-secondary);
            transition: transform 0.2s ease;
            font-size: 10px;
        }

        .trace-frame.expanded .frame-toggle {
            transform: rotate(180deg);
        }

        .frame-details {
            display: none;
            background: var(--code-bg);
            border-top: 1px solid var(--border-color);
        }

        .trace-frame.expanded .frame-details {
            display: block;
        }

        .frame-full-path {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 15px;
            font-family: "Courier New", Courier, monospace;
            font-size: 11px;
            color: var(--text-secondary);
            background: var(--bg-tertiary);
        }

        .frame-code {
            border-top: 1px solid var(--border-color);
        }

        /* Scrollbar Styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        /* Toast notification for copy feedback */
        .copy-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--accent-green);
            color: white;
            padding: 10px 20px;
            font-size: 12px;
            font-weight: bold;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            z-index: 1000;
            border: 1px solid #5a8a30;
        }

        .copy-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .exception-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .frame-header {
                flex-wrap: wrap;
            }

            .frame-location {
                order: 2;
                width: 100%;
                margin-top: 5px;
            }
        }
        ';
    }

    /**
     * Returns JavaScript for copy functionality
     */
    protected function getCopyButtonScript(): string
    {
        return '
        <div id="copy-toast" class="copy-toast">Copied to clipboard!</div>
        <script>
        function showToast(message = "Copied to clipboard!") {
            const toast = document.getElementById("copy-toast");
            toast.textContent = message;
            toast.classList.add("show");
            setTimeout(() => toast.classList.remove("show"), 2000);
        }

        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast();
            }).catch(err => {
                // Fallback for older browsers
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-9999px";
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand("copy");
                    showToast();
                } catch (e) {
                    showToast("Failed to copy");
                }
                document.body.removeChild(textArea);
            });
        }

        function copyElement(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                // Get text content, preserving structure
                let text = element.innerText || element.textContent;
                copyText(text.trim());

                // Visual feedback on button
                const btn = element.previousElementSibling?.querySelector(".copy-btn") 
                         || element.closest(".exception-message-container, .exception-location-container, .code-snippet-container")?.querySelector(".copy-btn");
                if (btn) {
                    btn.classList.add("copied");
                    setTimeout(() => btn.classList.remove("copied"), 2000);
                }
            }
        }

        function copyStackTrace(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                // Build a clean stack trace text
                const frames = element.querySelectorAll(".trace-frame");
                let traceText = "";
                frames.forEach((frame, index) => {
                    const location = frame.querySelector(".frame-location")?.textContent || "";
                    const file = frame.querySelector(".frame-full-path span")?.textContent || "";
                    traceText += "#" + index + " " + location.trim() + "\\n    " + file + "\\n";
                });
                copyText(traceText.trim());
            }
        }

        function copyAll(uniqueId) {
            const element = document.getElementById(uniqueId + "-all");
            if (element) {
                copyText(element.textContent.trim());

                // Visual feedback
                const btn = document.querySelector(".copy-all");
                if (btn) {
                    btn.classList.add("copied");
                    setTimeout(() => btn.classList.remove("copied"), 2000);
                }
            }
        }

        function toggleFrame(frameId) {
            const frame = document.getElementById(frameId);
            const container = frame?.closest(".trace-frame");
            if (container) {
                container.classList.toggle("expanded");
            }
        }

        // Keyboard shortcuts
        document.addEventListener("keydown", function(e) {
            // Ctrl/Cmd + Shift + C to copy all
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === "C") {
                e.preventDefault();
                const copyAllBtn = document.querySelector(".copy-all");
                if (copyAllBtn) copyAllBtn.click();
            }
        });
        </script>
        ';
    }
}
