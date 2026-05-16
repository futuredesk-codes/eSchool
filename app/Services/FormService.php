<?php

declare(strict_types=1);

namespace App\Services;

class FormService
{
    public function open(array $options = []): string
    {
        $method = $options['method'] ?? 'POST';
        $route = $options['route'] ?? '';
        $action = $options['action'] ?? '';
        
        if ($route) {
            $action = route($route);
        }
        
        $html = '<form';
        if ($action) {
            $html .= ' action="' . $action . '"';
        }
        if ($method !== 'GET') {
            $html .= ' method="' . $method . '"';
        }
        
        $html .= '>';
        
        if ($method !== 'GET' && $method !== 'POST') {
            $html .= '<input type="hidden" name="_method" value="' . $method . '">';
        }
        
        if ($method !== 'GET') {
            $html .= csrf_field();
        }
        
        return $html;
    }
    
    public function close(): string
    {
        return '</form>';
    }
    
    public function text(string $name, $value = null, array $options = []): string
    {
        return $this->input('text', $name, $value, $options);
    }
    
    public function textarea(string $name, $value = null, array $options = []): string
    {
        $attributes = $this->buildAttributes($options);
        $value = $value ?? '';
        
        return '<textarea name="' . $name . '"' . $attributes . '>' . e($value) . '</textarea>';
    }
    
    public function password(string $name, array $options = []): string
    {
        return $this->input('password', $name, null, $options);
    }
    
    public function number(string $name, $value = null, array $options = []): string
    {
        return $this->input('number', $name, $value, $options);
    }
    
    public function tel(string $name, $value = null, array $options = []): string
    {
        return $this->input('tel', $name, $value, $options);
    }
    
    public function email(string $name, $value = null, array $options = []): string
    {
        return $this->input('email', $name, $value, $options);
    }
    
    public function select(string $name, array $options = [], $selected = null, array $attributes = []): string
    {
        $attributes = $this->buildAttributes($attributes);
        $html = '<select name="' . $name . '"' . $attributes . '>';
        
        foreach ($options as $value => $label) {
            $isSelected = ($selected == $value) ? ' selected' : '';
            $html .= '<option value="' . e($value) . '"' . $isSelected . '>' . e($label) . '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }
    
    public function radio(string $name, $value, $checked = false, array $options = []): string
    {
        $options['type'] = 'radio';
        if ($checked) {
            $options['checked'] = 'checked';
        }
        return $this->input('radio', $name, $value, $options);
    }
    
    public function checkbox(string $name, $value = 1, $checked = false, array $options = []): string
    {
        $options['type'] = 'checkbox';
        if ($checked) {
            $options['checked'] = 'checked';
        }
        return $this->input('checkbox', $name, $value, $options);
    }
    
    public function submit(string $value, array $options = []): string
    {
        $options['type'] = 'submit';
        return $this->input('submit', '', $value, $options);
    }
    
    public function hidden(string $name, $value = null, array $options = []): string
    {
        return $this->input('hidden', $name, $value, $options);
    }
    
    public function model($model, array $options = []): string
    {
        // For now, just return an empty string as this is complex to implement
        return '';
    }
    
    private function input(string $type, string $name, $value = null, array $options = []): string
    {
        $attributes = $this->buildAttributes($options);
        $value = $value ?? '';
        
        // Handle readonly attribute properly
        $readonlyAttr = '';
        if (isset($options['readonly']) && $options['readonly']) {
            $readonlyAttr = ' readonly';
        }
        
        return '<input type="' . $type . '" name="' . $name . '" value="' . e($value) . '"' . $attributes . $readonlyAttr . '>';
    }
    
    private function buildAttributes(array $options): string
    {
        $attributes = '';
        
        foreach ($options as $key => $value) {
            if ($key === 'type') continue; // Skip type as it's handled separately
            
            if (is_bool($value)) {
                if ($value) {
                    $attributes .= ' ' . $key;
                }
            } else {
                $attributes .= ' ' . $key . '="' . e($value) . '"';
            }
        }
        
        return $attributes;
    }
} 