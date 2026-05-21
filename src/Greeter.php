<?php
namespace lindemannrock\releaseplease\test;

class Greeter
{
    public function greet(string $name): string
    {
        $safe = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return "<p>Hello, {$safe}!</p>";
    }
}
