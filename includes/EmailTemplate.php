<?php
class EmailTemplate {
    private $templateDir;
    private $template;
    private $data;

    public function __construct($templateDir = null) {
        $this->templateDir = $templateDir ?? __DIR__ . '/../templates/';
    }

    public function load($templateName) {
        $templatePath = $this->templateDir . $templateName . '.html';
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: $templatePath");
        }
        $this->template = file_get_contents($templatePath);
        return $this;
    }

    public function setData($data) {
        $this->data = $data;
        return $this;
    }

    private function replaceSimpleVariables($content, $data) {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', htmlspecialchars($value), $content);
        }
        return $content;
    }

    private function processConditionals($content, $data) {
        $pattern = '/{{#if\s+([^}]+)}}(.*?){{\/if}}/s';
        return preg_replace_callback($pattern, function($matches) use ($data) {
            $condition = trim($matches[1]);
            $content = $matches[2];
            return !empty($data[$condition]) ? $content : '';
        }, $content);
    }

    public function render() {
        if (empty($this->template)) {
            throw new Exception("No template loaded");
        }
        if (empty($this->data)) {
            throw new Exception("No data set for template");
        }

        $content = $this->template;
        // Process conditionals first
        $content = $this->processConditionals($content, $this->data);
        // Then replace variables
        $content = $this->replaceSimpleVariables($content, $this->data);
        
        return $content;
    }

    public function getAvailableTemplates() {
        $templates = [];
        foreach (glob($this->templateDir . '*.html') as $file) {
            $templates[] = basename($file, '.html');
        }
        return $templates;
    }
}