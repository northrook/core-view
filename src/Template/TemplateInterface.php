<?php

namespace Core\View\Template;

interface TemplateInterface {

    public function getName(): string;

    public function getParameters(): array;

    public function render() : string;
}
